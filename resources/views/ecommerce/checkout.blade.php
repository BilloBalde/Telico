@extends('layouts.shop-app')

@section('title', 'Validation de la commande')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Validation de la commande</h1>
    <p class="text-gray-600 text-sm mb-8">Choisissez votre adresse et le mode de paiement</p>

    <form action="{{ route('orders.store') }}" method="POST" class="space-y-8" enctype="multipart/form-data">
        @csrf
        @if($isBuyNow ?? false)
            <input type="hidden" name="is_buy_now" value="1">
        @endif
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <!-- Adresse -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Adresse de livraison</h2>
                        <a href="{{ route('addresses.create') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-amber-600 hover:text-amber-700">
                            <i class="fas fa-plus"></i> Nouvelle adresse
                        </a>
                    </div>
                    @if($addresses->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($addresses as $address)
                                <label for="address{{ $address->id }}" class="block cursor-pointer">
                                    <input class="peer sr-only" type="radio" name="delivery_address_id"
                                           id="address{{ $address->id }}" value="{{ $address->id }}"
                                           {{ $loop->first ? 'checked' : '' }} required>
                                    <span class="flex flex-col rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-amber-500 peer-checked:bg-amber-50/60 hover:border-amber-200">
                                        <span class="font-semibold text-gray-900">{{ $address->city }}</span>
                                        <span class="text-sm text-gray-600 mt-1">{{ $address->full_address }}</span>
                                        <span class="text-xs text-gray-500 mt-1">Tél. {{ $address->phone }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle mt-0.5"></i>
                            <span>Ajoutez une adresse de livraison pour continuer.</span>
                        </div>
                    @endif
                </div>

                <!-- Mode de paiement -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Mode de paiement</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label for="pay_cod" class="block cursor-pointer">
                            <input class="peer sr-only" type="radio" name="payment_method" id="pay_cod" value="cod" checked>
                            <span class="block rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-amber-500 peer-checked:bg-amber-50/60 hover:border-amber-200">
                                <span class="block font-semibold text-gray-900">Paiement à la livraison</span>
                                <span class="block text-sm text-gray-600 mt-1">Payez en espèces à la réception</span>
                            </span>
                        </label>
                        <label for="pay_om" class="block cursor-pointer">
                            <input class="peer sr-only" type="radio" name="payment_method" id="pay_om" value="orange_money">
                            <span class="block rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-orange-500 peer-checked:bg-orange-50/60 hover:border-orange-200">
                                <span class="flex items-center gap-2 font-semibold text-gray-900">
                                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-orange-500 text-white text-xs font-bold">OM</span>
                                    Orange Money
                                </span>
                                <span class="block text-sm text-gray-600 mt-1">Paiement sécurisé via Orange Money</span>
                            </span>
                        </label>
                    </div>

                    {{-- Info Orange Money affichée quand Orange Money est sélectionné --}}
                    <div id="om-info" class="hidden mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-start gap-3 bg-orange-50 border border-orange-200 rounded-xl p-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-full bg-orange-500 text-white font-bold text-sm">OM</span>
                            <div>
                                <p class="text-sm font-semibold text-orange-800">Paiement Orange Money sécurisé</p>
                                <p class="text-xs text-orange-700 mt-1 leading-relaxed">
                                    Après confirmation, vous serez redirigé vers la page de paiement Orange Money
                                    pour finaliser votre achat en GNF.
                                </p>
                                <div class="flex items-center gap-1 mt-2">
                                    <i class="fas fa-shield-alt text-green-500 text-xs"></i>
                                    <span class="text-xs text-gray-500">Paiement chiffré et sécurisé</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Récapitulatif -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sticky top-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Récapitulatif</h2>
                    <ul class="divide-y divide-gray-100 mb-4 max-h-64 overflow-y-auto">
                        @foreach($products as $product)
                            @php
                                $qty = 0;
                                foreach($cartItems as $item) {
                                    if($item['product_id'] == $product->id) $qty = $item['quantity'];
                                }
                            @endphp
                            <li class="py-3 flex justify-between gap-2 text-sm">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $product->libelle }}</span>
                                    <span class="block text-xs text-gray-500">Qté {{ $product->quantiteAvecUnite($qty) }}</span>
                                </div>
                                <span class="text-gray-800 whitespace-nowrap">{{ number_format($product->price * $qty, 0, ',', ' ') }} GNF</span>
                                <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                                <input type="hidden" name="items[{{ $loop->index }}][quantity]" value="{{ $qty }}">
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-gray-200 pt-4 space-y-2">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Sous-total</span>
                            <span>{{ number_format($total, 0, ',', ' ') }} GNF</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-gray-900 pt-2">
                            <span>Total</span>
                            <span class="text-amber-600">{{ number_format($total, 0, ',', ' ') }} GNF</span>
                        </div>
                    </div>
                    <button type="submit" id="submit-btn"
                            class="mt-6 w-full inline-flex justify-center items-center gap-2 py-3 px-4 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ $addresses->count() == 0 ? 'disabled' : '' }}>
                        <i class="fas fa-check-circle" id="btn-icon-cod"></i>
                        <i class="fas fa-mobile-alt hidden" id="btn-icon-om"></i>
                        <span id="btn-label">Confirmer la commande</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radioCod   = document.getElementById('pay_cod');
        const radioOm    = document.getElementById('pay_om');
        const omInfo     = document.getElementById('om-info');
        const btnLabel   = document.getElementById('btn-label');
        const btnIconCod = document.getElementById('btn-icon-cod');
        const btnIconOm  = document.getElementById('btn-icon-om');
        const submitBtn  = document.getElementById('submit-btn');
        const form       = submitBtn.closest('form');

        function updateUI() {
            if (radioOm.checked) {
                omInfo.classList.remove('hidden');
                submitBtn.classList.remove('from-amber-500', 'to-amber-600');
                submitBtn.classList.add('from-orange-500', 'to-orange-600');
                btnLabel.textContent = 'Payer avec Orange Money';
                btnIconCod.classList.add('hidden');
                btnIconOm.classList.remove('hidden');
            } else {
                omInfo.classList.add('hidden');
                submitBtn.classList.add('from-amber-500', 'to-amber-600');
                submitBtn.classList.remove('from-orange-500', 'to-orange-600');
                btnLabel.textContent = 'Confirmer la commande';
                btnIconCod.classList.remove('hidden');
                btnIconOm.classList.add('hidden');
            }
        }

        // Désactiver le bouton pendant la soumission pour éviter les doubles clics
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            btnLabel.textContent = radioOm.checked ? 'Redirection vers Orange Money…' : 'Traitement en cours…';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        });

        radioCod.addEventListener('change', updateUI);
        radioOm.addEventListener('change', updateUI);

        updateUI();
    });
</script>
@endsection