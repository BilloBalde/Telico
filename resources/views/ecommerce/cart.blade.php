@extends('layouts.shop-app')

@section('title', 'Mon panier')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Mon panier</h1>
    <p class="text-gray-600 text-sm mb-8">Gérez vos articles avant de commander</p>

    @php
        // Lignes du panier avec leur unité (carton, douzaine…). Paniers ouverts
        // avant l'ajout des unités : unité lue sur la fiche produit.
        $total = 0;
        $lignes = collect($cart)->map(function ($details, $id) use (&$total) {
            $unite = $details['unite'] ?? \App\Models\Product::whereKey($details['product_id'] ?? $id)->value('unite');
            $total += $details['price'] * $details['quantity'];

            return $details + [
                'id'         => $id,
                'mot_unite'  => \App\Models\Product::motUnite($unite),
                'quantite'   => \App\Models\Product::quantiteEnUnite($details['quantity'], $unite),
                'sous_total' => $details['price'] * $details['quantity'],
            ];
        });
    @endphp

    {{-- Téléphone : une carte par article, tout visible sans défilement horizontal. --}}
    <div class="sm:hidden space-y-3">
        @forelse($lignes as $ligne)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex gap-3">
                <div class="w-16 h-16 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                    <img src="{{ asset('products/' . $ligne['image']) }}" alt="" class="w-full h-full object-cover"
                         onerror="this.onerror=null;this.src='{{ asset('assets/img/produit-sans-photo.svg') }}'">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-sm font-medium text-gray-900">{{ $ligne['name'] }}</span>
                        <a href="{{ route('cart.remove', $ligne['id']) }}" class="inline-flex items-center justify-center w-9 h-9 -mt-1 -mr-1 rounded-lg text-red-600 hover:bg-red-50 transition shrink-0" title="Retirer" aria-label="Retirer {{ $ligne['name'] }}">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $ligne['quantite'] }} × {{ number_format($ligne['price'], 0, ',', ' ') }} GNF / {{ $ligne['mot_unite'] }}</p>
                    <p class="text-sm font-semibold text-gray-900 mt-1">{{ number_format($ligne['sous_total'], 0, ',', ' ') }} GNF</p>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-12 text-center text-gray-500">Votre panier est vide.</div>
        @endforelse
    </div>

    {{-- Tablette et ordinateur : tableau. --}}
    <div class="hidden sm:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qté</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lignes as $ligne)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        <img src="{{ asset('products/' . $ligne['image']) }}" alt="" class="w-full h-full object-cover"
                                             onerror="this.onerror=null;this.src='{{ asset('assets/img/produit-sans-photo.svg') }}'">
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $ligne['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-right whitespace-nowrap">{{ number_format($ligne['price'], 0, ',', ' ') }} GNF <span class="block text-xs text-gray-400">/ {{ $ligne['mot_unite'] }}</span></td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-right">{{ $ligne['quantite'] }}</td>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900 text-right whitespace-nowrap">{{ number_format($ligne['sous_total'], 0, ',', ' ') }} GNF</td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('cart.remove', $ligne['id']) }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-red-600 hover:bg-red-50 transition" title="Retirer">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-500">Votre panier est vide.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @php
        $continueUrl = Auth::check() && Auth::user()->isCustomer() ? route('shop.home') : route('accueil');
    @endphp

    @if(count($cart) > 0)
        <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <a href="{{ $continueUrl }}" class="inline-flex justify-center items-center px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition">
                Continuer mes achats
            </a>
            <div class="text-right">
                <p class="text-sm text-gray-600 mb-1">Total</p>
                <p class="text-2xl font-bold text-amber-600 mb-4">{{ number_format($total, 0, ',', ' ') }} GNF</p>
                @auth
                    @if(Auth::user()->isCustomer())
                        <a href="{{ route('checkout') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                            Passer à la caisse <i class="fas fa-arrow-right text-sm"></i>
                        </a>
                    @else
                        <p class="text-sm text-gray-500 max-w-xs ml-auto">Connectez-vous avec un compte client pour commander.</p>
                        <a href="{{ route('otp.login') }}" class="mt-2 inline-block text-amber-600 font-medium hover:underline">Espace client</a>
                    @endif
                @else
                    <a href="{{ route('otp.login') }}?redirect=checkout" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                        Se connecter pour commander
                    </a>
                @endauth
            </div>
        </div>
    @else
        <div class="text-center mt-10">
            <a href="{{ $continueUrl }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                Retour à la boutique
            </a>
        </div>
    @endif
</div>
@endsection
