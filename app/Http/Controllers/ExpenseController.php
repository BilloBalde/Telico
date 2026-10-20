<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Store;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    /**
     * Vue globale (accès à tous les magasins) : même règle que le reste du
     * logiciel. Un employé rattaché ne voit que les dépenses de son périmètre
     * (son magasin et les entrepôts / stocks qui en dépendent).
     */
    private function isAdmin()
    {
        $user = auth()->user();
        return $user && ! $user->isStoreScoped();
    }

    private function peutVoir($storeId): bool
    {
        return $this->isAdmin() || auth()->user()->canAccessStore($storeId);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with('store', 'category'); // charge les relations pour l'affichage
        $categories_expenses = ExpenseCategory::all();

        // 🔒 Restriction par magasin : si pas admin, on filtre par le store de l'utilisateur
        if (!$this->isAdmin()) {
            $query->whereIn('store_id', auth()->user()->accessibleStoreIds());
        }

        // Filtres de recherche
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }
        if ($request->filled('expense_categories_id')) {
            $query->where('expense_categories_id', $request->expense_categories_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('amount')) {
            $query->where('amount', 'like', '%' . $request->amount . '%');
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        $expenses = $query->get();

        return view('expenses.index', compact('expenses', 'categories_expenses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ExpenseCategory::all();
        $stores = $this->isAdmin() ? Store::all() : Store::whereIn('id', auth()->user()->accessibleStoreIds())->get();
        $ref = "DEP" . Carbon::now()->format('Ym') . sprintf("%04d", Expense::count() + 1);
        return view('expenses.create', compact('categories', 'ref', 'stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 🔒 Un non-admin ne peut créer une dépense que pour sa propre boutique
        // (edit/update/destroy appliquaient déjà cette règle, pas store()).
        if (! $this->isAdmin() && ! auth()->user()->canAccessStore($request->input('store_id'))) {
            $request->merge(['store_id' => auth()->user()->store_id]);
        }

        // Le résultat de validate() ne contient que les champs déclarés ici.
        // C'est lui qu'on écrit en base : $request->all() laissait passer tout le
        // reste, dont user_id — le formulaire l'envoie en champ caché, il suffisait
        // de le modifier pour attribuer la dépense à un collègue.
        $donnees = $request->validate([
            'reference' => 'required|string|max:255|unique:expenses,reference',
            'expense_categories_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'store_id' => 'required|exists:stores,id',
        ]);

        // L'auteur vient de la session, jamais de la requête.
        $donnees['user_id'] = auth()->id();

        try {
            Expense::create($donnees);
            return redirect()->route('expenses.index')->with('success', 'Dépense créée avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expenses.index')->with('error', 'Erreur lors de la création : ' . $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        // 🔒 Vérification : un non-admin ne peut modifier que ses propres dépenses (même store)
        if (! $this->peutVoir($expense->store_id)) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette dépense.');
        }

        $categories = ExpenseCategory::all();
        $stores = $this->isAdmin() ? Store::all() : Store::whereIn('id', auth()->user()->accessibleStoreIds())->get();
        return view('expenses.create', compact('expense', 'categories', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        // 🔒 Même vérification
        if (! $this->peutVoir($expense->store_id)) {
            abort(403);
        }

        // Même raison qu'à la création : on n'écrit que les champs validés.
        // L'auteur d'origine n'est pas modifié.
        $donnees = $request->validate([
            'reference' => 'required|string|max:255|unique:expenses,reference,' . $expense->id,
            'expense_categories_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'store_id' => 'required|exists:stores,id',
        ]);

        try {
            $expense->update($donnees);
            return redirect()->route('expenses.index')->with('success', 'Dépense mise à jour avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expenses.index')->with('error', 'Erreur lors de la mise à jour : ' . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        // 🔒 Même vérification
        if (! $this->peutVoir($expense->store_id)) {
            abort(403);
        }

        try {
            $expense->delete();
            return redirect()->route('expenses.index')->with('success', 'Dépense supprimée avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expenses.index')->with('error', 'Erreur lors de la suppression : ' . $th->getMessage());
        }
    }
}