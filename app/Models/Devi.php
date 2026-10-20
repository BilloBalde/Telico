<?php
// app/Models/Devi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devi extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'valid_until' => 'date',
        'validated_at' => 'datetime',
        'total_amount' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::creating(function ($devis) {
            if (auth()->check()) {
                $devis->created_by = auth()->id();
            }
            
            // Generate numero_devis automatically
            $devis->numero_devis = $devis->generateNumber();
        });
    }

    public function generateNumber()
    {
        $yearMonth = now()->format('Ym');
        $lastDevis = self::where('numero_devis', 'like', 'DEV-' . $yearMonth . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastDevis) {
            $lastNumber = intval(substr($lastDevis->numero_devis, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return 'DEV-' . $yearMonth . '-' . $newNumber;
    }

    public function lines()
    {
        return $this->hasMany(DeviLine::class, 'devis_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'draft' => '<span class="badge bg-secondary">Brouillon</span>',
            'sent' => '<span class="badge bg-info">Envoyé</span>',
            'accepted' => '<span class="badge bg-success">Accepté</span>',
            'rejected' => '<span class="badge bg-danger">Rejeté</span>',
            'expired' => '<span class="badge bg-warning">Expiré</span>',
            default => '<span class="badge bg-secondary">' . $this->status . '</span>'
        };
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 0, ',', ' ') . ' FG';
    }

    /**
     * Valider le devis - cela créera une facture et affectera le stock
     */
    /**
     * Refuse toute ligne vendue sous le plancher de vente du produit (prix
     * minimum de la fiche, ou coût de revient du propriétaire de la marchandise).
     *
     * @throws \RuntimeException
     */
    public function verifierPrix(): void
    {
        $funds = app(\App\Services\FundService::class);
        foreach ($this->lines()->with('product')->get() as $line) {
            if (! $line->product) {
                continue;
            }
            $cout = $line->fund_id ? $funds->unitCost((int) $line->fund_id, (int) $line->product_id) : null;
            $line->product->verifierPrixDeVente((float) $line->unit_price, $cout);
        }
    }

    public function validate()
    {
        if ($this->status !== 'accepted') {
            throw new \Exception('Seuls les devis acceptés peuvent être validés.');
        }

        if ($this->validated_at) {
            throw new \Exception('Ce devis a déjà été validé.');
        }

        if (! $this->customer_id) {
            throw new \Exception('Associez un client au devis avant de le valider : la facture est émise à son nom.');
        }

        $this->loadMissing('lines.product');

        // Chaque ligne sort de son propre emplacement (par défaut celui du devis).
        $lineStore = fn ($line) => (int) ($line->store_id ?: $this->store_id);

        $user = auth()->user();
        foreach ($this->lines as $line) {
            if ($user && ! $user->canAccessStore($lineStore($line))) {
                throw new \Exception('Vous n\'êtes pas autorisé à puiser dans l\'emplacement d\'une des lignes.');
            }
        }

        $stock = app(\App\Services\StockService::class);

        // Avant : aucune vérification, un devis pouvait rendre le stock négatif,
        // et la facture créée n'avait ni numéro ni avance (colonnes obligatoires),
        // ce qui faisait échouer toute validation.
        return \Illuminate\Support\Facades\DB::transaction(function () use ($stock, $lineStore) {
            // Chaque ligne puise dans le stock de son propriétaire (fonds propres
            // si fund_id est vide, sinon le fonds de l'associé).
            $stock->assertAvailable($this->lines->map(fn ($line) => [
                'store_id'   => $lineStore($line),
                'product_id' => $line->product_id,
                'quantity'   => $line->quantity,
                'fund_id'    => $line->fund_id,
            ])->all());

            $numero = Facture::prochainNumero();
            $total = (float) $this->lines->sum('total_price');
            $funds = app(\App\Services\FundService::class);

            // Prix de chaque ligne contrôlés avant toute écriture : un devis
            // négocié sous le prix minimum ou à perte n'est pas transformé en vente.
            $this->verifierPrix();

            foreach ($this->lines as $line) {
                $fundId = $line->fund_id ? (int) $line->fund_id : null;

                $stock->decrementOrFail($lineStore($line), (int) $line->product_id, (int) $line->quantity, [
                    'type'         => StockMovement::SALE,
                    'source_type'  => 'devis',
                    'source_id'    => $this->id,
                    'document_ref' => $numero,
                    'customer_id'  => $this->customer_id,
                    'unit_price'   => $line->unit_price,
                    'fund_id'      => $fundId,
                ]);

                // Marge au coût du propriétaire ; 0 si le coût est inconnu,
                // plutôt qu'une marge fictive égale au prix de vente.
                $coutFonds = $fundId ? $funds->unitCost($fundId, (int) $line->product_id) : null;
                $interet = $fundId
                    ? ($coutFonds > 0 ? ((float) $line->unit_price - $coutFonds) * (int) $line->quantity : 0)
                    : ($line->product ? $line->product->interetPour((float) $line->unit_price, (int) $line->quantity) : 0);

                Sale::create([
                    'numeroFacture' => $numero,
                    'product_id'    => $line->product_id,
                    'prix'          => $line->unit_price,
                    'quantity'      => $line->quantity,
                    'prixTotal'     => $line->total_price,
                    'interet'       => $interet,
                    'store_id'      => $lineStore($line),
                    'user_id'       => auth()->id(),
                    'fund_id'       => $fundId,
                    'unit_cost'     => $fundId ? $coutFonds : null,
                ]);
            }

            $facture = Facture::create([
                'numero_facture' => $numero,
                'store_id'       => $this->store_id,
                'user_id'        => auth()->id(),
                'channel'        => Facture::CHANNEL_DEVIS,
                'customer_id'    => $this->customer_id,
                'montant_total'  => $total,
                'quantity'       => $this->lines->sum('quantity'),
                'avance'         => 0,
                'reste'          => $total,
                'notes'          => 'Devis #' . $this->numero_devis . ' - ' . ($this->notes ?? ''),
                'statut'         => 'non payé',
                'livraison'      => 'non livré',
            ]);

            Customer::find($this->customer_id)?->recalculerCompteurs();

            $this->update([
                'validated_by' => auth()->id(),
                'validated_at' => now(),
                'status'       => 'validated',
            ]);

            return $facture;
        });
    }
}