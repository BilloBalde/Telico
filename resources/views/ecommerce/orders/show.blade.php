@extends('layouts.shop-app')

@section('title', 'Commande #'.$order->id)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Fil d'Ariane --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('accueil') }}" class="hover:text-amber-600 transition">Accueil</a>
            <span>/</span>
            <a href="{{ route('orders.index') }}" class="hover:text-amber-600 transition">Mes commandes</a>
            <span>/</span>
            <span class="text-gray-800 font-medium">Commande #{{ $order->id }}</span>
        </nav>
    </div>

    {{-- En-tête --}}
    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Commande #{{ $order->id }}</h1>
            <p class="text-gray-500 text-sm mt-1">
                Passée le {{ $order->created_at->translatedFormat('d F Y à H:i') }}
            </p>
        </div>
        <div class="flex gap-3">
            
            <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour aux commandes
            </a>
        </div>
    </div>

    {{-- Grille principale --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Colonne de gauche : résumé commande + produits --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Carte statut & paiement --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Suivi de commande
                    </h2>
                </div>
                <div class="p-5">
                    {{-- Statut visuel --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Statut actuel</span>
                            @php
                                $statusMap = [
                                    'pending'    => ['label' => 'En attente',  'class' => 'bg-yellow-100 text-yellow-800'],
                                    'approved'   => ['label' => 'Confirmée',   'class' => 'bg-blue-100 text-blue-800'],
                                    'processing' => ['label' => 'En cours',    'class' => 'bg-indigo-100 text-indigo-800'],
                                    'delivering' => ['label' => 'En livraison','class' => 'bg-indigo-100 text-indigo-800'],
                                    'completed'  => ['label' => 'Livrée',      'class' => 'bg-green-100 text-green-800'],
                                    'delivered'  => ['label' => 'Livrée',      'class' => 'bg-green-100 text-green-800'],
                                    'rejected'   => ['label' => 'Refusée',     'class' => 'bg-red-100 text-red-800'],
                                    'cancelled'  => ['label' => 'Annulée',     'class' => 'bg-gray-100 text-gray-800'],
                                ];
                                $s = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'bg-gray-100 text-gray-700'];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $s['class'] }}">{{ $s['label'] }}</span>
                        </div>
                        {{-- Barre de progression --}}
                        @php
                            $progress = match($order->status) {
                                'pending'    => 15,
                                'approved'   => 40,
                                'processing' => 65,
                                'delivering' => 65,
                                'completed'  => 100,
                                'delivered'  => 100,
                                default      => 0,
                            };
                        @endphp
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-amber-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>Commandée</span>
                            <span>Confirmée</span>
                            <span>En cours</span>
                            <span>Livrée</span>
                        </div>
                    </div>

                    {{-- Paiement --}}
                    @php
                        $methodLabel = match($order->payment_method) {
                            'cod'          => 'Paiement à la livraison',
                            'orange_money' => 'Orange Money',
                            default        => strtoupper($order->payment_method),
                        };
                        // Priorité au statut de la facture si elle existe
                        if ($order->facture) {
                            $payStatut = match($order->facture->statut) {
                                'payé'    => ['label' => '✓ Payé',         'class' => 'text-emerald-600'],
                                'partiel' => ['label' => '⏳ Partiel',      'class' => 'text-blue-600'],
                                default   => ['label' => '⏳ En attente',   'class' => 'text-amber-600'],
                            };
                        } else {
                            $payStatut = $order->payment_status === 'paid'
                                ? ['label' => '✓ Payé',       'class' => 'text-emerald-600']
                                : ['label' => '⏳ En attente', 'class' => 'text-amber-600'];
                        }
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <div class="flex items-center gap-2 text-gray-600 text-sm mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Mode de paiement
                            </div>
                            <p class="font-medium text-gray-800">{{ $methodLabel }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <div class="flex items-center gap-2 text-gray-600 text-sm mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0v8m2-8v8M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                                Statut paiement
                            </div>
                            <p class="font-medium {{ $payStatut['class'] }}">{{ $payStatut['label'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tableau des produits --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Articles commandés
                    </h2>
                </div>
                {{-- Téléphone : une ligne par article, quantité et prix dans l'unité du produit. --}}
                <ul class="sm:hidden divide-y divide-gray-100">
                    @foreach($order->items as $item)
                    <li class="px-5 py-4">
                        <div class="flex justify-between gap-3">
                            <span class="text-sm text-gray-900 font-medium">{{ $item->product->libelle ?? 'Produit #'.$item->product_id }}</span>
                            <span class="text-sm text-gray-900 font-semibold whitespace-nowrap">{{ number_format($item->quantity * $item->price, 0, ',', ' ') }} GNF</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $item->quantite_affichee }} × {{ number_format($item->price, 0, ',', ' ') }} GNF / {{ $item->mot_unite }}</p>
                    </li>
                    @endforeach
                </ul>
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qté</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Prix par unité</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($order->items as $item)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-sm text-gray-900 font-medium">
                                    {{ $item->product->libelle ?? 'Produit #'.$item->product_id }}
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right">{{ $item->quantite_affichee }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700 text-right">{{ number_format($item->price, 0, ',', ' ') }} GNF <span class="text-xs text-gray-400">/ {{ $item->mot_unite }}</span></td>
                                <td class="px-5 py-4 text-sm text-gray-900 font-semibold text-right">{{ number_format($item->quantity * $item->price, 0, ',', ' ') }} GNF</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Total général</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($order->total_amount, 0, ',', ' ') }} <span class="text-sm font-normal">GNF</span></div>
                    </div>
                </div>
            </div>
            {{-- Facture & Paiement (visible après confirmation manager) --}}
            @if($order->facture)
            @php $facture = $order->facture; @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Facture & Paiement
                    </h2>
                </div>
                <div class="p-5 space-y-4">
                    {{-- Numéro de facture --}}
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-500">N° Facture</span>
                        <span class="font-mono font-semibold text-gray-800 bg-gray-100 px-2 py-1 rounded">{{ $facture->numero_facture }}</span>
                    </div>

                    {{-- Montant total --}}
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-500">Montant total</span>
                        <span class="font-semibold text-gray-800">{{ number_format($facture->montant_total, 0, ',', ' ') }} GNF</span>
                    </div>

                    {{-- Montant payé --}}
                    @php $totalPaye = $facture->paiements->sum('versement'); @endphp
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-500">Montant payé</span>
                        <span class="font-semibold text-emerald-600">{{ number_format($totalPaye, 0, ',', ' ') }} GNF</span>
                    </div>

                    {{-- Reste à payer --}}
                    @if($facture->reste > 0)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-500">Reste à payer</span>
                        <span class="font-semibold text-amber-600">{{ number_format($facture->reste, 0, ',', ' ') }} GNF</span>
                    </div>
                    @endif

                    {{-- Statut paiement --}}
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-500">Statut paiement</span>
                        @php
                            $pStatut = match($facture->statut) {
                                'payé'    => ['label' => 'Payé',     'class' => 'bg-green-100 text-green-700'],
                                'partiel' => ['label' => 'Partiel',  'class' => 'bg-amber-100 text-amber-700'],
                                default   => ['label' => 'Non payé', 'class' => 'bg-red-100 text-red-700'],
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $pStatut['class'] }}">{{ $pStatut['label'] }}</span>
                    </div>

                    {{-- Statut livraison --}}
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-500">Livraison</span>
                        @if($facture->livraison === 'livré')
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Livrée</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">En attente de livraison</span>
                        @endif
                    </div>

                    {{-- Historique paiements --}}
                    @if($facture->paiements && $facture->paiements->count() > 0)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Historique des versements</p>
                        <div class="space-y-2">
                            @foreach($facture->paiements as $paiement)
                            <div class="flex justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
                                <span class="text-gray-500">{{ $paiement->created_at->format('d/m/Y') }} · {{ ucfirst($paiement->paid_by) }}</span>
                                <span class="font-medium text-gray-800">{{ number_format($paiement->versement, 0, ',', ' ') }} GNF</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Colonne de droite : infos livraison + support --}}
        <div class="space-y-6">
            @if($order->address)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Adresse de livraison
                    </h2>
                </div>
                <div class="p-5">
                    <address class="not-italic text-gray-700 text-sm leading-relaxed">
                        {{ $order->address->full_address ?? $order->address->street ?? '' }}<br>
                        @if($order->address->city) {{ $order->address->city }}<br>@endif
                        @if($order->address->postal_code) Code postal : {{ $order->address->postal_code }}<br>@endif
                        @if($order->address->country) {{ $order->address->country }}@endif
                    </address>
                </div>
            </div>
            @endif

            {{-- Bloc aide --}}
            <div class="bg-amber-50 rounded-2xl border border-amber-100 p-5">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636L9.172 14.828l-3.536-3.536m0 0L2.1 14.928l4.243 4.243L21.192 5.636l-3.536-3.536L9.172 14.828z"/></svg>
                    <div>
                        <h3 class="font-semibold text-gray-800">Besoin d'aide ?</h3>
                        <p class="text-sm text-gray-600 mt-1">Contactez notre service client via <a href="mailto:{{ \App\Models\Company::current()?->email }}" class="text-amber-700 underline">{{ \App\Models\Company::current()?->email }}</a> ou appelez le <strong>{{ \App\Models\Company::current()?->phone }}</strong>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection