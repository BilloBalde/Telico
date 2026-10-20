<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Rapport des ventes tierces en Excel, avec les mêmes filtres que la page :
 * une feuille par partenaire (dû, réglé, reste), le détail des produits pris
 * chez les partenaires et les règlements. Les montants et quantités restent
 * des nombres, pour pouvoir calculer dans Excel ; l'unité est dans sa colonne.
 */
class VentesTiercesExport implements WithMultipleSheets
{
    public function __construct(private array $donnees)
    {
    }

    public function sheets(): array
    {
        $d = $this->donnees;
        $periode = 'Période : ' . $d['start']->format('d/m/Y') . ' au ' . $d['end']->format('d/m/Y');

        $partenaires = [
            'Par partenaire',
            array_merge(
                ['Partenaire', 'Téléphone', 'Lignes', 'Quantités', 'Ventes (FG)', 'Dû période (FG)', 'Marge (FG)'],
                $d['peutRegler'] ? ['Dû total (FG)', 'Réglé (FG)', 'Reste à payer (FG)'] : []
            ),
            $d['partenaires']->map(fn ($r) => array_merge([
                $r->partner->name,
                $r->partner->phone,
                (int) ($r->periode->nb ?? 0),
                $r->qty,
                (float) ($r->periode->ca ?? 0),
                (float) ($r->periode->du ?? 0),
                (float) ($r->periode->marge ?? 0),
            ], $d['peutRegler'] ? [$r->du, $r->regle, max(0, $r->solde)] : []))->all(),
        ];

        $detail = [
            'Détail',
            ['Date', 'Facture', 'Vendeur', 'Emplacement', 'Produit', 'Partenaire', 'Quantité', 'Unité',
                'Payé au partenaire / unité (FG)', 'Prix de vente / unité (FG)', 'Dû (FG)', 'Vente (FG)', 'Marge (FG)', 'Client'],
            $d['details']->map(fn ($s) => [
                $s->created_at->format('d/m/Y H:i'),
                $s->numeroFacture,
                $s->user?->name,
                $s->store?->store_name,
                $s->product?->libelle ?? 'Produit #' . $s->product_id,
                $s->partner?->name,
                (float) $s->quantity,
                Product::motUnite($s->unite ?: $s->product?->unite, $s->quantity),
                (float) $s->purchase_price_from_partner,
                (float) $s->prix,
                $s->du_partenaire,
                (float) $s->prixTotal,
                (float) $s->interet,
                $d['customers'][$s->facture_customer_id] ?? null,
            ])->all(),
        ];

        $feuilles = [
            new FeuilleTiers($partenaires[0], $periode, $partenaires[1], $partenaires[2]),
            new FeuilleTiers($detail[0], $periode, $detail[1], $detail[2]),
        ];

        if ($d['peutRegler']) {
            $feuilles[] = new FeuilleTiers('Règlements', $periode,
                ['Date', 'Partenaire', 'Montant (FG)', 'Mode', 'Note', 'Enregistré par'],
                $d['reglements']->map(fn ($r) => [
                    $r->paid_at->format('d/m/Y'), $r->partner?->name, (float) $r->amount, $r->mode_label, $r->note, $r->user?->name,
                ])->all()
            );
        }

        return $feuilles;
    }
}
