<?php
// app/Http/Controllers/DevisController.php

namespace App\Http\Controllers;

use App\Models\Devi;
use App\Models\DeviLine;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DeviController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $query = Devi::with(['store', 'customer', 'createdBy']);

        if ($request->filled('numero_devis')) {
            $query->where('numero_devis', 'like', '%' . $request->numero_devis . '%');
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by store for staff
        if (auth()->user()->isStoreScoped()) {
            $storeId = auth()->user()->assignedStoreId();
            $query->where('store_id', $storeId);
        }

        $devis = $query->orderBy('created_at', 'desc')->paginate(15);
        $customers = Customer::all();

        return view('devis.index', compact('devis', 'customers'));
    }

    public function create()
    {
        $stores = Store::all();
        $products = Product::with('stores')->get()
            // Unité unique de chaque produit, affichée dans le choix du produit.
            ->each(function ($p) {
                $p->setAttribute('unite_pluriel', Product::motUnite($p->unite, 2))->append('unite_libelle');
                // Plancher de vente, signalé à la saisie des prix du devis.
                [$plancher, $origine] = $p->plancherDeVente();
                $p->setAttribute('plancher', $plancher);
                $p->setAttribute('plancher_origine', $origine === 'minimum' ? 'prix de vente minimum' : 'prix de revient');
            });
        $customers = Customer::all();

        // For staff, preselect their store
        $userStoreId = null;
        if (auth()->user()->isStoreScoped()) {
            $userStoreId = auth()->user()->assignedStoreId();
        }

        $funds = \App\Models\Fund::active()->orderBy('name')->get(['id', 'name']);
        return view('devis.create', compact('stores', 'products', 'customers', 'userStoreId', 'funds'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_id' => 'nullable|exists:customers,id',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            // Emplacement source par ligne (facultatif : sinon celui du devis).
            'products.*.store_id' => 'nullable|exists:stores,id',
            // Propriétaire de la marchandise : vide = fonds propres.
            'products.*.fund_id' => 'nullable|exists:funds,id',
        ]);

        $this->authorizeStore($request->store_id);
        foreach ((array) $request->products as $ligne) {
            if (! empty($ligne['store_id'])) {
                $this->authorizeStore($ligne['store_id']);
            }
        }

        // Prix contrôlés dès la saisie du devis (même règle qu'à la caisse) :
        // le vendeur le sait tout de suite, pas à la validation.
        if ($erreur = $this->erreurDePrix((array) $request->products)) {
            return back()->withInput()->with('error', $erreur);
        }

        DB::beginTransaction();
        try {
            // Create customer if new
            $customerId = $request->customer_id;
            if (!$customerId && $request->customer_name) {
                $customer = Customer::create([
                    'customerName' => $request->customer_name,
                    'tel' => $request->customer_phone ?? 'N/A',
                    'address' => $request->customer_address ?? 'N/A',
                    'mark' => 'DEV',
                ]);
                $customerId = $customer->id;
            }

            // Calculate total
            $totalAmount = 0;
            foreach ($request->products as $product) {
                $totalAmount += $product['quantity'] * $product['unit_price'];
            }

            // Create devis
            $devis = Devi::create([
                'store_id' => $request->store_id,
                'customer_id' => $customerId,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'valid_until' => $request->valid_until,
                'status' => 'draft',
            ]);

            // Create devi lines
            foreach ($request->products as $productData) {
                $totalPrice = $productData['quantity'] * $productData['unit_price'];

                DeviLine::create([
                    'devis_id' => $devis->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'total_price' => $totalPrice,
                    'notes' => $productData['notes'] ?? null,
                    'store_id' => $productData['store_id'] ?? null,
                    'fund_id' => ! empty($productData['fund_id']) ? (int) $productData['fund_id'] : null,
                ]);
            }

            DB::commit();

            return redirect()->route('devis.show', $devis->id)
                ->with('success', 'Devis créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');

        // Get company info for PDF
        $company = \App\Models\Company::current();

        return view('devis.show', compact('devis', 'company'));
    }

    public function edit($id)
    {
        $devis = Devi::with('lines')->findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');

        // Can't edit if already accepted or validated
        if ($devis->status !== 'draft') {
            return redirect()->route('devis.show', $id)
                ->with('error', 'Seuls les devis en brouillon peuvent être modifiés.');
        }

        $stores = Store::all();
        $products = Product::with('stores')->get()
            // Unité unique de chaque produit, affichée dans le choix du produit.
            ->each(function ($p) {
                $p->setAttribute('unite_pluriel', Product::motUnite($p->unite, 2))->append('unite_libelle');
                // Plancher de vente, signalé à la saisie des prix du devis.
                [$plancher, $origine] = $p->plancherDeVente();
                $p->setAttribute('plancher', $plancher);
                $p->setAttribute('plancher_origine', $origine === 'minimum' ? 'prix de vente minimum' : 'prix de revient');
            });
        $customers = Customer::all();

        $funds = \App\Models\Fund::orderBy('name')->get(['id', 'name']);
        return view('devis.edit', compact('devis', 'stores', 'products', 'customers', 'funds'));
    }

    public function update(Request $request, $id)
    {
        $devis = Devi::findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');

        if ($devis->status !== 'draft') {
            return back()->with('error', 'Seuls les devis en brouillon peuvent être modifiés.');
        }

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_id' => 'nullable|exists:customers,id',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            // Emplacement source par ligne (facultatif : sinon celui du devis).
            'products.*.store_id' => 'nullable|exists:stores,id',
            // Propriétaire de la marchandise : vide = fonds propres.
            'products.*.fund_id' => 'nullable|exists:funds,id',
        ]);

        $this->authorizeStore($request->store_id);
        foreach ((array) $request->products as $ligne) {
            if (! empty($ligne['store_id'])) {
                $this->authorizeStore($ligne['store_id']);
            }
        }

        // Prix contrôlés dès la saisie du devis (même règle qu'à la caisse) :
        // le vendeur le sait tout de suite, pas à la validation.
        if ($erreur = $this->erreurDePrix((array) $request->products)) {
            return back()->withInput()->with('error', $erreur);
        }

        DB::beginTransaction();
        try {
            // Calculate total
            $totalAmount = 0;
            foreach ($request->products as $product) {
                $totalAmount += $product['quantity'] * $product['unit_price'];
            }

            // Update devis
            $devis->update([
                'store_id' => $request->store_id,
                'customer_id' => $request->customer_id,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'valid_until' => $request->valid_until,
            ]);

            // Delete old lines
            $devis->lines()->delete();

            // Create new lines
            foreach ($request->products as $productData) {
                $totalPrice = $productData['quantity'] * $productData['unit_price'];

                DeviLine::create([
                    'devis_id' => $devis->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'total_price' => $totalPrice,
                    'notes' => $productData['notes'] ?? null,
                    'store_id' => $productData['store_id'] ?? null,
                    'fund_id' => ! empty($productData['fund_id']) ? (int) $productData['fund_id'] : null,
                ]);
            }

            DB::commit();

            return redirect()->route('devis.show', $devis->id)
                ->with('success', 'Devis mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /** Première ligne vendue sous le plancher de vente du produit, ou null. */
    private function erreurDePrix(array $lignes): ?string
    {
        $funds = app(\App\Services\FundService::class);
        foreach ($lignes as $ligne) {
            $produit = Product::find($ligne['product_id'] ?? null);
            if (! $produit) {
                continue;
            }
            $cout = ! empty($ligne['fund_id']) ? $funds->unitCost((int) $ligne['fund_id'], (int) $produit->id) : null;
            try {
                $produit->verifierPrixDeVente((float) ($ligne['unit_price'] ?? 0), $cout);
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    public function destroy($id)
    {
        $devis = Devi::findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');

        // Can only delete draft devis
        if ($devis->status !== 'draft') {
            return back()->with('error', 'Seuls les devis en brouillon peuvent être supprimés.');
        }

        $devis->delete();

        return redirect()->route('devis.index')
            ->with('success', 'Devis supprimé avec succès.');
    }

    /**
     * Change status of devis
     */
    public function changeStatus(Request $request, $id)
    {
        $devis = Devi::findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');

        $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
        ]);

        $devis->update(['status' => $request->status]);

        $statusMessages = [
            'draft' => 'Devis remis en brouillon.',
            'sent' => 'Devis marqué comme envoyé.',
            'accepted' => 'Devis accepté par le client.',
            'rejected' => 'Devis rejeté.',
            'expired' => 'Devis expiré.',
        ];

        return back()->with('success', $statusMessages[$request->status]);
    }

    /**
     * Validate devis - create invoice and affect stock
     */
    public function validateDevis($id)
    {
        $devis = Devi::with('lines')->findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');

        if ($devis->status !== 'accepted') {
            return back()->with('error', 'Seuls les devis acceptés peuvent être validés.');
        }

        if ($devis->validated_at) {
            return back()->with('error', 'Ce devis a déjà été validé.');
        }

        try {
            $facture = $devis->validate();

            return redirect()->route('factures.show', $facture->numero_facture)
                ->with('success', 'Devi validé avec succès. Facture #' . $facture->numero_facture . ' créée.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function showPdf($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');
        $company = \App\Models\Company::current();

        return view('devis.pdf', compact('devis', 'company'));
    }

    public function downloadPdf($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');
        $company = \App\Models\Company::current();

        $html = view('devis.pdf', compact('devis', 'company'))->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download('Devis_' . $devis->numero_devis . '.pdf');
    }

    /**
     * Generate PDF for devis
     */
    public function generatePdf($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $this->autoriserPerimetre($devis->store_id, 'Ce devis appartient à un autre magasin.');
        $company = \App\Models\Company::current();

        $pdf = Pdf::loadView('devis.pdf', compact('devis', 'company'));
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download('Devis_' . $devis->numero_devis . '.pdf');
    }
}