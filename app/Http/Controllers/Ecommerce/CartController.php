<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\PricingTiers;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function add(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $cart = session()->get('cart', []);
        $requestedQty = max(1, (int) $request->input('quantity', 1));
        $basePrice = $product->effective_promo_price ?? $product->price;

        // Si le produit est déjà dans le panier, on augmente la quantité
        $newQuantity = isset($cart[$id]) ? $cart[$id]['quantity'] + $requestedQty : $requestedQty;

        $cart[$id] = [
            "product_id" => $product->id,
            "name" => $product->libelle,
            "quantity" => $newQuantity,
            "unite" => $product->unite,
            "price" => PricingTiers::unitPriceFor($basePrice, $newQuantity),
            "image" => $product->image
        ];

        session()->put('cart', $cart);

        if($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Produit ajouté au panier !',
                'count' => count($cart)
            ]);
        }

        return redirect()->back()->with('success', 'Produit ajouté au panier !');
    }

    public function show()
    {
        $cart = session()->get('cart', []);
        return view('ecommerce.cart', compact('cart'));
    }

    public function remove($id)
    {
        $cart = session()->get('cart', []);
        if(isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }
        return redirect()->back()->with('success', 'Produit retiré du panier.');
    }
}
