<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TELICO — Quincaillerie générale à Conakry</title>
    <meta name="description" content="TELICO, quincaillerie générale à Madina, Conakry : peinture, outillage, serrurerie, plomberie, électricité. Commande en ligne, livraison partout en Guinée.">

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Système de design partagé TELICO (palette du logo, typographies, composants). --}}
    @include('partials.theme-head')

    {{-- Font Awesome pour les icônes --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ══════════════════════════════════════════════════════════
           ACCUEIL TELICO — « le comptoir de quincaillerie »
           Fond ivoire, emblème du baobab, étiquettes de rayon.
           Grands titres en Cinzel (comme le logo), étiquettes et prix
           en Barlow Condensed, texte en Source Sans 3.
           ══════════════════════════════════════════════════════════ */
        :root {
            --ivoire: #F9F9F3;
            --ecorce: #5B4F2B;
            --or: #C7A150;
            --or-texte: #8A6A24;
            --trait: #E6E6D8;
            --brand-400: #8C9A55;
            --brand-800: #283114;
        }
        body { background: var(--ivoire); }
        .label { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; text-transform: uppercase; letter-spacing: .14em; font-weight: 600; }
        .titre { font-family: 'Cinzel', Georgia, serif; font-weight: 600; color: var(--brand-700); letter-spacing: .02em; text-wrap: balance; }
        .section-tete { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 28px; }
        .section-tete .label { color: var(--or-texte); font-size: .85rem; display: block; margin-bottom: 8px; }
        .section-tete h2 { font-size: clamp(1.7rem, 3.4vw, 2.4rem); line-height: 1.15; }
        .section-tete p { color: var(--ink-muted); max-width: 60ch; margin-top: 8px; }
        .lien-fleche { color: var(--brand-700); font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; gap: 8px; }
        .lien-fleche:hover { color: var(--brand-900); }
        .filet-or { display: flex; align-items: center; gap: 10px; color: var(--or); font-size: 10px; }
        .filet-or::before, .filet-or::after { content: ''; height: 1px; flex: 1; background: var(--or); opacity: .6; }

        /* ── Ouverture ── */
        .ouverture {
            margin-top: 83px; /* bandeau fixe : 80 px + filet de 3 px */
            position: relative; overflow: hidden;
            background:
                radial-gradient(1200px 500px at 85% 20%, rgba(199,161,80,.12), transparent 60%),
                radial-gradient(900px 500px at 10% 100%, rgba(122,135,64,.10), transparent 60%),
                var(--ivoire);
            border-bottom: 1px solid var(--trait);
        }
        .ouverture-grille { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 48px; align-items: center; padding-block: 56px 64px; }
        .ouverture .label { color: var(--or-texte); font-size: .9rem; }
        .ouverture h1 { font-size: clamp(2.3rem, 5.2vw, 3.9rem); line-height: 1.08; margin: 14px 0 18px; }
        .ouverture h1 em { font-style: normal; color: var(--ecorce); }
        .ouverture .chapeau { font-size: 1.12rem; line-height: 1.65; color: var(--ink-muted); max-width: 54ch; }
        .recherche { margin-top: 28px; display: flex; background: #fff; border: 1.5px solid var(--brand-200); border-radius: 14px; padding: 6px; max-width: 560px; box-shadow: 0 12px 30px -22px rgba(28,35,13,.5); }
        .recherche:focus-within { border-color: var(--brand-500); }
        .recherche i { color: var(--brand-500); padding: 0 6px 0 12px; align-self: center; }
        .recherche input { flex: 1; min-width: 0; border: 0; outline: 0; font-size: 1rem; padding: 10px 6px; background: transparent; color: var(--ink); }
        .recherche button { background: var(--brand-600); color: #fff; font-weight: 700; border-radius: 10px; padding: 0 20px; transition: background .2s; }
        .recherche button:hover { background: var(--brand-800); }
        .rayons-rapides { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 16px; }
        .rayons-rapides span { font-size: .85rem; color: var(--ink-muted); margin-right: 4px; }
        .rayons-rapides a { font-size: .85rem; font-weight: 600; color: var(--brand-700); background: #fff; border: 1px solid var(--trait); border-radius: 999px; padding: 5px 12px; transition: border-color .2s, background .2s; }
        .rayons-rapides a:hover { border-color: var(--brand-400); background: var(--brand-50); }

        .embleme { position: relative; display: flex; justify-content: center; }
        .embleme-cercle { position: relative; width: min(100%, 400px); aspect-ratio: 1; border-radius: 50%; background: #fff; border: 1px solid var(--or); box-shadow: 0 0 0 10px rgba(255,255,255,.7), 0 30px 60px -30px rgba(28,35,13,.45); display: flex; align-items: center; justify-content: center; }
        .embleme-cercle img { width: 78%; height: auto; }
        .etiquette { position: absolute; background: #fff; border: 1px solid var(--trait); border-radius: 8px; padding: 10px 14px 10px 26px; box-shadow: 0 14px 28px -20px rgba(28,35,13,.6); display: grid; gap: 1px; min-width: 140px; }
        .etiquette::before { content: ''; position: absolute; left: 10px; top: 50%; width: 7px; height: 7px; margin-top: -3.5px; border-radius: 50%; background: var(--ivoire); border: 1.5px solid var(--or); }
        .etiquette b { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: 1.6rem; line-height: 1; color: var(--brand-700); font-variant-numeric: tabular-nums; }
        .etiquette small { font-size: .78rem; color: var(--ink-muted); }
        .etiquette.e1 { top: 8%; left: -4%; transform: rotate(-3deg); }
        .etiquette.e2 { bottom: 16%; right: -6%; transform: rotate(2.5deg); }
        .etiquette.e3 { bottom: 2%; left: 4%; transform: rotate(-1.5deg); }

        /* ── Bandeau des services ── */
        .comptoir { background: var(--brand-700); color: #F6F2DF; }
        .comptoir-grille { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .comptoir-item { display: flex; align-items: center; gap: 14px; padding: 20px 18px; }
        .comptoir-item + .comptoir-item { border-left: 1px solid rgba(199,161,80,.35); }
        .comptoir-item i { color: var(--or); font-size: 1.3rem; width: 24px; text-align: center; }
        .comptoir-item b { display: block; font-size: .98rem; }
        .comptoir-item small { color: rgba(246,242,223,.75); font-size: .82rem; }

        /* ── Rayons ── */
        .rayons { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 14px; }
        .rayon { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid var(--trait); border-radius: 10px; padding: 14px; transition: border-color .2s, transform .2s, box-shadow .2s; }
        .rayon:hover { border-color: var(--brand-400); transform: translateY(-2px); box-shadow: 0 14px 26px -20px rgba(28,35,13,.6); }
        .rayon-visuel { width: 54px; height: 54px; flex-shrink: 0; border-radius: 8px; background: var(--brand-50); color: var(--brand-600); display: flex; align-items: center; justify-content: center; font-size: 22px; overflow: hidden; }
        .rayon-visuel img { width: 100%; height: 100%; object-fit: cover; }
        .rayon-nom { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-weight: 600; font-size: 1.08rem; letter-spacing: .05em; text-transform: uppercase; color: var(--ink); line-height: 1.15; overflow-wrap: anywhere; }
        .rayon-compte { font-size: .8rem; color: var(--ink-muted); }
        .rayon--tous { background: var(--brand-700); border-color: var(--brand-700); }
        .rayon--tous .rayon-visuel { background: rgba(255,255,255,.12); color: var(--or); }
        .rayon--tous .rayon-nom { color: #F6F2DF; }
        .rayon--tous .rayon-compte { color: rgba(246,242,223,.75); }

        /* ── Cartes produits (mêmes classes que le script) ── */
        .prod-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 18px; }
        @media (max-width: 1100px) { .prod-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (max-width: 820px)  { .prod-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 520px)  { .prod-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; } }
        .prod-card { background: #fff; border: 1px solid var(--trait); border-radius: 10px; overflow: hidden; cursor: pointer; transition: border-color .2s, box-shadow .2s, transform .2s; display: flex; flex-direction: column; }
        .prod-card:hover { border-color: var(--brand-300); transform: translateY(-3px); box-shadow: 0 18px 34px -24px rgba(28,35,13,.6); }
        .prod-img { aspect-ratio: 1 / .82; max-width: 100%; display: flex; align-items: center; justify-content: center; position: relative; background: var(--ivoire); overflow: hidden; border-bottom: 1px solid var(--trait); }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
        .prod-card:hover .prod-img img { transform: scale(1.04); }
        .prod-img .no-img { font-size: 38px; color: var(--brand-200); }
        .prod-badge { position: absolute; top: 10px; left: 10px; font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: .82rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; padding: 3px 9px; border-radius: 4px; }
        .badge-promo { background: #B3261E; color: #fff; }
        .badge-best  { background: var(--brand-700); color: #F6F2DF; }
        .prod-info { padding: 12px 13px 14px; display: flex; flex-direction: column; flex: 1; }
        .prod-cat-tag { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: .78rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--or-texte); margin-bottom: 4px; }
        .prod-name { font-size: .98rem; font-weight: 600; color: var(--ink); line-height: 1.3; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .prod-stars { font-size: 12px; color: var(--or); margin-bottom: 8px; }
        .tier-table { margin-bottom: 10px; }
        .tier-label { font-size: 9.5px; font-weight: 700; color: var(--brand-600); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
        .tier-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; }
        .tier-cell { background: var(--brand-50); border: 1px solid var(--brand-100); border-radius: 6px; padding: 5px 2px; text-align: center; }
        .tier-qty { font-size: 10px; font-weight: 700; color: var(--brand-700); }
        .tier-price { font-size: 11px; font-weight: 600; color: var(--ink); }
        .tier-badge { display: inline-block; margin-top: 2px; font-size: 9px; font-weight: 700; color: #fff; background: var(--accent-600); border-radius: 999px; padding: 1px 6px; }
        .prod-price-row { display: flex; align-items: baseline; gap: 8px; margin: auto 0 11px; padding-top: 6px; flex-wrap: wrap; }
        .prod-price { font-family: 'Barlow Condensed', 'Arial Narrow', sans-serif; font-size: 1.35rem; font-weight: 700; color: var(--brand-700); font-variant-numeric: tabular-nums; }
        .prod-price-old { font-size: 12px; color: var(--ink-faint); text-decoration: line-through; }
        .prod-btns { display: flex; gap: 6px; }
        .prod-btn, .prod-btn-order { flex: 1; padding: 9px 4px; font-size: .82rem; font-weight: 700; text-align: center; border-radius: 8px; cursor: pointer; transition: background .18s, color .18s, border-color .18s; }
        .prod-btn { background: var(--brand-600); color: #fff; border: 1.5px solid var(--brand-600); }
        .prod-btn:hover { background: var(--brand-800); border-color: var(--brand-800); }
        .prod-btn-order { background: #fff; color: var(--or-texte); border: 1.5px solid var(--or); }
        .prod-btn-order:hover { background: var(--or); color: #1C230D; }

        /* ── Sélections en onglets ── */
        .onglets { display: flex; gap: 6px; flex-wrap: wrap; border-bottom: 1px solid var(--trait); margin-bottom: 22px; }
        .onglet { font-weight: 700; color: var(--ink-muted); padding: 10px 14px; border-bottom: 3px solid transparent; margin-bottom: -1px; display: inline-flex; align-items: center; gap: 8px; }
        .onglet i { color: var(--or); }
        .onglet[aria-selected="true"] { color: var(--brand-700); border-bottom-color: var(--brand-600); }
        .onglet:focus-visible { outline: 2px solid var(--or); outline-offset: 2px; }
        .prod-row { display: flex; gap: 16px; overflow-x: auto; padding: 4px 2px 14px; scrollbar-width: thin; scrollbar-color: var(--brand-200) transparent; }
        .prod-row .prod-card { flex: 0 0 240px; }

        /* ── Baobab : racines, tronc, branches ── */
        .baobab { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; border: 1px solid var(--trait); border-radius: 12px; background: #fff; overflow: hidden; }
        .baobab > div { padding: 26px 24px; display: grid; gap: 8px; align-content: start; }
        .baobab > div + div { border-left: 1px solid var(--trait); }
        .baobab .label { color: var(--or-texte); font-size: .82rem; }
        .baobab h3 { font-family: 'Cinzel', Georgia, serif; font-weight: 600; color: var(--brand-700); font-size: 1.25rem; }
        .baobab p { color: var(--ink-muted); line-height: 1.6; }
        .livraisons { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-top: 18px; }
        .livraison { display: flex; gap: 12px; align-items: flex-start; padding: 14px; border-radius: 10px; background: var(--brand-50); border: 1px solid var(--brand-100); }
        .livraison i { color: var(--brand-600); margin-top: 3px; }
        .livraison b { display: block; color: var(--ink); }
        .livraison small { color: var(--ink-muted); line-height: 1.45; display: block; }

        /* ── Boutiques ── */
        .boutiques { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .boutique { background: #fff; border: 1px solid var(--trait); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
        .boutique-visuel { height: 150px; background: var(--brand-50); display: flex; align-items: center; justify-content: center; color: var(--brand-400); font-size: 40px; }
        .boutique-visuel img { width: 100%; height: 100%; object-fit: cover; }
        .boutique-corps { padding: 16px; display: grid; gap: 6px; }
        .boutique-corps h3 { font-weight: 700; color: var(--ink); font-size: 1.05rem; }
        .boutique-corps p, .boutique-corps a { font-size: .9rem; color: var(--ink-muted); }
        .boutique-corps a { color: var(--brand-700); font-weight: 600; }

        /* ── Contact et newsletter ── */
        .final { background: var(--brand-800); color: #F6F2DF; }
        .final-grille { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 40px; padding-block: 60px; align-items: start; }
        .final h2 { font-family: 'Cinzel', Georgia, serif; font-weight: 600; font-size: clamp(1.7rem, 3.2vw, 2.3rem); color: #F6F2DF; }
        .final .label { color: var(--or); font-size: .85rem; }
        .coordonnees { display: grid; gap: 14px; margin-top: 22px; }
        .coordonnees div { display: flex; gap: 14px; align-items: flex-start; }
        .coordonnees i { color: var(--or); width: 20px; margin-top: 4px; text-align: center; }
        .coordonnees small { display: block; color: rgba(246,242,223,.7); font-size: .8rem; }
        .coordonnees a, .coordonnees span { color: #F6F2DF; font-weight: 600; overflow-wrap: anywhere; }
        .boite { background: rgba(255,255,255,.06); border: 1px solid rgba(199,161,80,.35); border-radius: 12px; padding: 24px; }
        .boite p { color: rgba(246,242,223,.8); margin: 8px 0 16px; }
        .boite form { display: flex; gap: 8px; flex-wrap: wrap; }
        .boite input { flex: 1 1 200px; min-width: 0; border-radius: 8px; padding: 11px 14px; color: var(--ink); border: 0; }
        .boite button { background: var(--or); color: #1C230D; font-weight: 700; border-radius: 8px; padding: 11px 18px; }
        .boite button:hover { background: #D9B867; }
        .btn-or { display: inline-flex; align-items: center; gap: 8px; background: var(--or); color: #1C230D; font-weight: 700; border-radius: 8px; padding: 11px 18px; margin-top: 22px; }
        .btn-or:hover { background: #D9B867; color: #1C230D; }

        @media (max-width: 960px) {
            .ouverture-grille, .final-grille { grid-template-columns: minmax(0, 1fr); }
            .embleme { max-width: 380px; margin: 0 auto; width: 100%; }
            .comptoir-grille, .livraisons { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .comptoir-item:nth-child(3) { border-left: 0; }
            .baobab { grid-template-columns: minmax(0, 1fr); }
            .baobab > div + div { border-left: 0; border-top: 1px solid var(--trait); }
        }
        @media (max-width: 520px) {
            .ouverture-grille { padding-block: 36px 44px; }
            .comptoir-grille, .livraisons { grid-template-columns: minmax(0, 1fr); }
            .comptoir-item + .comptoir-item { border-left: 0; border-top: 1px solid rgba(199,161,80,.25); }
            .etiquette { min-width: 0; padding: 8px 10px 8px 22px; }
            .etiquette b { font-size: 1.3rem; }
            .etiquette.e2 { right: 0; }
            .etiquette.e1 { left: 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .prod-card, .rayon, .prod-img img { transition: none; }
        }
            .prod-unite { font-size: .78rem; color: var(--ink-muted, #66695A); margin-left: -4px; }
    </style>
</head>
<body>

    @include('partials.storefront-nav')

    @php
        $company = \App\Models\Company::current();
        // Icône d'un rayon d'après son nom (aucune catégorie n'est codée en dur).
        $iconePour = function ($nom) {
            $rayons = [
                'peinture' => 'fa-paint-roller', 'outil' => 'fa-screwdriver-wrench', 'serrur' => 'fa-lock',
                'plomb' => 'fa-faucet', 'lectric' => 'fa-bolt', 'ciment' => 'fa-trowel-bricks', 'fer' => 'fa-bars',
                'quincaill' => 'fa-toolbox', 'jardin' => 'fa-seedling', 'sanitaire' => 'fa-sink',
            ];
            $minuscule = mb_strtolower($nom);
            foreach ($rayons as $motCle => $icone) {
                if (str_contains($minuscule, $motCle)) {
                    return $icone;
                }
            }
            return 'fa-toolbox';
        };
        $rayonsPopulaires = $categories->sortByDesc('products_count')->take(5);
    @endphp

    {{-- ══════════ OUVERTURE ══════════ --}}
    <section id="accueil" class="ouverture">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="ouverture-grille">
                <div>
                    <span class="label">Quincaillerie générale · Madina, Conakry</span>
                    <h1 class="titre">Bâtir solide, <em>comme le baobab.</em></h1>
                    <p class="chapeau">
                        Peinture, outillage, serrurerie, plomberie, électricité : tout le matériel du chantier
                        au même comptoir. Commandez en ligne, payez à la livraison ou par Orange Money.
                    </p>

                    <form action="{{ route('products.index') }}" method="GET" class="recherche" role="search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <label for="recherche-accueil" class="sr-only">Rechercher un article</label>
                        <input id="recherche-accueil" type="text" name="search" placeholder="Ciment, peinture, robinet, câble…" autocomplete="off">
                        <button type="submit">Chercher</button>
                    </form>

                    @if($rayonsPopulaires->isNotEmpty())
                    <div class="rayons-rapides">
                        <span>Rayons :</span>
                        @foreach($rayonsPopulaires as $cat)
                            <a href="{{ route('products.index') }}?cat={{ urlencode($cat->name) }}">{{ $cat->name }}</a>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="embleme" aria-hidden="true">
                    <div class="embleme-cercle">
                        <img src="{{ asset('companies/telico-embleme.png') }}" alt="">
                    </div>
                    <div class="etiquette e1"><b>{{ number_format($totalProducts, 0, ',', ' ') }}+</b><small>articles en catalogue</small></div>
                    <div class="etiquette e2"><b>{{ $totalCategories }}</b><small>rayons</small></div>
                    <div class="etiquette e3"><b>24–48 h</b><small>livraison à Conakry</small></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════ SERVICES ══════════ --}}
    <section class="comptoir" aria-label="Nos services">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="comptoir-grille">
                @foreach([
                    ['icon' => 'fa-truck-fast',      'title' => 'Livraison 24–48 h',   'text' => 'À Conakry, partout en Guinée'],
                    ['icon' => 'fa-store',           'title' => 'Retrait gratuit',     'text' => 'Au magasin de Madina'],
                    ['icon' => 'fa-money-bill-wave', 'title' => 'Paiement souple',     'text' => 'Orange Money ou à la livraison'],
                    ['icon' => 'fa-headset',         'title' => 'Conseil chantier',    'text' => $company?->phone ?: 'Par téléphone'],
                ] as $item)
                <div class="comptoir-item">
                    <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                    <div><b>{{ $item['title'] }}</b><small>{{ $item['text'] }}</small></div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ RAYONS ══════════ --}}
    @if($categories->isNotEmpty())
    <section class="py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="section-tete">
                <div>
                    <span class="label">Nos rayons</span>
                    <h2 class="titre">Trouvez le bon rayon</h2>
                </div>
                <a href="{{ route('public.categories') }}" class="lien-fleche">Tous les rayons <i class="fas fa-arrow-right text-sm"></i></a>
            </div>
            <div class="rayons">
                @foreach($categories->take(11) as $cat)
                <a href="{{ route('products.index') }}?cat={{ urlencode($cat->name) }}" class="rayon">
                    <span class="rayon-visuel">
                        @if($cat->image_url)
                            <img src="{{ $cat->image_url }}" alt="" loading="lazy"
                                 onerror="this.outerHTML='<i class=&quot;fas {{ $iconePour($cat->name) }}&quot;></i>'">
                        @else
                            <i class="fas {{ $iconePour($cat->name) }}"></i>
                        @endif
                    </span>
                    <span>
                        <span class="rayon-nom">{{ $cat->name }}</span>
                        <span class="rayon-compte">{{ $cat->products_count }} article{{ $cat->products_count > 1 ? 's' : '' }}</span>
                    </span>
                </a>
                @endforeach
                <a href="{{ route('products.index') }}" class="rayon rayon--tous">
                    <span class="rayon-visuel"><i class="fas fa-store"></i></span>
                    <span>
                        <span class="rayon-nom">Tout le catalogue</span>
                        <span class="rayon-compte">{{ number_format($totalProducts, 0, ',', ' ') }} articles</span>
                    </span>
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- ══════════ NOUVEAUX ARRIVAGES ══════════ --}}
    <section id="produits" class="py-16 bg-white border-y" style="border-color: var(--trait);">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="section-tete">
                <div>
                    <span class="label">Arrivages</span>
                    <h2 class="titre">Nouveau au comptoir</h2>
                    <p>Nos dernières références. Ajustez la quantité directement sur la fiche, le prix suit.</p>
                </div>
                <a href="{{ route('products.index') }}" class="lien-fleche">Voir le catalogue ({{ number_format($totalProducts, 0, ',', ' ') }}) <i class="fas fa-arrow-right text-sm"></i></a>
            </div>
            @if(isset($allProducts) && count($allProducts) > 0)
            <div class="prod-grid" id="grid-all">
                @foreach($allProducts->take(10) as $p)
                <div class="prod-card" onclick="window.location='{{ route('productDetail', $p['id']) }}'">
                    <div class="prod-img">
                        @if($p['image'])
                            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" loading="lazy"
                                 onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='block'">
                            <span class="no-img" style="display:none"><i class="fas fa-toolbox"></i></span>
                        @else
                            <span class="no-img"><i class="fas fa-toolbox"></i></span>
                        @endif
                        @if($p['old_price'])
                            <span class="prod-badge badge-promo">-{{ $p['discount'] }}%</span>
                        @endif
                    </div>
                    <div class="prod-info">
                        <div class="prod-cat-tag">{{ $p['category_name'] }}</div>
                        <div class="prod-name">{{ $p['name'] }}</div>
                        <div class="prod-stars" aria-label="Note {{ $p['rating'] }} sur 5">
                            @for($i = 1; $i <= 5; $i++)@if($i <= floor($p['rating']))★@else☆@endif @endfor
                        </div>
                        {{-- Paliers désactivés sur cette installation : displayTiers()
                             renvoie un tableau vide et le bloc disparaît. --}}
                        @php $paliers = \App\Support\PricingTiers::displayTiers($p['price']); @endphp
                        @if($paliers)
                        <div class="tier-table">
                            <div class="tier-label">Tarifs de gros — prix unitaire</div>
                            <div class="tier-grid">
                                @foreach($paliers as $tier)
                                <div class="tier-cell">
                                    <div class="tier-qty">{{ $tier['qty'] }}+</div>
                                    <div class="tier-price">{{ number_format($tier['price'], 0, ',', ' ') }}</div>
                                    @if($tier['percent'] > 0)<div class="tier-badge">-{{ $tier['percent'] }}%</div>@endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        <div class="prod-qty" onclick="event.stopPropagation()">
                            <span class="qty-label">Quantité</span>
                            <button type="button" class="qty-btn" onclick="changeQty(this,-1)" aria-label="Diminuer la quantité">−</button>
                            <input type="number" class="qty-input" min="1" value="1" data-base-price="{{ $p['price'] }}" onchange="updateCardPrice(this)" aria-label="Quantité">
                            <button type="button" class="qty-btn" onclick="changeQty(this,1)" aria-label="Augmenter la quantité">+</button>
                        </div>
                        <div class="prod-price-row">
                            <span class="prod-price" data-computed-price>{{ number_format($p['price'], 0, ',', ' ') }} GNF</span><span class="prod-unite">/ {{ $p['unite'] ?? 'pièce' }}</span>
                            @if($p['old_price'])
                                <span class="prod-price-old">{{ number_format($p['old_price'], 0, ',', ' ') }} GNF</span>
                            @endif
                        </div>
                        <div class="prod-btns">
                            <button type="button" class="prod-btn" onclick="event.stopPropagation();addToCartWithQty({{ $p['id'] }}, this)">
                                <i class="fas fa-shopping-bag mr-1"></i> Panier
                            </button>
                            <button type="button" class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty({{ $p['id'] }}, this)">
                                Commander
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-center py-10" style="color:var(--ink-muted)">Le catalogue est en cours de mise en ligne. Appelez-nous pour toute demande.</p>
            @endif
        </div>
    </section>

    {{-- ══════════ SÉLECTIONS (onglets) ══════════ --}}
    <section class="py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="section-tete">
                <div>
                    <span class="label">Sélections</span>
                    <h2 class="titre">Ce que nos clients choisissent</h2>
                </div>
            </div>
            @php
                $onglets = array_values(array_filter([
                    (isset($promoProducts) && count($promoProducts) > 0) ? ['row-promo', 'fa-tag', 'Promotions'] : null,
                    ['row-best-sellers', 'fa-fire', 'Les plus vendus'],
                    ['row-top-rated', 'fa-star', 'Les mieux notés'],
                    ['row-trending', 'fa-chart-line', 'Tendances (30 jours)'],
                ]));
            @endphp
            <div class="onglets" role="tablist" aria-label="Sélections de produits">
                @foreach($onglets as $i => [$cible, $icone, $libelle])
                    <button type="button" class="onglet" role="tab" id="tab-{{ $cible }}" aria-controls="{{ $cible }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}" tabindex="{{ $i === 0 ? '0' : '-1' }}">
                        <i class="fas {{ $icone }}" aria-hidden="true"></i> {{ $libelle }}
                    </button>
                @endforeach
            </div>
            @foreach($onglets as $i => [$cible, $icone, $libelle])
                <div class="prod-row" id="{{ $cible }}" role="tabpanel" aria-labelledby="tab-{{ $cible }}" @if($i !== 0) hidden @endif></div>
            @endforeach
        </div>
    </section>

    {{-- ══════════ POURQUOI TELICO ══════════ --}}
    <section id="apropos" class="py-16 bg-white border-y" style="border-color: var(--trait);">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="section-tete">
                <div>
                    <span class="label">Pourquoi TELICO</span>
                    <h2 class="titre">Comme le baobab : des racines, un tronc, des branches</h2>
                    <p>TELICO est une quincaillerie générale de Madina, à Conakry. Particuliers et professionnels du bâtiment y trouvent le matériel et le conseil pour chaque chantier.</p>
                </div>
            </div>
            <div class="baobab">
                <div>
                    <span class="label">Les racines</span>
                    <h3>Ancrés à Madina</h3>
                    <p>Une quincaillerie de quartier, avec un vrai comptoir et des vendeurs qui connaissent le matériel. Vous pouvez passer voir, comparer, demander conseil.</p>
                </div>
                <div>
                    <span class="label">Le tronc</span>
                    <h3>Du matériel qui dure</h3>
                    <p>Peinture, outillage, serrurerie, plomberie, électricité : des articles choisis pour tenir sur le chantier, à des prix accessibles.</p>
                </div>
                <div>
                    <span class="label">Les branches</span>
                    <h3>Partout en Guinée</h3>
                    <p>Livraison en 24 à 48 h à Conakry, expédition dans toutes les régions, ou retrait gratuit au magasin.</p>
                </div>
            </div>
            <div class="livraisons">
                @foreach([
                    ['icon' => 'fa-truck', 'title' => 'À domicile ou sur chantier', 'text' => 'En 24 à 48 h à Conakry.'],
                    ['icon' => 'fa-store', 'title' => 'Retrait en magasin', 'text' => 'Gratuit, à notre quincaillerie de Madina.'],
                    ['icon' => 'fa-bolt', 'title' => 'Express', 'text' => 'Le jour même à Conakry pour une commande passée avant midi.'],
                    ['icon' => 'fa-map', 'title' => 'En région', 'text' => 'Expédition dans toute la Guinée via nos transporteurs.'],
                ] as $mode)
                <div class="livraison">
                    <i class="fas {{ $mode['icon'] }}" aria-hidden="true"></i>
                    <div><b>{{ $mode['title'] }}</b><small>{{ $mode['text'] }}</small></div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ BOUTIQUES ══════════ --}}
    @if(isset($stores) && $stores->count() > 0)
    <section class="py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="section-tete">
                <div>
                    <span class="label">Où nous trouver</span>
                    <h2 class="titre">Nos points de vente</h2>
                </div>
            </div>
            <div class="boutiques">
                @foreach($stores as $store)
                <div class="boutique">
                    <div class="boutique-visuel">
                        @if($store->image_url)
                            <img src="{{ $store->image_url }}" alt="{{ $store->name }}" loading="lazy"
                                 onerror="this.outerHTML='<i class=&quot;fas fa-store&quot;></i>'">
                        @else
                            <i class="fas fa-store" aria-hidden="true"></i>
                        @endif
                    </div>
                    <div class="boutique-corps">
                        <h3>{{ $store->name }}</h3>
                        @if($store->address)<p><i class="fas fa-map-marker-alt mr-1" style="color:var(--or)"></i>{{ $store->address }}</p>@endif
                        @if($store->description)<p>{{ $store->description }}</p>@endif
                        @if($store->manager)<p>Responsable : {{ $store->manager }}</p>@endif
                        @if($store->phone)<a href="tel:{{ $store->phone }}"><i class="fas fa-phone mr-1"></i>{{ $store->phone }}</a>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ══════════ CONTACT ET NEWSLETTER ══════════ --}}
    <section id="contact" class="final">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="final-grille">
                <div>
                    <span class="label">Contact</span>
                    <h2>Un devis, un conseil, une livraison ?</h2>
                    <div class="filet-or mt-4" style="max-width:220px">◆</div>
                    <div class="coordonnees">
                        <div><i class="fas fa-map-marker-alt" aria-hidden="true"></i><div><small>Adresse</small><span>{{ $company?->address ?: 'Madina, Conakry' }}</span></div></div>
                        @if($company?->phone)
                        <div><i class="fas fa-phone-alt" aria-hidden="true"></i><div><small>Téléphone</small>
                            <a href="tel:+{{ $company->phoneInternational() }}">{{ $company->phone }}</a>
                            @if($company->phone_secondary) · <a href="tel:+{{ $company->phoneSecondaryInternational() }}">{{ $company->phone_secondary }}</a>@endif
                        </div></div>
                        @endif
                        @if($company?->email)
                        <div><i class="fas fa-envelope" aria-hidden="true"></i><div><small>E-mail</small><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></div></div>
                        @endif
                    </div>
                    <a href="{{ route('contact') }}" class="btn-or"><i class="fas fa-paper-plane" aria-hidden="true"></i> Envoyer un message</a>
                </div>
                <div class="boite">
                    <span class="label">Nouveautés et promotions</span>
                    <p>Recevez nos arrivages et nos offres sur la peinture, l'outillage et le matériel électrique.</p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST">
                        @csrf
                        <label for="newsletter-email" class="sr-only">Votre e-mail</label>
                        <input id="newsletter-email" type="email" name="email" placeholder="Votre e-mail" required>
                        <button type="submit">S'inscrire</button>
                    </form>
                    @if(session('newsletter_success'))
                        <p style="color:#D9E4B8;margin:12px 0 0;">{{ session('newsletter_success') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @include('partials.storefront-footer')

    <script>window.PRICING_TIERS_ENABLED = @json(\App\Support\PricingTiers::enabled());</script>
    <script src="{{ asset('assets/js/pricing-tiers.js') }}"></script>
    <script>
        {{-- Données injectées depuis le contrôleur : name, category_name, price,
             old_price (nullable), discount (nullable), rating, image (nullable) --}}
        const PROMO         = @json($promoProducts ?? []);
        const BEST_SELLERS  = @json($bestSellers ?? []);
        const TOP_RATED     = @json($topRated ?? []);
        const TRENDING      = @json($trending ?? []);

        function echapper(texte) {
            return String(texte ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        /* ── Rendu d'une carte produit (mêmes classes que la grille) ── */
        function renderCard(p) {
            const stars    = '★'.repeat(Math.floor(p.rating)) + (p.rating % 1 >= 0.5 ? '½' : '');
            const isPromo  = !!p.old_price;
            const badgeTxt = isPromo ? `-${p.discount}%` : 'Top vente';
            const badgeCls = isPromo ? 'badge-promo' : 'badge-best';
            const oldHTML  = p.old_price ? `<span class="prod-price-old">${Number(p.old_price).toLocaleString('fr-FR')} GNF</span>` : '';
            const imgHTML  = p.image
                ? `<img src="${echapper(p.image)}" alt="${echapper(p.name)}" loading="lazy" onerror="this.onerror=null;this.replaceWith(Object.assign(document.createElement('span'),{className:'no-img',innerHTML:'<i class=&quot;fas fa-toolbox&quot;></i>'}))">`
                : `<span class="no-img"><i class="fas fa-toolbox"></i></span>`;
            const tierHTML = displayTiersFor(p.price).map(t => `
                <div class="tier-cell">
                    <div class="tier-qty">${t.qty}+</div>
                    <div class="tier-price">${Number(t.price).toLocaleString('fr-FR')}</div>
                    ${t.percent > 0 ? `<div class="tier-badge">-${t.percent}%</div>` : ''}
                </div>`).join('');
            return `<div class="prod-card" onclick="window.location='/product/${p.id}'">
                <div class="prod-img">
                    <span class="prod-badge ${badgeCls}">${badgeTxt}</span>
                    ${imgHTML}
                </div>
                <div class="prod-info">
                    <div class="prod-cat-tag">${echapper(p.category_name)}</div>
                    <div class="prod-name">${echapper(p.name)}</div>
                    <div class="prod-stars">${stars} <span style="color:#9A9C8A;font-size:11px;">${parseFloat(p.rating).toFixed(1)}</span></div>
                    ${tierHTML ? `<div class="tier-table"><div class="tier-label">Tarifs de gros — prix unitaire</div><div class="tier-grid">${tierHTML}</div></div>` : ''}
                    <div class="prod-qty" onclick="event.stopPropagation()">
                        <span class="qty-label">Quantité</span>
                        <button type="button" class="qty-btn" onclick="changeQty(this,-1)" aria-label="Diminuer la quantité">−</button>
                        <input type="number" class="qty-input" min="1" value="1" data-base-price="${p.price}" onchange="updateCardPrice(this)" aria-label="Quantité">
                        <button type="button" class="qty-btn" onclick="changeQty(this,1)" aria-label="Augmenter la quantité">+</button>
                    </div>
                    <div class="prod-price-row">
                        <span class="prod-price" data-computed-price>${Number(p.price).toLocaleString('fr-FR')} GNF</span><span class="prod-unite">/ ${p.unite || 'pièce'}</span>
                        ${oldHTML}
                    </div>
                    <div class="prod-btns">
                        <button type="button" class="prod-btn" onclick="event.stopPropagation();addToCartWithQty(${p.id}, this)"><i class="fas fa-shopping-bag mr-1"></i> Panier</button>
                        <button type="button" class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty(${p.id}, this)">Commander</button>
                    </div>
                </div>
            </div>`;
        }

        /* ── Toast ── */
        function showToast(msg, type = 'success') {
            document.getElementById('cart-toast')?.remove();
            const toast = document.createElement('div');
            toast.id = 'cart-toast';
            toast.setAttribute('role', 'status');
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${msg}</span>`;
            toast.style.cssText = `
                position:fixed; bottom:28px; right:28px; z-index:9999;
                display:flex; align-items:center; gap:12px; padding:14px 20px; border-radius:10px;
                background:${type === 'success' ? '#3E4C21' : '#B3261E'}; color:#fff;
                border-left:4px solid ${type === 'success' ? '#C7A150' : '#fff'};
                font-size:14px; font-weight:600; font-family:'Source Sans 3',sans-serif;
                box-shadow:0 12px 30px rgba(0,0,0,.2); transform:translateY(80px); opacity:0;
                transition:transform .35s cubic-bezier(.34,1.56,.64,1), opacity .3s;`;
            document.body.appendChild(toast);
            requestAnimationFrame(() => { toast.style.transform = 'translateY(0)'; toast.style.opacity = '1'; });
            setTimeout(() => {
                toast.style.transform = 'translateY(80px)'; toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 350);
            }, 2800);
        }

        function changeQty(btn, delta) {
            const input = btn.closest('.prod-qty').querySelector('.qty-input');
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
            const qty = parseInt(btn.closest('.prod-card')?.querySelector('.qty-input')?.value, 10) || 1;
            addToCart(id, qty);
        }

        function orderNowWithQty(id, btn) {
            const qty = parseInt(btn.closest('.prod-card')?.querySelector('.qty-input')?.value, 10) || 1;
            orderNow(id, qty);
        }

        function majBadgePanier(data) {
            const badge = document.getElementById('cart-count');
            if (badge && data.count !== undefined) {
                badge.textContent = data.count;
                badge.style.transform = 'scale(1.4)';
                setTimeout(() => badge.style.transform = 'scale(1)', 300);
            }
        }

        function envoyerAuPanier(id, quantity) {
            return fetch(`{{ url('/cart/add') }}/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ quantity })
            }).then(response => {
                if (!response.ok) throw new Error();
                return response.json();
            });
        }

        function addToCart(id, quantity = 1) {
            envoyerAuPanier(id, quantity)
                .then(data => { majBadgePanier(data); showToast('Produit ajouté au panier.'); })
                .catch(() => showToast('Impossible d\'ajouter ce produit. Réessayez.', 'error'));
        }

        function orderNow(id, quantity = 1) {
            envoyerAuPanier(id, quantity)
                .then(data => {
                    majBadgePanier(data);
                    showToast('Produit ajouté. Redirection…');
                    @if(auth()->check() && auth()->user()->isCustomer())
                        setTimeout(() => { window.location.href = '{{ route("checkout") }}'; }, 500);
                    @else
                        setTimeout(() => { window.location.href = '{{ route("otp.login") }}?product_id=' + id; }, 500);
                    @endif
                })
                .catch(() => showToast('Impossible de passer la commande. Réessayez.', 'error'));
        }

        /* ── Défilement doux vers les ancres ── */
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const cible = document.querySelector(a.getAttribute('href'));
                if (!cible) return;
                e.preventDefault();
                cible.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            });
        });

        /* ── Sélections : remplissage et onglets ── */
        (function () {
            const donnees = { 'row-promo': PROMO, 'row-best-sellers': BEST_SELLERS, 'row-top-rated': TOP_RATED, 'row-trending': TRENDING };
            Object.entries(donnees).forEach(([id, data]) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.innerHTML = data.length
                    ? data.map(renderCard).join('')
                    : '<p style="color:#66695A;padding:12px;">Aucun produit dans cette sélection pour le moment.</p>';
            });

            const onglets = Array.from(document.querySelectorAll('.onglet'));
            function activer(onglet) {
                onglets.forEach(o => {
                    const actif = o === onglet;
                    o.setAttribute('aria-selected', actif ? 'true' : 'false');
                    o.tabIndex = actif ? 0 : -1;
                    document.getElementById(o.getAttribute('aria-controls')).hidden = !actif;
                });
            }
            onglets.forEach((o, i) => {
                o.addEventListener('click', () => activer(o));
                o.addEventListener('keydown', e => {
                    if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                    const suivant = onglets[(i + (e.key === 'ArrowRight' ? 1 : -1) + onglets.length) % onglets.length];
                    activer(suivant);
                    suivant.focus();
                });
            });
        })();
    </script>
</body>
</html>
