<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->libelle }} — TELICO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Source Sans 3', sans-serif; }
        .navbar-fixed {
            position: fixed; top: 0; width: 100%; z-index: 50;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        }
        .prod-img-main {
            width: 100%; height: 380px;
            object-fit: cover; border-radius: 1.25rem;
            background: #f9fafb;
        }
        .prod-img-placeholder {
            width: 100%; height: 380px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #E6E9D5, #CED5AE);
            border-radius: 1.25rem; font-size: 80px;
        }
        .badge-cat {
            background: #E6E9D5; color: #283114;
            font-size: 12px; font-weight: 600;
            padding: 4px 14px; border-radius: 999px;
            display: inline-block;
        }
        .star { color: #e8a020; }
        .star-empty { color: #d1d5db; }
        .price-main { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: 2.1rem; font-weight: 700; color: var(--ink); }
        .price-old { font-size: 1rem; color: #9ca3af; text-decoration: line-through; }
        .tier-table { margin-bottom: 10px; }
        .tier-label {
            font-size: 10px; font-weight: 600; color: #3E4C21;
            text-transform: uppercase; letter-spacing: 0.03em;
            margin-bottom: 6px;
        }
        .tier-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; max-width: 320px; }
        .tier-cell { background: #E6E9D5; border-radius: 8px; padding: 6px 4px; text-align: center; }
        .tier-qty { font-size: 11px; font-weight: 700; color: #283114; }
        .tier-price { font-size: 12px; font-weight: 600; color: var(--ink); }
        .tier-badge {
            display: inline-block; margin-top: 2px;
            font-size: 10px; font-weight: 700; color: #fff;
            background: var(--accent-600); border-radius: 999px; padding: 1px 6px;
        }
        /* Sélecteur de quantité : styles centralisés dans partials/theme-head */
        .btn-cart {
            width: 100%;
            background: linear-gradient(135deg, #8C9A55, #3E4C21);
            color: #fff; font-weight: 700; font-size: 15px;
            padding: 14px; border: none; border-radius: 12px;
            cursor: pointer; transition: opacity .2s, transform .1s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-cart:hover { opacity: .88; transform: scale(1.01); }
        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; color: #6b7280; font-weight: 500;
            padding: 6px 14px; border: 1.5px solid #e5e7eb;
            border-radius: 999px; transition: all .2s;
            text-decoration: none;
        }
        .btn-back:hover { color: #3E4C21; border-color: #6B7A35; }
        #cart-count {
            transition: transform 0.3s ease;
        }

        /* Cartes produits similaires */
        .rel-card {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 16px; overflow: hidden;
            transition: all .25s; cursor: pointer;
            text-decoration: none;
        }
        .rel-card:hover { border-color: #6B7A35; transform: translateY(-3px); box-shadow: 0 10px 26px rgba(62,76,33,.16); }
        .rel-img { width: 100%; height: 130px; object-fit: cover; background: #fafafa; }
        .rel-placeholder {
            width: 100%; height: 130px;
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; background: #E6E9D5;
        }
        .rel-info { padding: 10px 12px 14px; }
        .rel-name { font-size: 12px; font-weight: 600; color: #1f2937; line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .rel-price { font-size: 14px; font-weight: 700; color: var(--ink); margin-top: 4px; }

        /* Toast */
        #toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            background: #1f2937; color: #fff;
            padding: 12px 20px; border-radius: 12px;
            font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            transform: translateY(80px); opacity: 0;
            transition: all .35s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
        }
        #toast.show { transform: translateY(0); opacity: 1; }
    </style>
</head>
<body class="bg-gray-50">

{{-- NAVBAR --}}
@include('partials.storefront-nav')

<div class="pt-24 pb-16 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- BREADCRUMB --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
        <a href="{{ route('accueil') }}" class="hover:text-brand-600 transition">Accueil</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('products.index') }}" class="hover:text-brand-600 transition">Catalogue</a>
        <i class="fas fa-chevron-right text-xs"></i>
        @if($product->categories->first())
        <span class="text-gray-400">{{ $product->categories->first()->category_type }}</span>
        <i class="fas fa-chevron-right text-xs"></i>
        @endif
        <span class="text-gray-700 font-medium truncate max-w-xs">{{ $product->libelle }}</span>
    </nav>

    {{-- FICHE PRODUIT --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

            {{-- IMAGE --}}
            <div class="p-6 flex items-center justify-center bg-gray-50">
                @if($product->image)
                    <img src="{{ asset('products/' . $product->image) }}"
                         alt="{{ $product->libelle }}"
                         class="prod-img-main"
                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="prod-img-placeholder" style="display:none;">🧰</div>
                @else
                    <div class="prod-img-placeholder">🧰</div>
                @endif
            </div>

            {{-- INFOS --}}
            <div class="p-8 flex flex-col justify-between">
                <div>
                    {{-- Catégorie + SKU --}}
                    <div class="flex items-center gap-3 mb-4">
                        @if($product->categories->first())
                            <span class="badge-cat">{{ $product->categories->first()->category_type }}</span>
                        @endif
                        <span class="text-xs text-gray-400 font-mono">SKU : {{ $product->sku }}</span>
                    </div>

                    {{-- Nom --}}
                    <h1 class="text-2xl font-bold text-gray-900 mb-3 leading-tight">{{ $product->libelle }}</h1>

                    {{-- Étoiles --}}
                    <div class="flex items-center gap-2 mb-4">
                        <span>
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($product->rating ?? 4.5))
                                    <i class="fas fa-star star text-sm"></i>
                                @elseif($i - 0.5 <= ($product->rating ?? 4.5))
                                    <i class="fas fa-star-half-alt star text-sm"></i>
                                @else
                                    <i class="far fa-star star-empty text-sm"></i>
                                @endif
                            @endfor
                        </span>
                        <span class="text-sm text-gray-500">{{ number_format($product->rating ?? 4.5, 1) }} / 5</span>
                    </div>

                    {{-- Description --}}
                    @if($product->description)
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">{{ $product->description }}</p>
                    @endif

                    {{-- Unité de vente du produit (carton de 24, rouleau de 100 m…) :
                         la quantité commandée et le prix sont dans cette unité. --}}
                    <div class="flex items-center gap-2 mb-2 text-sm text-gray-500">
                        <i class="fas fa-box text-brand-400"></i>
                        <span>Vendu par : <strong class="text-gray-700">{{ $product->unite_libelle }}</strong></span>
                    </div>
                </div>

                {{-- Prix + Bouton --}}
                <div>
                    @php $basePrice = $product->effective_promo_price ?? $product->price; @endphp
                    {{-- Voir home.blade.php : bloc masqué en entier quand les paliers
                         sont désactivés (config/pricing.php). --}}
                    @php $paliers = \App\Support\PricingTiers::displayTiers($basePrice); @endphp
                    @if($paliers)
                    <div class="tier-table mb-4">
                        <div class="tier-label">Tarifs de gros — prix unitaire</div>
                        <div class="tier-grid">
                            @foreach($paliers as $tier)
                            <div class="tier-cell">
                                <div class="tier-qty">{{ $tier['qty'] }}+</div>
                                <div class="tier-price">{{ number_format($tier['price'], 0, ',', ' ') }}</div>
                                @if($tier['percent'] > 0)
                                    <div class="tier-badge">-{{ $tier['percent'] }}%</div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="prod-qty prod-qty--lg">
                        <span class="qty-label">Quantité ({{ \App\Models\Product::motUnite($product->unite, 2) }})</span>
                        <button type="button" class="qty-btn" onclick="changeDetailQty(-1)" aria-label="Diminuer la quantité">−</button>
                        <input type="number" class="qty-input" min="1" value="1" id="detail-qty" data-base-price="{{ $basePrice }}" onchange="updateDetailPrice()">
                        <button type="button" class="qty-btn" onclick="changeDetailQty(1)" aria-label="Augmenter la quantité">+</button>
                    </div>

                    <div class="flex items-baseline gap-3 mb-3">
                        <span class="price-main" id="detail-price">{{ number_format($basePrice, 0, ',', ' ') }} GNF</span>
                        <span class="text-sm text-gray-500">/ {{ \App\Models\Product::motUnite($product->unite) }}</span>
                        @if($product->effective_promo_price)
                            <span class="price-old">{{ number_format($product->price, 0, ',', ' ') }} GNF</span>
                            @php $disc = round((1 - $product->effective_promo_price / $product->price) * 100); @endphp
                            <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded-full">-{{ $disc }}%</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between px-4 py-3 mb-6 rounded-xl" style="background:var(--brand-50);border:1px solid var(--brand-100);">
                        <span class="text-sm text-gray-600">Total pour <strong id="detail-total-qty">1</strong> <span id="detail-total-unite" data-singulier="{{ \App\Models\Product::motUnite($product->unite) }}" data-pluriel="{{ \App\Models\Product::motUnite($product->unite, 2) }}">{{ \App\Models\Product::motUnite($product->unite) }}</span></span>
                        <span class="text-lg font-bold" style="color:var(--ink);" id="detail-total">{{ number_format($basePrice, 0, ',', ' ') }} GNF</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" class="btn-cart" onclick="addToCart({{ $product->id }}, this, detailQty())">
                            <i class="fas fa-shopping-bag"></i>
                            Ajouter
                        </button>
                        <button type="button" class="btn-cart" style="background: linear-gradient(135deg, var(--accent-600), var(--accent-700));" onclick="buyNow({{ $product->id }}, this, detailQty())">
                            <i class="fas fa-bolt"></i>
                            Commander
                        </button>
                    </div>

                    <a href="{{ url()->previous() === url()->current() ? route('accueil') : url()->previous() }}"
                       class="btn-back mt-3 w-full justify-center">
                        <i class="fas fa-arrow-left text-xs"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- PRODUITS SIMILAIRES --}}
    @if($related->count() > 0)
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-5">
            <i class="fas fa-layer-group text-brand-500 mr-2"></i>
            Produits similaires
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach($related as $r)
            <a href="{{ route('productDetail', $r->id) }}" class="rel-card">
                @if($r->image)
                    <img src="{{ asset('products/' . $r->image) }}" alt="{{ $r->libelle }}"
                         class="rel-img"
                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="rel-placeholder" style="display:none;">🧰</div>
                @else
                    <div class="rel-placeholder">🧰</div>
                @endif
                <div class="rel-info">
                    <div class="rel-name">{{ $r->libelle }}</div>
                    <div class="rel-price">{{ number_format($r->effective_promo_price ?? $r->price, 0, ',', ' ') }} GNF</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>

@include('partials.storefront-footer')

{{-- TOAST --}}
<div id="toast">
    <i class="fas fa-check-circle text-green-400"></i>
    <span id="toast-msg">Produit ajouté au panier</span>
</div>

<script>window.PRICING_TIERS_ENABLED = @json(\App\Support\PricingTiers::enabled());</script>
<script src="{{ asset('assets/js/pricing-tiers.js') }}"></script>
<script>
function detailQty() {
    const el = document.getElementById('detail-qty');
    return el ? (parseInt(el.value, 10) || 1) : 1;
}

function changeDetailQty(delta) {
    const el = document.getElementById('detail-qty');
    if (!el) return;
    let val = (parseInt(el.value, 10) || 1) + delta;
    if (val < 1) val = 1;
    el.value = val;
    updateDetailPrice();
}

function updateDetailPrice() {
    const el = document.getElementById('detail-qty');
    const priceEl = document.getElementById('detail-price');
    if (!el || !priceEl) return;
    const base = parseFloat(el.dataset.basePrice);
    const qty = parseInt(el.value, 10) || 1;
    const unitPrice = unitPriceForQuantity(base, qty);
    priceEl.textContent = new Intl.NumberFormat('fr-FR').format(unitPrice) + ' GNF';

    const totalEl = document.getElementById('detail-total');
    const totalQtyEl = document.getElementById('detail-total-qty');
    if (totalEl) totalEl.textContent = new Intl.NumberFormat('fr-FR').format(unitPrice * qty) + ' GNF';
    if (totalQtyEl) totalQtyEl.textContent = qty;
    // « 1 douzaine », « 3 douzaines »
    const uniteEl = document.getElementById('detail-total-unite');
    if (uniteEl) uniteEl.textContent = qty > 1 ? uniteEl.dataset.pluriel : uniteEl.dataset.singulier;
}

function addToCart(id, btn, quantity = 1) {
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout...'; }
    fetch(`{{ url('/cart/add') }}/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ quantity })
    })
    .then(response => {
        if (!response.ok) throw new Error();
        return response.json();
    })
    .then(data => {
        showToast(data.message || 'Produit ajouté au panier !');
        // Mettre à jour le compteur du panier avec animation
        console.log('Cart count:', data.count);
        if (data.count !== undefined) {
            document.querySelectorAll('#cart-count').forEach(el => {
                console.log('Updating cart badge to:', data.count);
                el.textContent = data.count;
                el.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    el.style.transform = 'scale(1)';
                }, 300);
            });
        }
    })
    .catch(err => {
        console.error('Erreur lors de l\'ajout au panier:', err);
        showToast('Impossible d\'ajouter ce produit.', 'error');
    })
    .finally(() => {
        if (btn) {
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-shopping-bag"></i> Ajouter';
            }, 1200);
        }
    });
}

function buyNow(id, btn, quantity = 1) {
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...'; }

    // Ajouter au panier et rediriger
    fetch(`{{ url('/cart/add') }}/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ quantity })
    })
    .then(response => {
        console.log('Response:', response);
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data);
        // Mettre à jour le compteur du panier
        if (data.count !== undefined) {
            document.querySelectorAll('#cart-count').forEach(el => {
                el.textContent = data.count;
                el.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    el.style.transform = 'scale(1)';
                }, 300);
            });
        }
        @if(auth()->check() && auth()->user()->isCustomer())
            showToast('Produit ajouté ! Redirection...');
            setTimeout(() => {
                window.location.href = '{{ route("checkout") }}';
            }, 500);
        @else
            showToast('Produit ajouté ! Redirection vers la connexion...');
            const loginUrl = '{{ route("otp.login") }}?product_id=' + id;
            setTimeout(() => {
                window.location.href = loginUrl;
            }, 500);
        @endif
    })
    .catch((error) => {
        console.error('Erreur:', error);
        showToast('Impossible de passer la commande : ' + error.message, 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt"></i> Commander';
        }
    });
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    const msgEl = document.getElementById('toast-msg');
    msgEl.textContent = msg;
    t.classList.add('show');
    
    // Changer la couleur selon le type
    if (type === 'error') {
        t.style.background = '#ef4444';
    } else {
        t.style.background = '#1f2937';
    }
    
    setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
</body>
</html>
