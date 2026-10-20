<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Catalogue — TELICO</title>

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

        /* ── Sidebar ── */
        .sidebar {
            background: #fff;
            border: 1.5px solid #f3f4f6;
            border-radius: 16px;
            padding: 1.5rem;
            position: sticky;
            top: 90px;
        }
        .sidebar-title {
            font-size: 11px; font-weight: 700; color: #9ca3af;
            text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: 16px;
        }
        .filter-group { margin-bottom: 1.5rem; }
        .filter-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 10px; display: block; }
        .filter-option { display: flex; align-items: center; gap: 8px; padding: 5px 0; cursor: pointer; }
        .filter-option input { width: 15px; height: 15px; accent-color: #3E4C21; cursor: pointer; }
        .filter-option span { font-size: 13px; color: #374151; }
        .filter-badge { margin-left: auto; font-size: 11px; background: #f3f4f6; color: #6b7280; padding: 1px 8px; border-radius: 20px; }
        .filter-divider { border: none; border-top: 1px solid #f3f4f6; margin: 0 0 1.25rem; }
        .price-inputs { display: flex; gap: 8px; align-items: center; }
        .price-inputs input {
            width: 80px; padding: 6px 10px; font-size: 12px;
            border: 1.5px solid #e5e7eb; border-radius: 8px;
            background: #fff; color: #374151; outline: none;
            font-family: 'Source Sans 3', sans-serif;
        }
        .price-inputs input:focus { border-color: #6B7A35; }
        .price-sep { font-size: 12px; color: #9ca3af; }
        .reset-btn {
            width: 100%; padding: 8px; font-size: 12px; font-weight: 600;
            color: #3E4C21; border: 1.5px solid #ADB97E;
            border-radius: 8px; background: none; cursor: pointer;
            margin-top: 4px; transition: background 0.2s;
        }
        .reset-btn:hover { background: #E6E9D5; }

        /* ── Grille produits ── */
        .prod-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 20px;
        }
        .prod-card {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 20px; overflow: hidden;
            transition: all 0.25s; cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .prod-card:hover {
            border-color: #6B7A35;
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(245,158,11,0.15);
        }
        .prod-img {
            height: 190px; display: flex; align-items: center;
            justify-content: center; position: relative;
            background: #fafafa; overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .prod-card:hover .prod-img img { transform: scale(1.05); }
        .prod-img .no-img { font-size: 52px; }
        .prod-badge {
            position: absolute; top: 10px; left: 10px;
            font-size: 10px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
        }
        .badge-promo { background: rgba(244,236,225,0.95); color: #283114; }
        .badge-best  { background: rgba(62,76,33,.94); color: #fff; }

        .prod-info { padding: 14px 14px 16px; }
        .prod-name {
            font-size: 13px; font-weight: 600; color: #1f2937;
            margin-bottom: 4px; line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .prod-cat-tag {
            font-size: 11px; color: #fff; background: #6B7A35;
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            margin-bottom: 8px; font-weight: 500;
        }
        .tier-table { margin-bottom: 10px; }
        .tier-label {
            font-size: 10px; font-weight: 600; color: #3E4C21;
            text-transform: uppercase; letter-spacing: 0.03em;
            margin-bottom: 6px;
        }
        .tier-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
        .tier-cell { background: #E6E9D5; border-radius: 8px; padding: 4px 2px; text-align: center; }
        .tier-qty { font-size: 10px; font-weight: 700; color: #283114; }
        .tier-price { font-size: 11px; font-weight: 600; color: var(--ink); }
        .tier-badge {
            display: inline-block; margin-top: 2px;
            font-size: 9px; font-weight: 700; color: #fff;
            background: var(--accent-600); border-radius: 999px; padding: 1px 5px;
        }
        /* Sélecteur de quantité : styles centralisés dans partials/theme-head */
        .prod-price-row { display: flex; align-items: baseline; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; }
        .prod-price { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: 17.5px; font-weight: 700; color: var(--ink); }
        .prod-price-old { font-size: 12px; color: var(--ink-faint); text-decoration: line-through; }
        .prod-btns { display: flex; gap: 6px; }
        .prod-btn {
            flex: 1; padding: 9px 4px; font-size: 11px;
            font-weight: 600; text-align: center;
            background: linear-gradient(135deg, #8C9A55, #3E4C21);
            color: #fff; border: none; border-radius: 10px; cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            font-family: 'Source Sans 3', sans-serif;
        }
        .prod-btn:hover { opacity: 0.88; transform: scale(1.02); }
        .prod-btn-order {
            flex: 1; padding: 9px 4px; font-size: 11px;
            font-weight: 600; text-align: center;
            background: none; color: #3E4C21;
            border: 2px solid #3E4C21; border-radius: 10px; cursor: pointer;
            transition: all 0.2s; font-family: 'Source Sans 3', sans-serif;
        }
        .prod-btn-order:hover { background: #3E4C21; color: #fff; transform: scale(1.02); }

        .empty-state { text-align: center; padding: 4rem 1rem; color: #9ca3af; font-size: 14px; }
        .empty-state i { font-size: 40px; margin-bottom: 16px; display: block; color: #d1d5db; }

        /* ── Search bar ── */
        .search-bar {
            display: flex; align-items: center;
            border: 1.5px solid #e5e7eb; border-radius: 12px;
            overflow: hidden; background: #fff;
            transition: border-color 0.2s;
        }
        .search-bar:focus-within { border-color: #6B7A35; }
        .search-bar input {
            flex: 1; padding: 10px 16px; font-size: 13px;
            border: none; outline: none; background: transparent;
            font-family: 'Source Sans 3', sans-serif; color: #374151;
        }
        .search-bar button {
            padding: 10px 16px; background: #6B7A35; border: none;
            color: #fff; cursor: pointer; transition: background 0.2s;
        }
        .search-bar button:hover { background: #3E4C21; }

        /* ── Pagination ── */
        .pagination { display: flex; gap: 6px; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
        .page-btn-item {
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; cursor: pointer;
            border: 1.5px solid #e5e7eb; background: #fff; color: #374151;
            transition: all 0.2s;
        }
        .page-btn-item:hover, .page-btn-item.active {
            background: #6B7A35; border-color: #6B7A35; color: #fff;
        }

        @media (max-width: 768px) {
            .catalogue-layout { grid-template-columns: 1fr !important; }
            .sidebar { position: static !important; }
        }
            .prod-unite { font-size: .78rem; color: var(--ink-muted, #66695A); margin-left: -4px; }
    </style>
</head>
<body class="bg-gray-50">

    {{-- NAVBAR --}}
    @include('partials.storefront-nav')

    {{-- HERO BANNER --}}
    <div class="pt-20 bg-gradient-to-r from-brand-500 to-brand-600">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-1">Notre Catalogue</h1>
                    <p class="text-white/80 text-sm">
                        <span id="total-count">{{ count($allProducts) }}</span> produits disponibles
                    </p>
                </div>
                {{-- Barre de recherche --}}
                <div class="w-full md:max-w-sm">
                    <div class="search-bar">
                        <input type="text" id="search-input" placeholder="Rechercher un produit…" oninput="applyFilters()">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CONTENU PRINCIPAL --}}
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid catalogue-layout gap-6" style="grid-template-columns: 240px 1fr; align-items: start;">

            {{-- SIDEBAR FILTRES --}}
            <aside class="sidebar">
                <p class="sidebar-title"><i class="fas fa-sliders-h mr-2"></i>Filtres</p>

                <div class="filter-group">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span class="filter-label" style="margin-bottom:0">Catégories</span>
                        <a href="{{ route('public.categories') }}"
                           style="font-size:11px;font-weight:600;color:#6B7A35;text-decoration:none;display:flex;align-items:center;gap:4px;padding:3px 8px;border:1px solid #ADB97E;border-radius:20px;transition:all 0.2s;"
                           onmouseover="this.style.background='#E6E9D5'" onmouseout="this.style.background='transparent'">
                            <i class="fas fa-th-large" style="font-size:10px;"></i> Toutes
                        </a>
                    </div>
                    <label class="filter-option">
                        <input type="radio" name="cat-filter" value="" checked onchange="applyFilters()">
                        <span>Toutes</span>
                    </label>
                    @foreach($categories as $cat)
                    <label class="filter-option">
                        <input type="radio" name="cat-filter" value="{{ $cat->name }}" onchange="applyFilters()">
                        <span>{{ $cat->name }}</span>
                        <span class="filter-badge">{{ $cat->products_count }}</span>
                    </label>
                    @endforeach
                </div>

                <hr class="filter-divider">

                <div class="filter-group">
                    <span class="filter-label">Prix (GNF)</span>
                    <div class="price-inputs">
                        <input type="number" id="min-price" placeholder="Min" min="0" oninput="applyFilters()">
                        <span class="price-sep">—</span>
                        <input type="number" id="max-price" placeholder="Max" min="0" oninput="applyFilters()">
                    </div>
                </div>

                <hr class="filter-divider">

                <div class="filter-group">
                    <span class="filter-label">Trier par</span>
                    <select id="sort-select" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-brand-400" onchange="applyFilters()" style="font-family:'Source Sans 3',sans-serif;">
                        <option value="default">Plus récents</option>
                        <option value="price-asc">Prix croissant</option>
                        <option value="price-desc">Prix décroissant</option>
                        <option value="name-asc">Nom A → Z</option>
                        <option value="promo">Promotions d'abord</option>
                    </select>
                </div>

                <hr class="filter-divider">

                <div class="filter-group">
                    <span class="filter-label">Type</span>
                    <label class="filter-option">
                        <input type="radio" name="type-filter" value="" checked onchange="applyFilters()">
                        <span>Tous</span>
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="type-filter" value="promo" onchange="applyFilters()">
                        <span>En promotion</span>
                    </label>
                </div>

                <button class="reset-btn" onclick="resetFilters()">
                    <i class="fas fa-undo mr-1"></i> Réinitialiser
                </button>
            </aside>

            {{-- GRILLE PRODUITS --}}
            <div>
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <span id="results-count" class="text-sm text-gray-500"></span>
                </div>
                <div class="prod-grid" id="prod-grid"></div>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </div>

    @include('partials.storefront-footer')

    <script>window.PRICING_TIERS_ENABLED = @json(\App\Support\PricingTiers::enabled());</script>
    <script src="{{ asset('assets/js/pricing-tiers.js') }}"></script>
    <script>
        const ALL_PRODUCTS = @json($allProducts);
        const PER_PAGE = 24;
        let currentPage = 1;
        let filtered = [...ALL_PRODUCTS];

        function renderCard(p) {
            const isPromo = !!p.old_price;
            const badgeTxt = isPromo ? `-${p.discount}%` : '';
            const badgeCls = isPromo ? 'badge-promo' : '';
            const oldHTML  = p.old_price
                ? `<span class="prod-price-old">${Number(p.old_price).toLocaleString('fr-FR')} GNF</span>` : '';
            const imgHTML  = p.image
                ? `<img src="${p.image}" alt="${p.name}" onerror="this.onerror=null;this.src='https://placehold.co/400x400?text=Image'">`
                : `<span class="no-img">🌿</span>`;
            const badge = badgeTxt
                ? `<span class="prod-badge ${badgeCls}">${badgeTxt}</span>` : '';
            const tierHTML = displayTiersFor(p.price).map(t => `
                <div class="tier-cell">
                    <div class="tier-qty">${t.qty}+</div>
                    <div class="tier-price">${Number(t.price).toLocaleString('fr-FR')}</div>
                    ${t.percent > 0 ? `<div class="tier-badge">-${t.percent}%</div>` : ''}
                </div>`).join('');
            return `<div class="prod-card" onclick="window.location='/product/${p.id}'" style="cursor:pointer;">
                <div class="prod-img">
                    ${badge}
                    ${imgHTML}
                </div>
                <div class="prod-info">
                    <div class="prod-name">${p.name}</div>
                    <span class="prod-cat-tag">${p.category_name ?? ''}</span>
                    ${tierHTML ? `<div class="tier-table">
                        <div class="tier-label">Tarifs de gros — prix unitaire</div>
                        <div class="tier-grid">${tierHTML}</div>
                    </div>` : ''}
                    <div class="prod-qty" onclick="event.stopPropagation()">
                        <span class="qty-label">Quantité</span>
                        <button type="button" class="qty-btn" onclick="changeQty(this,-1)" aria-label="Diminuer la quantité">−</button>
                        <input type="number" class="qty-input" min="1" value="1" data-base-price="${p.price}" onchange="updateCardPrice(this)">
                        <button type="button" class="qty-btn" onclick="changeQty(this,1)" aria-label="Augmenter la quantité">+</button>
                    </div>
                    <div class="prod-price-row">
                        <span class="prod-price" data-computed-price>${Number(p.price).toLocaleString('fr-FR')} GNF</span><span class="prod-unite">/ ${p.unite || 'pièce'}</span>
                        ${oldHTML}
                    </div>
                    <div class="prod-btns">
                        <button class="prod-btn" onclick="event.stopPropagation();addToCartWithQty(${p.id}, this)">
                            <i class="fas fa-shopping-bag mr-1"></i> Panier
                        </button>
                        <button class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty(${p.id}, this)">
                            <i class="fas fa-bolt mr-1"></i> Commander
                        </button>
                    </div>
                </div>
            </div>`;
        }

        function changeQty(btn, delta) {
            const container = btn.closest('.prod-qty');
            const input = container.querySelector('.qty-input');
            let val = (parseInt(input.value, 10) || 1) + delta;
            if (val < 1) val = 1;
            input.value = val;
            updateCardPrice(input);
        }

        function updateCardPrice(input) {
            const card = input.closest('.prod-card');
            const base = parseFloat(input.dataset.basePrice);
            const qty = parseInt(input.value, 10) || 1;
            const unit = unitPriceForQuantity(base, qty);
            const priceEl = card?.querySelector('[data-computed-price]');
            if (priceEl) priceEl.textContent = new Intl.NumberFormat('fr-FR').format(unit) + ' GNF';
        }

        function addToCartWithQty(id, btn) {
            const card = btn.closest('.prod-card');
            const qty = parseInt(card?.querySelector('.qty-input')?.value, 10) || 1;
            addToCart(id, qty);
        }

        function orderNowWithQty(id, btn) {
            const card = btn.closest('.prod-card');
            const qty = parseInt(card?.querySelector('.qty-input')?.value, 10) || 1;
            orderNow(id, qty);
        }

        function applyFilters() {
            const search   = document.getElementById('search-input').value.toLowerCase().trim();
            const catVal   = document.querySelector('input[name="cat-filter"]:checked')?.value ?? '';
            const typeVal  = document.querySelector('input[name="type-filter"]:checked')?.value ?? '';
            const minP     = parseFloat(document.getElementById('min-price').value) || 0;
            const maxP     = parseFloat(document.getElementById('max-price').value) || Infinity;
            const sort     = document.getElementById('sort-select').value;

            filtered = ALL_PRODUCTS.filter(p => {
                if (search && !p.name.toLowerCase().includes(search) && !(p.category_name ?? '').toLowerCase().includes(search)) return false;
                if (catVal && p.category_name !== catVal) return false;
                if (typeVal === 'promo' && !p.old_price) return false;
                if (p.price < minP || p.price > maxP) return false;
                return true;
            });

            if (sort === 'price-asc')  filtered.sort((a,b) => a.price - b.price);
            if (sort === 'price-desc') filtered.sort((a,b) => b.price - a.price);
            if (sort === 'name-asc')   filtered.sort((a,b) => a.name.localeCompare(b.name));
            if (sort === 'promo')      filtered.sort((a,b) => (b.discount??0) - (a.discount??0));

            currentPage = 1;
            render();
        }

        function render() {
            const start = (currentPage - 1) * PER_PAGE;
            const page  = filtered.slice(start, start + PER_PAGE);
            const grid  = document.getElementById('prod-grid');

            grid.innerHTML = page.length
                ? page.map(renderCard).join('')
                : `<div class="empty-state col-span-full">
                       <i class="fas fa-box-open"></i>
                       Aucun produit trouvé.
                   </div>`;

            document.getElementById('results-count').textContent =
                `${filtered.length} produit${filtered.length !== 1 ? 's' : ''} trouvé${filtered.length !== 1 ? 's' : ''}`;

            renderPagination();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function renderPagination() {
            const total = Math.ceil(filtered.length / PER_PAGE);
            const pag   = document.getElementById('pagination');
            if (total <= 1) { pag.innerHTML = ''; return; }

            let html = '';
            if (currentPage > 1)
                html += `<div class="page-btn-item" onclick="goPage(${currentPage-1})"><i class="fas fa-chevron-left text-xs"></i></div>`;
            for (let i = 1; i <= total; i++) {
                if (total > 7 && Math.abs(i - currentPage) > 2 && i !== 1 && i !== total) {
                    if (i === currentPage - 3 || i === currentPage + 3) html += `<div class="page-btn-item" style="cursor:default">…</div>`;
                    continue;
                }
                html += `<div class="page-btn-item ${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</div>`;
            }
            if (currentPage < total)
                html += `<div class="page-btn-item" onclick="goPage(${currentPage+1})"><i class="fas fa-chevron-right text-xs"></i></div>`;
            pag.innerHTML = html;
        }

        function goPage(n) { currentPage = n; render(); }

        function resetFilters() {
            document.getElementById('search-input').value = '';
            document.querySelectorAll('input[name="cat-filter"]')[0].checked = true;
            document.querySelectorAll('input[name="type-filter"]')[0].checked = true;
            document.getElementById('min-price').value = '';
            document.getElementById('max-price').value = '';
            document.getElementById('sort-select').selectedIndex = 0;
            applyFilters();
        }

        function orderNow(id, quantity = 1) {
            @if(auth()->check() && auth()->user()->isCustomer())
                window.location.href = '{{ url("/shop/buy-now") }}/' + id + '?quantity=' + quantity;
            @else
                window.location.href = '{{ route("otp.login") }}';
            @endif
        }

        function addToCart(id, quantity = 1) {
            fetch(`{{ url('/cart/add') }}/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ quantity })
            })
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(data => {
                const badge = document.getElementById('cart-count');
                if (badge && data.count !== undefined) badge.textContent = data.count;
                showToast('Produit ajouté au panier !');
            })
            .catch(() => showToast('Impossible d\'ajouter ce produit.', true));
        }

        function showToast(msg, isError = false) {
            let t = document.getElementById('toast');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toast';
                t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;color:#fff;transition:opacity 0.4s;box-shadow:0 4px 20px rgba(0,0,0,0.15)';
                document.body.appendChild(t);
            }
            t.style.background = isError ? '#d92d20' : '#3E4C21';
            t.textContent = msg;
            t.style.opacity = '1';
            clearTimeout(t._timer);
            t._timer = setTimeout(() => t.style.opacity = '0', 2800);
        }

        // Lire le paramètre ?cat= dans l'URL et pré-sélectionner la catégorie
        const urlCat = new URLSearchParams(window.location.search).get('cat');
        if (urlCat) {
            const radios = document.querySelectorAll('input[name="cat-filter"]');
            radios.forEach(r => {
                if (r.value.toLowerCase() === urlCat.toLowerCase()) {
                    r.checked = true;
                }
            });
        }

        // Lire le paramètre ?search= dans l'URL (recherche depuis le header)
        const urlSearch = new URLSearchParams(window.location.search).get('search');
        if (urlSearch) {
            document.getElementById('search-input').value = urlSearch;
        }

        // Init
        applyFilters();
    </script>
</body>
</html>
