<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;

use Illuminate\Http\Request;

class VisitController extends Controller
{
    /**
     * Page d'accueil vitrine (tous les visiteurs).
     */
    public function index()
    {
        return view('home', $this->buildHomePageData());
    }

    /**
     * Accueil catalogue pour les clients connectés (espace dédié).
     */
    public function shop()
    {
        return view('home', array_merge($this->buildHomePageData(), [
            'shopCustomerArea' => true,
        ]));
    }

    /**
     * Page catalogue publique — tous les produits.
     */
    public function indexProducts()
    {
        return view('catalogue', $this->buildHomePageData());
    }

    /**
     * Page publique — toutes les catégories.
     */
    public function publicCategories()
    {
        $categories = $this->groupedCategories();
        return view('visitor.categories', compact('categories'));
    }

    private function groupedCategories(): \Illuminate\Support\Collection
    {
        return Category::withCount('products')
            ->with(['products' => fn($q) => $q->whereNotNull('image')->select('products.id', 'products.image')])
            ->get()
            ->groupBy('category_type')
            ->map(function ($group, $type) {
                $firstProductImage = null;
                foreach ($group as $cat) {
                    $img = $cat->products->first()?->image;
                    if ($img) { $firstProductImage = $img; break; }
                }
                return (object)[
                    'name'           => $type,
                    'category_type'  => $type,
                    'products_count' => $group->sum('products_count'),
                    'image'          => $firstProductImage,
                    'image_url'      => $firstProductImage ? asset('products/' . $firstProductImage) : null,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    protected function buildHomePageData(): array
    {
        $getCategoryName = fn($p) => $p->categories->first()?->category_type ?? 'Sans catégorie';

        $mapProduct = function ($p) use ($getCategoryName) {
            // Vérifier si le produit est en promotion active
            $isPromo = $p->is_promo 
                && !is_null($p->promo_price)
                && (is_null($p->promo_start_date) || $p->promo_start_date <= now())
                && (is_null($p->promo_end_date) || $p->promo_end_date >= now());
            
            return [
                'id'            => $p->id,
                'name'          => $p->libelle ?? $p->name,
                'category_name' => $getCategoryName($p),
                'price'         => (float) ($isPromo ? $p->promo_price : $p->price),
                'old_price'     => $isPromo ? (float) $p->price : null,
                'discount'      => $isPromo ? round((1 - $p->promo_price / $p->price) * 100) : 0,
                'rating'        => (float) ($p->rating ?? 4.5),
                'image'         => $p->image ? asset('products/' . $p->image) : null,
                // Prix et quantité dans l'unité unique du produit : « / carton ».
                'unite'         => \App\Models\Product::motUnite($p->unite),
            ];
        };

        $mapPromo = function ($p) use ($getCategoryName) {
            return [
                'id'            => $p->id,
                'name'          => $p->libelle ?? $p->name,
                'category_name' => $getCategoryName($p),
                'price'         => (float) ($p->promo_price ?? $p->price),
                'old_price'     => (float) $p->price,
                'discount'      => $p->promo_price ? round((1 - $p->promo_price / $p->price) * 100) : 0,
                'rating'        => (float) ($p->rating ?? 4.5),
                'image'         => $p->image ? asset('products/' . $p->image) : null,
                // Prix et quantité dans l'unité unique du produit : « / carton ».
                'unite'         => \App\Models\Product::motUnite($p->unite),
            ];
        };

        // ... le reste de votre code ...

        $categories      = $this->groupedCategories();
            $totalCategories = $categories->count();
            $totalProducts   = Product::count();

            $stores = Store::with('manager')->get()->map(function ($s) {
                return (object)[
                    'id'          => $s->id,
                    'name'        => $s->store_name,
                    'address'     => $s->address,
                    'description' => $s->description,
                    'image_url'   => $s->store_picture ? asset('stores/' . $s->store_picture) : null,
                    'manager'     => $s->manager?->name,
                    'phone'       => $s->phone ?? $s->manager?->phone,
                ];
            })->values();

            // Meilleures ventes réelles (somme des quantités vendues, table sales)
            $bestSellerIds = \App\Models\Sale::select('product_id')
                ->selectRaw('SUM(quantity) as qty_sold')
                ->groupBy('product_id')
                ->orderByDesc('qty_sold')
                ->limit(10)
                ->pluck('product_id');

            $bestSellers = Product::with('categories')
                ->whereIn('id', $bestSellerIds)
                ->get()
                ->sortBy(fn ($p) => $bestSellerIds->search($p->id))
                ->map($mapProduct)
                ->values();

            // Tendances : meilleures ventes des 30 derniers jours, fallback nouveautés
            $trendingIds = \App\Models\Sale::select('product_id')
                ->selectRaw('SUM(quantity) as qty_sold')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('product_id')
                ->orderByDesc('qty_sold')
                ->limit(10)
                ->pluck('product_id');

            $trending = $trendingIds->isNotEmpty()
                ? Product::with('categories')
                    ->whereIn('id', $trendingIds)
                    ->get()
                    ->sortBy(fn ($p) => $trendingIds->search($p->id))
                    ->map($mapProduct)
                    ->values()
                : Product::with('categories')->latest()->limit(10)->get()->map($mapProduct)->values();


        return [
            'categories'      => $categories,
            'totalCategories' => $totalCategories,
            'totalProducts'   => $totalProducts,
            'stores'          => $stores,
            'allProducts'     => Product::with('categories')->latest()->get()->map($mapProduct)->values(),
            'bestProducts'    => Product::with('categories')->orderByDesc('rating')->get()->map($mapProduct)->values(),
            'promoProducts'   => Product::with('categories')
                ->where('is_promo', true)
                ->whereNotNull('promo_price')
                ->where(fn ($q) => $q->whereNull('promo_start_date')->orWhereDate('promo_start_date', '<=', now()))
                ->where(fn ($q) => $q->whereNull('promo_end_date')->orWhereDate('promo_end_date', '>=', now()))
                ->get()->map($mapPromo)->values(),
            'bestSellers'  => $bestSellers,
            'topRated'     => Product::with('categories')->orderByDesc('rating')->limit(10)->get()->map($mapProduct)->values(),
            'trending'     => $trending,
        ];
    }

    public function showProduct($id)
    {
        $product = \App\Models\Product::with('categories')->findOrFail($id);
        $related = \App\Models\Product::with('categories')
            ->whereHas('categories', function ($q) use ($product) {
                $q->whereIn('categories.id', $product->categories->pluck('id'));
            })
            ->where('id', '!=', $id)
            ->take(4)
            ->get();

        return view('visitor.productDetail', compact('product', 'related'));
    }

    public function loadMoreProducts(Request $request)
    {
        $offset = $request->input('offset', 0);
        $products = Product::orderBy('created_at', 'desc')->paginate(12);
        $html = view('visitor.partials.product_cards', compact('products'))->render();

        return response()->json([
            'html' => $html,
            'count' => $products->count(),
        ]);
    }

}





