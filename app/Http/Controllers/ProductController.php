<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Route::resource('produits', ...) n'appliquait aucune permission : tout
        // rôle connecté pouvait créer/modifier/supprimer des produits, alors que
        // le preset PermissionSeeder réserve products.create/.edit/.delete à
        // admin/manager (vendeur et achats n'ont que products.view).
        $this->middleware('permission:products.view')->only(['index', 'show']);
        $this->middleware('permission:products.create')->only(['create', 'store']);
        $this->middleware('permission:products.edit')->only(['edit', 'update']);
        $this->middleware('permission:products.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $categories = Category::all();

        $query = Product::query();

        if ($request->filled('libelle')) {
            $query->where('libelle', 'like', '%' . $request->input('libelle') . '%');
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        $connectedUser = Auth::user()->role_id;

        if (Auth::user()->isStoreScoped()) {
            $userStoreId = Auth::user()->assignedStoreId();
            $query->whereHas('stores', function ($q) use ($userStoreId) {
                $q->where('store_id', $userStoreId);
            });
        }
        $allProducts = $query->with(['categories', 'stores'])->get();

        $userStoreId = Auth::user()->isStoreScoped()
            ? Auth::user()->assignedStoreId()
            : null;

        return view('products.index', compact('allProducts', 'categories', 'userStoreId'))
            ->with('libelle', $request->input('libelle'))
            ->with('category_id', $request->input('category_id'));
    }

    public function fetchProductDetails(Request $request)
    {
        $productName = $request->query('productName');
        // Libellé exact d'abord : c'est ce produit-là que la facture utilisera.
        $product = Product::with('category')->where('libelle', $productName)->first()
            ?? ($request->boolean('exact') ? null : Product::with('category')->where('libelle', 'like', '%' . $productName . '%')->first());

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'libelle' => $product->libelle,
                    'unite' => $product->unite_libelle,
                    'price' => (float) $product->price,
                    // Plancher de vente (prix minimum ou prix de revient), pour le signaler à la saisie.
                    'plancher' => $product->plancherDeVente()[0],
                    'plancher_origine' => $product->plancherDeVente()[1] === 'minimum' ? 'prix de vente minimum' : 'prix de revient',
                    'image' => $product->image,
                    'category' => [
                        'id' => $product->category?->id,
                        'name' => $product->category?->slug
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }

    public function fetchProductDetailsSuggestion(Request $request)
    {
        $productName = $request->query('productName');

        if ($request->boolean('suggestions')) {
            $suggestions = Product::where('libelle', 'like', '%' . $productName . '%')
                ->limit(10)
                ->get(['id', 'libelle', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau'])
                ->map(fn ($p) => ['id' => $p->id, 'libelle' => $p->libelle, 'unite' => $p->unite_libelle]);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        }

        $product = Product::where('libelle', $productName)->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }

    public function create()
    {
        $categories = Category::all();
        $stores = Store::all();
        return view('products.create', compact('categories', 'stores'));
    }

    /**
     * Unité unique du produit, commune à la création et à la modification :
     * le nombre de pièces est obligatoire pour un carton ou un paquet, la
     * dimension pour un rouleau.
     */
    private function reglesUnites(): array
    {
        $si = fn (string $code) => \Illuminate\Validation\Rule::requiredIf(fn () => request()->input('unite') === $code);

        return [
            'unite'             => 'required|in:'.implode(',', array_keys(Product::UNITES)),
            'pcs_par_carton'    => ['nullable', $si('carton'), 'integer', 'min:2'],
            'pcs_par_paquet'    => ['nullable', $si('paquet'), 'integer', 'min:2'],
            'dimension_rouleau' => ['nullable', $si('rouleau'), 'string', 'max:60'],
        ];
    }

    private function messagesUnites(): array
    {
        return [
            'unite.required'             => "Choisissez l'unité du produit.",
            'unite.in'                   => 'Unité inconnue.',
            'pcs_par_carton.required'    => 'Indiquez le nombre de pièces par carton.',
            'pcs_par_carton.min'         => 'Un carton contient au moins 2 pièces.',
            'pcs_par_paquet.required'    => 'Indiquez le nombre de pièces par paquet.',
            'pcs_par_paquet.min'         => 'Un paquet contient au moins 2 pièces.',
            'dimension_rouleau.required' => 'Indiquez la dimension du rouleau.',
        ];
    }

    /** Valeurs à enregistrer : seul le détail de l'unité choisie est gardé. */
    private function valeursUnites(Request $request): array
    {
        $unite = $request->input('unite', 'pcs');

        return [
            'unite'             => $unite,
            'pcs_par_carton'    => $unite === 'carton' ? (int) $request->pcs_par_carton : null,
            'pcs_par_paquet'    => $unite === 'paquet' ? (int) $request->pcs_par_paquet : null,
            'dimension_rouleau' => $unite === 'rouleau' ? trim((string) $request->dimension_rouleau) : null,
        ];
    }

    /**
     * Prix de vente minimum (facultatif, par unité du produit) : strictement
     * au-dessus du prix de revient et au plus égal au prix de vente, sinon le
     * prix proposé à la caisse serait lui-même refusé.
     */
    private function regleMinimum(Request $request, float $prixVente): array
    {
        return ['nullable', 'numeric', 'min:0', function ($attr, $valeur, $echec) use ($request, $prixVente) {
            if ($valeur === null || $valeur === '') {
                return;
            }
            $fg = fn ($n) => number_format((float) $n, 0, ',', ' ');
            $revient = (float) $request->input('price_sale');
            if ($revient > 0 && (float) $valeur <= $revient) {
                $echec('Le prix de vente minimum doit être strictement supérieur au prix de revient (' . $fg($revient) . ' FG).');
            } elseif ($prixVente > 0 && (float) $valeur > $prixVente) {
                $echec('Le prix de vente minimum ne peut pas dépasser le prix de vente (' . $fg($prixVente) . ' FG).');
            }
        }];
    }

    private function valeurMinimum(Request $request): ?float
    {
        return $request->filled('price_sale_minimum') ? round((float) $request->input('price_sale_minimum'), 2) : null;
    }

    public function validation(Request $request)
    {
        return $request->validate([
            ...$this->reglesUnites(),
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'libelle' => 'required|string|max:225',
            'sku' => 'required|string|max:225',
            'description' => 'required|string',
            'price_sale' => 'required|numeric|min:0',
            'price_sale_minimum' => $this->regleMinimum($request, round((float) $request->input('price_sale') * 1.3, 2)),
            'stock_initial' => 'required|integer|min:0',
            'store_id' => 'required|exists:stores,id',
            'image' => 'image',
            'is_promo' => 'nullable|boolean',
            'promo_percent' => 'nullable|numeric|min:0|max:100',
            'promo_start_date' => 'nullable|date',
            'promo_end_date' => 'nullable|date|after_or_equal:promo_start_date',
        ], [
            'categories.required' => 'veuillez selectionner la categorie',
            'categories.array' => 'Selectionner plusieurs categories',
            'libelle.required' => 'champ libelle doit être rempli',
            'libelle.string' => 'champ libelle prend au maximum 225 caractères',
            'sku.required' => 'champ sku doit être rempli',
            'sku.string' => 'champ sku prend au maximum 225 caractères',
            'description.required' => 'champ description doit être rempli avec une chaine de caractere',
            'price_sale.required' => 'champ Prix de Revient doit être rempli avec une valeure numérique',
            'price_sale.numeric' => 'champ Prix de Revient doit être rempli avec une valeure numérique',
            'stock_initial.required' => 'champ Stock initial doit être rempli',
            'stock_initial.integer' => 'champ Stock initial doit être un nombre entier',
            'stock_initial.min' => 'champ Stock initial doit être positif',
            'store_id.required' => 'champ Magasin doit être rempli',
            'store_id.exists' => 'Magasin sélectionné invalide',
            'image.image' => 'champ image ne prend que des images',
            'promo_percent.max' => 'Le pourcentage de promotion ne peut pas dépasser 100.',
            'promo_end_date.after_or_equal' => 'La date de fin de promo doit être après la date de début.',
            ...$this->messagesUnites(),
        ]);
    }

    /**
     * Calcule le prix promo à partir du prix de vente et du pourcentage saisi.
     */
    private function computePromoPrice(Request $request, float $price): ?float
    {
        if (!$request->boolean('is_promo') || !$request->filled('promo_percent')) {
            return null;
        }

        return round($price * (1 - ((float) $request->promo_percent / 100)), 2);
    }

    /**
     * Création rapide d'un produit en AJAX (ex : depuis le formulaire Achat, pour ne pas
     * obliger l'utilisateur à quitter la page en cours). Pas de prix ici : le prix de
     * revient n'est connu qu'une fois l'achat complet soumis (transport/douane/frais
     * divers répartis) — c'est PurchaseOrderCostingService qui le renseignera, avec le
     * prix de vente calculé automatiquement à ce moment-là (cf. costOrder()). Pas
     * d'image ni de stock initial non plus — le stock est ajouté par la ligne d'achat
     * qui suit la création. La fiche complète reste modifiable ensuite depuis Produit
     * List.
     */
    public function quickStore(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:225',
            'sku' => 'required|string|max:225',
            'category_id' => 'required|exists:categories,id',
            // Unité unique, mêmes règles que la fiche produit (pièces par
            // carton ou par paquet, dimension du rouleau).
            ...$this->reglesUnites(),
        ], [
            'libelle.required' => 'Le nom du produit est requis.',
            'sku.required' => 'Le SKU est requis.',
            'category_id.required' => 'La catégorie est requise.',
            ...$this->messagesUnites(),
        ]);

        try {
            $product = Product::create([
                'libelle' => $request->libelle,
                'sku' => $request->sku,
                'description' => $request->libelle,
                ...$this->valeursUnites($request),
            ]);

            $product->categories()->attach($request->category_id);

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id, 'libelle' => $product->libelle, 'sku' => $product->sku,
                    'unite' => $product->unite, 'unite_libelle' => $product->unite_libelle,
                    'unite_pluriel' => Product::motUnite($product->unite, 2),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $this->validation($request);
        if (request()->hasfile('image')) {
            $productName = time() . '.' . request()->image->getClientOriginalExtension();
            request()->image->move(public_path('products'), $productName);
        }
        try {
            // Prix de vente = prix de revient + 30% de marge, calculé ici (jamais fait
            // confiance au champ "price" du formulaire, en lecture seule côté client)
            // pour garantir la cohérence même si le JS est désactivé ou contourné.
            $priceSale = (float) $request->price_sale;
            $price = round($priceSale * 1.3, 2);

            $product = Product::create([
                'libelle' => $request->libelle,
                'sku' => $request->sku,
                'description' => $request->description,
                'pcs' => $request->stock_initial, // stock global e-commerce, initialisé au stock du premier magasin
                'price' => $price,
                'price_sale' => $priceSale,
                'price_sale_minimum' => $this->valeurMinimum($request),
                // Sans upload, on stocke une valeur vide : les vues basculent alors
                // sur leur visuel de repli. Pointer vers un "ib profile.jpg" qui
                // n'existe pas produisait une image cassée sur chaque fiche.
                'image' => $productName ?? '',
                'is_promo' => $request->boolean('is_promo'),
                'promo_percent' => $request->promo_percent ?? null,
                'promo_price' => $this->computePromoPrice($request, $price),
                'promo_start_date' => $request->promo_start_date ?? null,
                'promo_end_date' => $request->promo_end_date ?? null,
                ...$this->valeursUnites($request),
            ]);

            $product->categories()->attach($request->categories);

            // Ajout du stock dans le magasin sélectionné
            $storeId = $request->store_id;
            // Stock initial, tracé dans le journal comme premier mouvement.
            // products.pcs a déjà été renseigné ci-dessus : on le remet à zéro
            // pour que l'incrément du service ne le compte pas deux fois.
            $product->forceFill(['pcs' => 0])->save();
            app(\App\Services\StockService::class)->increment((int) $storeId, (int) $product->id, (int) $request->stock_initial, [
                'type'        => \App\Models\StockMovement::INITIAL,
                'source_type' => 'product',
                'source_id'   => $product->id,
                'reason'      => 'Stock initial saisi à la création du produit',
            ]);
            // Stock initial nul : la ligne existe quand même, le produit reste
            // rattaché au magasin choisi (comme avant).
            StoreProduct::firstOrCreate(['store_id' => $storeId, 'product_id' => $product->id], ['quantity' => 0]);

            return redirect()->route('produits.index')->with('success', 'Produit crée avec succès.');
        } catch (\Exception $e) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $product = Product::with('categories')->findOrFail($id);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'libelle' => $product->libelle,
                'sku' => $product->sku,
                'description' => $product->description,
                'price' => $product->price,
                'price_sale' => $product->price_sale,
                'image' => $product->image,
                'category_name' => $product->categories->pluck('slug')->implode(', ')
            ]
        ]);
    }

    public function edit($id)
    {
        $product = Product::with(['categories', 'stores', 'latestLigneCommande'])->findOrFail($id);
        // ✅ SUPPRIMÉ: Category::where('tenant_id', Auth::user()->tenant_id)
        $categories = Category::all();
        $stores = Store::select('id', 'store_name')->get();

        $user = Auth::user();
        $userStoreId = ($user && $user->isStoreScoped())
            ? $user->assignedStoreId()
            : null;

        $perStoreQuantities = $product->stores
            ->mapWithKeys(fn ($s) => [$s->id => (int) ($s->pivot->quantity ?? 0)])
            ->toArray();

        $totalQty = array_sum($perStoreQuantities);

        $quantityForUser = $userStoreId
            ? (int) ($perStoreQuantities[$userStoreId] ?? 0)
            : 0;

        return view('products.edit', compact(
            'product',
            'categories',
            'stores',
            'userStoreId',
            'perStoreQuantities',
            'totalQty',
            'quantityForUser'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle'      => 'required|string|max:255',
            'sku'          => 'required|string|max:255|unique:products,sku,' . $id,
            'categories'   => 'required|array',
            'categories.*' => 'exists:categories,id',
            'description'  => 'required|string',
            'price'        => 'required|numeric',
            'stock_restant' => 'required|integer|min:0',
            'price_sale'   => 'nullable|numeric',
            'price_sale_minimum' => $this->regleMinimum($request, (float) $request->input('price')),
            'image'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'store_id' => 'required|exists:stores,id',
            'is_promo' => 'nullable|boolean',
            'promo_percent' => 'nullable|numeric|min:0|max:100',
            'promo_start_date' => 'nullable|date',
            'promo_end_date' => 'nullable|date|after_or_equal:promo_start_date',
            ...$this->reglesUnites(),
        ], [
            'promo_percent.max' => 'Le pourcentage de promotion ne peut pas dépasser 100.',
            'promo_end_date.after_or_equal' => 'La date de fin de promo doit être après la date de début.',
            ...$this->messagesUnites(),
        ]);

        try {
            // ✅ SUPPRIMÉ: withoutGlobalScope('tenant') - plus besoin
            $product = Product::findOrFail($id);
            $storeId = $request->input('store_id');

            $product->fill($request->only([
                'libelle', 'sku', 'description', 'price', 'price_sale',
            ]));
            $product->price_sale_minimum = $this->valeurMinimum($request);

            $product->is_promo = $request->boolean('is_promo');
            $product->promo_percent = $request->promo_percent ?? null;
            $product->promo_price = $this->computePromoPrice($request, (float) $request->price);
            $product->promo_start_date = $request->promo_start_date ?? null;
            $product->promo_end_date = $request->promo_end_date ?? null;
            $product->fill($this->valeursUnites($request));

            $product->save();

            if ($request->hasFile('image')) {
                $productName = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(public_path('products'), $productName);
                $product->update(['image' => $productName]);
            }

            // ✅ SUPPRIMÉ: tenant_id dans sync
            $product->categories()->sync($request->categories);

            // Le stock saisi sur la fiche produit n'est plus écrasé en silence :
            // l'écart avec le stock enregistré devient un mouvement d'ajustement
            // tracé (auteur, date, avant/après), dans le périmètre de l'utilisateur.
            $this->authorizeStore($storeId);
            app(\App\Services\StockService::class)->adjustTo((int) $storeId, (int) $id, (int) $request->input('stock_restant'), [
                'type'        => \App\Models\StockMovement::ADJUSTMENT,
                'source_type' => 'product',
                'source_id'   => (int) $id,
                'reason'      => 'Stock modifié depuis la fiche produit',
            ]);

            // Garde products.pcs (stock global e-commerce) en phase avec la somme des stocks par magasin
            $product->pcs = $product->stores()->sum('store_products.quantity');
            $product->save();

            return redirect()
                ->route('produits.index')
                ->with('success', 'Produit mis à jour avec succès!');

        } catch (\Exception $e) {
            return back()->with('fall', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Product::find($id)->delete();
            return redirect()->back()->with('success', 'Produit supprimé avec succès.');
        } catch (\Illuminate\Database\QueryException $th) {
            if ($th->getCode() == "23000") {
                return back()->with(
                    'error',
                    "Impossible de supprimer ce produit : il est déjà utilisé dans d'autres enregistrements."
                );
            }
            return redirect()->back()->with('error', 'Produit pas supprimé. Voici l\'erreur' . $th->getMessage());
        }
    }
}
