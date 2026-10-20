<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement réussi — TELICO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-100 flex items-center justify-center p-4">

    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-10 text-center">

        {{-- Icône succès animée --}}
        <div class="flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mx-auto mb-6 animate-bounce">
            <i class="fas fa-check-circle text-green-500 text-5xl"></i>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Paiement réussi !</h1>
        <p class="text-gray-500 mb-6 text-sm leading-relaxed">
            Votre paiement Orange Money a été traité avec succès.<br>
            Notre équipe va valider votre commande sous peu.
        </p>

        @if($order)
        <div class="bg-gray-50 rounded-2xl p-5 mb-6 text-left">
            <div class="flex justify-between items-center mb-3">
                <span class="text-gray-500 text-sm">N° Commande</span>
                <span class="font-bold text-gray-800">#{{ $order->id }}</span>
            </div>
            <div class="flex justify-between items-center mb-3">
                <span class="text-gray-500 text-sm">Montant payé</span>
                <span class="font-bold text-green-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-500 text-sm">Statut</span>
                <span class="bg-yellow-100 text-yellow-700 text-xs font-semibold px-3 py-1 rounded-full">
                    En cours de validation
                </span>
            </div>

            @if($order->items->count())
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-gray-500 text-xs mb-2">Articles commandés :</p>
                @foreach($order->items as $item)
                <div class="flex justify-between text-sm py-1">
                    <span class="text-gray-700">{{ $item->product?->libelle ?? 'Article #'.$item->product_id }}</span>
                    <span class="text-gray-500">× {{ $item->quantite_affichee }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Note importante --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-left">
            <div class="flex gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <p class="text-blue-700 text-xs leading-relaxed">
                    Votre commande sera traitée dès confirmation du paiement par Orange Money.
                    Vous recevrez une notification dès validation.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3">
            <a href="{{ route('orders.index') }}"
               class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-xl transition-colors">
                <i class="fas fa-list me-2"></i> Voir mes commandes
            </a>
            <a href="{{ route('shop.home') }}"
               class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition-colors">
                <i class="fas fa-store me-2"></i> Continuer mes achats
            </a>
        </div>
    </div>

</body>
</html>
