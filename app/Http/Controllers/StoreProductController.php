<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\Product;
use App\Services\TransferService;
use Illuminate\Http\Request;

/**
 * Transferts de stock entre emplacements (route resource « transfers »).
 * La logique du circuit (demande → autorisation → expédition → réception)
 * est dans TransferService.
 */
class StoreProductController extends Controller
{
    public function __construct(private TransferService $transfers)
    {
        $this->middleware('auth.check');
        // Aucune permission n'était vérifiée : n'importe quel employé connecté,
        // vendeur compris, pouvait déplacer du stock entre boutiques.
        $this->middleware('permission:transfers.view')->only(['index', 'show']);
        $this->middleware('permission:transfers.create')->only(['create', 'store']);
        $this->middleware('permission:transfers.edit')->only(['edit', 'update']);
        $this->middleware('permission:transfers.approve')->only(['approve', 'reject']);
        $this->middleware('permission:transfers.execute')->only(['ship', 'receive']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $produits = Product::orderBy('libelle')->get(['id', 'libelle', 'sku', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        // Toutes les destinations possibles (un magasin peut demander à un
        // entrepôt hors de son périmètre) ; la visibilité des transferts, elle,
        // reste limitée au périmètre.
        $boutiques = Store::optionsFor(null);
        $mesEmplacements = Store::optionsFor($user);

        $query = StockTransfer::query()->visibleTo($user);

        if ($request->filled('from_store_id')) {
            $query->where('from_store_id', $request->input('from_store_id'));
        }

        if ($request->filled('to_store_id')) {
            $query->where('to_store_id', $request->input('to_store_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->input('created_at'));
        }

        // Eager loading + pagination serveur (évite ~1100 requêtes N+1)
        $dataTable = $query->with(['product.categories', 'fromStore', 'toStore', 'requester', 'approver', 'shipper', 'receiver'])
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        $funds = \App\Models\Fund::active()->orderBy('name')->get();

        return view('transfers.index', compact('dataTable', 'produits', 'boutiques', 'mesEmplacements', 'funds'));
    }

    public function create()
    {
        return redirect()->route('transfers.index');
    }

    /**
     * Demande de transfert. Si l'utilisateur peut à la fois autoriser et
     * exécuter, et coche « exécuter immédiatement », le transfert est mené à
     * son terme en une fois (comportement historique), étapes tracées.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'fund_id' => 'nullable|exists:funds,id',
        ]);

        $user = auth()->user();

        try {
            $transfer = $this->transfers->request($user, $data);

            if ($request->boolean('execute_now')
                && $user->hasPermission('transfers.approve')
                && $user->hasPermission('transfers.execute')) {
                $this->transfers->executeImmediately($user, $transfer);

                return redirect()->route('transfers.index')->with('success', "Transfert {$transfer->reference} effectué : stock déplacé.");
            }

            return redirect()->route('transfers.index')->with('success', "Transfert {$transfer->reference} demandé, en attente d'autorisation.");
        } catch (\Throwable $th) {
            return redirect()->route('transfers.index')->with('error', $th->getMessage())->withInput();
        }
    }

    public function approve(StockTransfer $transfer)
    {
        return $this->step(fn () => $this->transfers->approve(auth()->user(), $transfer), "Transfert {$transfer->reference} autorisé.");
    }

    public function reject(Request $request, StockTransfer $transfer)
    {
        return $this->step(fn () => $this->transfers->reject(auth()->user(), $transfer, $request->input('reason')), "Transfert {$transfer->reference} refusé.");
    }

    public function ship(StockTransfer $transfer)
    {
        return $this->step(fn () => $this->transfers->ship(auth()->user(), $transfer), "Transfert {$transfer->reference} expédié : stock sorti de la source.");
    }

    public function receive(StockTransfer $transfer)
    {
        return $this->step(fn () => $this->transfers->receive(auth()->user(), $transfer), "Transfert {$transfer->reference} réceptionné : stock entré à destination.");
    }

    public function cancel(StockTransfer $transfer)
    {
        $user = auth()->user();
        if (! $user->hasPermission('transfers.approve') && (int) $transfer->requested_by !== (int) $user->id) {
            abort(403, 'Seul le demandeur ou un responsable peut annuler ce transfert.');
        }

        return $this->step(fn () => $this->transfers->cancel($user, $transfer), "Transfert {$transfer->reference} annulé.");
    }

    private function step(callable $action, string $success)
    {
        try {
            $action();

            return redirect()->route('transfers.index')->with('success', $success);
        } catch (\Throwable $th) {
            return redirect()->route('transfers.index')->with('error', $th->getMessage());
        }
    }

    public function show(StoreProduct $storeProduct)
    {
        return redirect()->route('transfers.index');
    }

    public function edit($id)
    {
        $storeProduct = StockTransfer::visibleTo(auth()->user())->findOrFail($id);
        $produits = Product::all();
        $boutiques = Store::optionsFor(null);

        return view('transfers.edit', compact('storeProduct', 'produits', 'boutiques'));
    }

    /**
     * Correction de la quantité d'un transfert terminé. L'ancienne version
     * annulait puis réécrivait le transfert (produit, source et destination
     * compris) sans laisser de trace ; désormais seul l'écart de quantité est
     * appliqué, par des mouvements de correction visibles dans le journal.
     * Un transfert vers le mauvais emplacement se corrige par un nouveau
     * transfert, pas en réécrivant l'ancien.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:500',
        ]);

        $transfer = StockTransfer::visibleTo(auth()->user())->findOrFail($id);

        if ((int) $request->product_id && (int) $request->product_id !== (int) $transfer->product_id
            || (int) $request->from_store_id && (int) $request->from_store_id !== (int) $transfer->from_store_id
            || (int) $request->to_store_id && (int) $request->to_store_id !== (int) $transfer->to_store_id) {
            return redirect()->route('transfers.index')->with('error', 'Le produit, la source et la destination d\'un transfert ne se modifient pas : annulez-le ou créez un transfert inverse.');
        }

        return $this->step(
            fn () => $this->transfers->correctQuantity(auth()->user(), $transfer, (int) $request->quantity, $request->input('reason')),
            "Transfert {$transfer->reference} corrigé."
        );
    }

    public function destroy(StoreProduct $storeProduct)
    {
        abort(405, 'Un transfert ne se supprime pas : annulez-le.');
    }
}
