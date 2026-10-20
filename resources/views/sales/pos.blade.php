<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .total {
                font-size: 18px;
                font-weight: bold;
                margin-top: 20px;
            }

            .product-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin: 8px 0;
                padding: 12px;
                background: #fff;
                border: 1px solid #E6E9D5;
                border-radius: 10px;
                box-shadow: 0 1px 3px rgba(28,35,13, 0.06);
                transition: box-shadow .15s ease, transform .15s ease;
                animation: rowIn .25s ease;
            }

            .product-row:hover {
                box-shadow: 0 4px 10px rgba(28,35,13, 0.12);
            }

            @keyframes rowIn {
                from { opacity: 0; transform: translateY(-6px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            .product-row .quantity-set,
            .product-row .price,
            .product-row .row-total {
                margin-right: 10px;
            }

            .row-remove-btn {
                background-color: #fdecea;
                color: #3E4C21;
                border: none;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color .15s ease, color .15s ease;
            }

            .row-remove-btn:hover {
                background-color: #3E4C21;
                color: #fff;
            }

            .qty-stepper {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .qty-stepper button {
                width: 28px;
                height: 28px;
                border: 1px solid #CED5AE;
                background: #F4F5EC;
                color: #3E4C21;
                border-radius: 6px;
                font-weight: bold;
                line-height: 1;
                cursor: pointer;
                transition: background-color .15s ease;
            }

            .qty-stepper button:hover {
                background-color: #3E4C21;
                color: #fff;
            }

            .qty-stepper input {
                width: 50px !important;
                text-align: center;
            }

            #emptyCartState {
                text-align: center;
                padding: 40px 10px;
                color: #66695A;
            }

            #emptyCartState i {
                font-size: 42px;
                color: #CED5AE;
                margin-bottom: 10px;
                display: block;
            }

            #cartCountBadge {
                background: #3E4C21;
                color: #fff;
                border-radius: 999px;
                font-size: 12px;
                padding: 2px 9px;
                margin-left: 8px;
                vertical-align: middle;
            }

            @keyframes addPulse {
                0%   { box-shadow: 0 0 0 0 rgba(62,76,33, 0.55); }
                100% { box-shadow: 0 0 0 16px rgba(62,76,33, 0); }
            }

            .product-item.just-added .productset {
                animation: addPulse .5s ease-out;
            }

            .stock-badge {
                display: inline-block;
                font-size: 11px;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 999px;
                margin-top: 4px;
            }

            .stock-badge.stock-ok {
                background: #e8f6ec;
                color: #3E4C21;
            }

            .stock-badge.stock-low {
                background: #fdecea;
                color: #b42318;
            }

            .price-vente {
                color: #3E4C21;
                font-weight: 700;
                font-size: 16px;
            }

            .store-stock-breakdown {
                margin-top: 6px;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }

            .store-pill {
                font-size: 10px;
                font-weight: 600;
                color: #323D1A;
                background: #E6E9D5;
                border-radius: 999px;
                padding: 2px 7px;
                white-space: nowrap;
            }

            /* ── Vente tierce : plusieurs produits, un client, un récapitulatif ── */
            .vt-modal {
                border: 0;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 30px 70px -25px rgba(28,35,13, .45);
            }

            .vt-header {
                background: linear-gradient(135deg, #3E4C21, #1C230D);
                color: #fff;
                border: 0;
                padding: 20px 26px;
                align-items: flex-start;
            }

            .vt-header .modal-title {
                font-weight: 700;
                letter-spacing: -.01em;
            }

            .vt-sous-titre {
                margin: 4px 0 0;
                font-size: 13px;
                color: rgba(255, 255, 255, .72);
            }

            .vt-body {
                background: #F9F9F3;
                padding: 22px 26px;
            }

            .vt-bloc {
                background: #fff;
                border: 1px solid #e6ece9;
                border-radius: 12px;
                padding: 18px 20px;
                margin-bottom: 16px;
            }

            .vt-bloc--recap {
                background: linear-gradient(180deg, #fff, #f4faf6);
            }

            .vt-bloc-tete {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .vt-bloc-tete h6 {
                margin: 0;
                font-size: 14px;
                font-weight: 700;
                color: #12261c;
                letter-spacing: .02em;
                text-transform: uppercase;
                flex: 1;
            }

            .vt-num {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background: #3E4C21;
                color: #fff;
                font-size: 12px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            /* Bascule client existant / nouveau */
            .vt-choix {
                display: inline-flex;
                background: #eef4f0;
                border-radius: 999px;
                padding: 3px;
                margin-bottom: 12px;
            }

            .vt-choix-btn {
                border: 0;
                background: transparent;
                color: #55665d;
                font-size: 13px;
                font-weight: 600;
                padding: 7px 18px;
                border-radius: 999px;
                transition: background .18s ease, color .18s ease;
            }

            .vt-choix-btn.actif {
                background: #fff;
                color: #3E4C21;
                box-shadow: 0 2px 6px -2px rgba(28,35,13, .25);
            }

            .vt-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #55665d;
                margin-bottom: 4px;
            }

            .vt-opt {
                font-weight: 400;
                color: #93a49b;
            }

            .vt-champ {
                border: 1px solid #dbe4df;
                border-radius: 9px;
                padding: 9px 12px;
                font-size: 14px;
                color: #12261c;
                background: #fff;
                width: 100%;
                transition: border-color .15s ease, box-shadow .15s ease;
            }

            .vt-champ:focus {
                outline: 0;
                border-color: #3E4C21;
                box-shadow: 0 0 0 3px rgba(62,76,33, .13);
            }

            .vt-champ.vt-invalide {
                border-color: #b42318;
                box-shadow: 0 0 0 3px rgba(180, 35, 24, .12);
            }

            .vt-aide {
                margin: 8px 0 0;
                font-size: 12px;
                color: #93a49b;
            }

            /* Lignes produit */
            .vt-btn-ajout {
                border: 1px dashed #3E4C21;
                background: #F4F5EC;
                color: #3E4C21;
                font-size: 13px;
                font-weight: 600;
                border-radius: 9px;
                padding: 7px 14px;
                transition: background .18s ease;
            }

            .vt-btn-ajout:hover {
                background: #E6E9D5;
            }

            .vt-ligne {
                border: 1px solid #e6ece9;
                border-radius: 11px;
                padding: 14px 16px 10px;
                margin-bottom: 12px;
                background: #fcfdfc;
                position: relative;
            }

            .vt-ligne-tete {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
            }

            .vt-ligne-num {
                font-size: 12px;
                font-weight: 700;
                color: #3E4C21;
                background: #e8f6ec;
                border-radius: 999px;
                padding: 3px 11px;
            }

            .vt-btn-retirer {
                border: 0;
                background: transparent;
                color: #b42318;
                font-size: 13px;
                padding: 4px 8px;
                border-radius: 7px;
                transition: background .15s ease;
            }

            .vt-btn-retirer:hover {
                background: #fdecea;
            }

            .vt-btn-plus {
                border: 0;
                background: #3E4C21;
                color: #fff;
                border-radius: 9px;
                padding: 0 13px;
                flex-shrink: 0;
            }

            .vt-ligne-pied {
                margin-top: 8px;
                min-height: 18px;
            }

            .vt-ligne-marge {
                font-size: 12px;
                font-weight: 600;
            }

            .vt-ligne-marge.ok    { color: #3E4C21; }
            .vt-ligne-marge.perte { color: #b42318; }

            /* Récapitulatif */
            .vt-recap {
                border: 1px solid #e0eae4;
                border-radius: 11px;
                overflow: hidden;
                background: #fff;
            }

            .vt-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13.5px;
            }

            .vt-table thead th {
                background: #f2f7f4;
                color: #55665d;
                font-size: 11.5px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
                padding: 9px 14px;
                border-bottom: 1px solid #e0eae4;
            }

            .vt-table td {
                padding: 10px 14px;
                border-bottom: 1px solid #f0f4f2;
                color: #12261c;
            }

            .vt-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .vt-table .vt-c { text-align: center; }
            .vt-table .vt-d { text-align: right; }

            .vt-table .vt-vide td {
                text-align: center;
                color: #93a49b;
                font-style: italic;
                padding: 18px;
            }

            .vt-totaux {
                border-top: 1px solid #e0eae4;
                background: #f8fbf9;
                padding: 12px 14px;
            }

            .vt-ligne-total {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                padding: 4px 0;
                font-size: 13.5px;
                color: #55665d;
            }

            .vt-ligne-total strong {
                color: #12261c;
                font-weight: 600;
            }

            .vt-marge { color: #3E4C21 !important; }

            .vt-ligne-total--final {
                margin-top: 6px;
                padding-top: 10px;
                border-top: 1px dashed #d4e2da;
                font-size: 15px;
            }

            .vt-ligne-total--final strong {
                font-size: 20px;
                font-weight: 800;
                color: #3E4C21;
            }

            /* Pied et messages */
            .vt-footer {
                border: 0;
                background: #fff;
                padding: 16px 26px;
                box-shadow: 0 -1px 0 #e6ece9;
            }

            .vt-btn-annuler {
                background: #eef2f0;
                color: #55665d;
                font-weight: 600;
                border-radius: 9px;
                padding: 10px 20px;
            }

            .vt-btn-valider {
                background: linear-gradient(135deg, #3E4C21, #323D1A);
                color: #fff;
                font-weight: 700;
                border-radius: 9px;
                padding: 10px 26px;
                box-shadow: 0 8px 18px -8px rgba(62,76,33, .7);
            }

            .vt-btn-valider:hover { color: #fff; filter: brightness(1.07); }
            .vt-btn-valider:disabled { opacity: .6; box-shadow: none; }

            .vt-erreur {
                margin-top: 4px;
                background: #fdecea;
                border: 1px solid #f5c6c2;
                color: #8f1d14;
                border-radius: 10px;
                padding: 11px 14px;
                font-size: 13.5px;
            }

            .vt-erreur ul { margin: 6px 0 0; padding-left: 18px; }

            @media (max-width: 767px) {
                .vt-body { padding: 16px; }
                .vt-bloc { padding: 14px; }
                .vt-table { font-size: 12.5px; }
                .vt-table thead th, .vt-table td { padding: 8px 10px; }
            }

            .pos-pagination {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 6px;
                margin: 18px 0 10px;
            }

            .pos-pagination button {
                min-width: 34px;
                height: 34px;
                border: 1px solid #CED5AE;
                background: #fff;
                color: #3E4C21;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: background-color .15s ease, color .15s ease;
            }

            .pos-pagination button:hover {
                background: #F4F5EC;
            }

            .pos-pagination button.active {
                background: #3E4C21;
                color: #fff;
                border-color: #3E4C21;
            }

            .pos-pagination button:disabled {
                opacity: .4;
                cursor: not-allowed;
            }

            .product-item.pos-hidden {
                display: none !important;
            }

            .tabs_wrapper ul.tabs li.active,
            .productset .check-product {
                background-color: #3E4C21 !important;
            }

            .productset.active,
            .productset:hover {
                border-color: #3E4C21 !important;
            }

            .product-details:hover {
                background: #3E4C21;
            }

            .product-details.active {
                background-color: #3E4C21 !important;
            }

            .btn-totallabel,
            .btn-scanner-set {
                background-color: #3E4C21 !important;
                color: #fff !important;
            }

            /* .setvalue, .totalitem et .setvaluecash n'existent plus sur cette page :
               le bloc des totaux a été remplacé par .pos-totals. */

            .owl-product .owl-nav button {
                color: #3E4C21 !important;
            }

            .cat-chip {
                display: inline-block;
                background: #E6E9D5;
                color: #323D1A;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
                padding: 2px 8px;
                border-radius: 999px;
                margin-bottom: 4px;
            }

            .productsetimg {
                background: #F4F5EC;
                border-radius: 8px;
                position: relative;
                overflow: hidden;
            }

            .productsetimg.img-fallback img {
                opacity: 0;
            }

            .productsetimg.img-fallback::after {
                content: "\f03e";
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 32px;
                color: #ADB97E;
            }

            .productset {
                transition: box-shadow .15s ease, transform .15s ease;
            }

            .productset:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px rgba(62,76,33, 0.15) !important;
            }

            .tab-item:not(.active) .product-details {
                background: #F9F9F3;
                border: 1px solid #CED5AE;
                transition: background-color .15s ease, box-shadow .15s ease;
            }

            .tab-item:not(.active):hover .product-details {
                background: #F4F5EC;
                box-shadow: 0 2px 8px rgba(62,76,33, 0.1);
            }

            .btn-adds {
                border-color: #3E4C21 !important;
                color: #3E4C21 !important;
            }

            .btn-adds:hover {
                background: #3E4C21 !important;
                color: #fff !important;
            }

            .card-order {
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            }

            .check-product {
                display: none;
                position: absolute;
                top: 10px;
                right: 10px;
                background-color: #3E4C21;
                color: white;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                font-weight: bold;
                animation: checkPop .2s ease;
            }

            @keyframes checkPop {
                from { transform: scale(0); }
                to   { transform: scale(1); }
            }

            .product-item.selected .check-product {
                display: flex;
            }
            .qty-price-group {
                display: flex;
                flex-direction: row;
                gap: 16px;
                flex-wrap: wrap;
            }

            .qty-price-group .form-group {
                flex: 1;
                min-width: 120px;
            }

            @media (max-width: 768px) {
                .qty-price-group {
                    flex-direction: column;
                }
            }

            /* ══════════════════════════════════════
               Colonne de droite : récapitulatif, client, paiement
            ══════════════════════════════════════ */
            .pos-section-title {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #3E4C21;
                margin: 22px 0 10px;
                padding-bottom: 7px;
                border-bottom: 1px solid #E6E9D5;
            }

            .pos-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #66695A;
                margin-bottom: 5px;
            }
            .pos-optional {
                font-weight: 400;
                color: #9A9C8A;
            }

            /* Les totaux étaient une liste indifférenciée : le montant à payer,
               qui est l'information que le vendeur annonce au client, se noyait
               au milieu du sous-total et des frais. */
            .pos-totals {
                background: #F4F5EC;
                border: 1px solid #E6E9D5;
                border-radius: 12px;
                padding: 12px 14px;
                margin-top: 4px;
            }
            .pos-total-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 5px 0;
            }
            .pos-total-row span,
            .pos-total-row label {
                font-size: 13px;
                color: #66695A;
                margin: 0;
            }
            .pos-total-row strong {
                font-size: 15px;
                font-weight: 600;
                color: #1F2414;
            }
            .pos-total-input {
                width: 110px;
                height: 34px;
                text-align: right;
                font-size: 13px;
            }
            .pos-total-row--final {
                margin-top: 6px;
                padding-top: 11px;
                border-top: 1px dashed #ADB97E;
            }
            .pos-total-row--final span {
                font-size: 14px;
                font-weight: 700;
                color: #1F2414;
            }
            .pos-total-row--final strong {
                font-size: 22px;
                font-weight: 800;
                color: #3E4C21;
                line-height: 1.1;
            }

            .btn-pos-validate {
                background: linear-gradient(135deg, #3E4C21, #323D1A);
                color: #fff;
                font-weight: 700;
                font-size: 15px;
                padding: 13px;
                border: none;
                border-radius: 10px;
                margin-top: 6px;
                transition: box-shadow .2s ease, transform .1s ease;
            }
            .btn-pos-validate:hover {
                color: #fff;
                box-shadow: 0 10px 22px -10px rgba(62,76,33,.9);
                transform: translateY(-1px);
            }
            .btn-pos-validate:active { transform: translateY(0); }

            /* ── Barre d'outils : recherche + action ── */
            .pos-toolbar {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
                margin-bottom: 18px;
            }
            .pos-search {
                position: relative;
                flex: 1 1 280px;
                min-width: 220px;
                max-width: 440px;
            }
            .pos-search input {
                padding-left: 38px;
                padding-right: 34px;
                height: 42px;
            }
            .pos-search input:focus {
                border-color: #6B7A35;
                box-shadow: 0 0 0 3px rgba(107,122,53,.14);
            }
            .pos-search-icon {
                position: absolute; left: 14px; top: 50%;
                transform: translateY(-50%);
                color: #9A9C8A; font-size: 13px; pointer-events: none;
            }
            .pos-search-clear {
                position: absolute; right: 12px; top: 50%;
                transform: translateY(-50%);
                cursor: pointer; color: #9A9C8A;
            }
            .pos-search-clear:hover { color: #66695A; }

            /* btn-warning (jaune Bootstrap) jurait avec la charte verte */
            .btn-pos-secondary {
                height: 42px;
                white-space: nowrap;
                background: #fff;
                color: #3E4C21;
                border: 1.5px solid #ADB97E;
                border-radius: 10px;
                font-weight: 600;
                font-size: 13.5px;
                padding: 0 18px;
                transition: background .18s ease, border-color .18s ease;
            }
            .btn-pos-secondary:hover {
                background: #F4F5EC;
                border-color: #6B7A35;
                color: #323D1A;
            }

            /* Ligne du panier : l'origine du stock et le bloc « vente pour un tiers »
               prennent toute une ligne ; sans retour à la ligne, la rangée flex les
               écrasait à quelques dizaines de pixels. */
            #product-list .product-lists { flex-wrap: wrap; }
            #product-list .product-lists > li.origin-group,
            #product-list .product-lists > li[style*="width:100%"] { flex: 0 0 100%; max-width: 100%; }
            #product-list .origin-select { font-size: 13px; }
        </style>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')

            <div class="page-wrapper ms-0">
                <div class="content">
                    <div class="row">
                        <div class="col-lg-7 col-md-12 col-sm-12 tabs_wrapper">
                            <div class="page-header ">
                                <div class="page-title">
                                    <h4>Point de vente</h4>
                                    <h6>Enregistrer une vente en caisse</h6>
                                </div>
                            </div>
                            {{-- Recherche et action sur une seule ligne : elles étaient
                                 empilées, ce qui repoussait la grille produits vers le bas. --}}
                            <div class="pos-toolbar">
                                <div class="pos-search">
                                    <i class="fa fa-search pos-search-icon"></i>
                                    <input type="text" class="form-control" id="posSearchInput" placeholder="Rechercher un produit ou une catégorie…">
                                    <span id="clearSearchBtn" class="pos-search-clear" style="display:none;">
                                        <i class="fa fa-times-circle"></i>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-pos-secondary" data-bs-toggle="modal" data-bs-target="#thirdPartyQuickModal">
                                    <i class="fa fa-plus me-1"></i> Vente Eclaire
                                </button>
                            </div>
                            @include('layouts.flash')
                            <ul class="tabs owl-carousel owl-theme owl-product border-0">
                                <li class="tab-item active" data-tab="all">
                                    <div class="product-details">
                                        <h6>Tous</h6>
                                    </div>
                                </li>
                                @foreach ($categories as $item)
                                <li class="tab-item" data-tab="{{ $item->slug }}">
                                    <div class="product-details">
                                        <h6>{{ $item->slug }}</h6>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            <div class="tabs_container">
                                <div class="tab_content active" data-tab="all">
                                    <div class="row product-grid">
                                        @foreach ($produits as $dataItem)
                                            @include('sales.partials.pos-product-card', ['dataItem' => $dataItem])
                                        @endforeach
                                    </div>
                                    <div class="pos-pagination"></div>
                                </div>
                                @foreach ($categories as $category)
                                <div class="tab_content" data-tab="{{ $category->slug }}">
                                    <div class="row product-grid">
                                        @foreach ($produits->filter(fn($p) => $p->categories->contains('slug', $category->slug)) as $dataItem)
                                            @include('sales.partials.pos-product-card', ['dataItem' => $dataItem])
                                        @endforeach
                                    </div>
                                    <div class="pos-pagination"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- col-md-12 : sur tablette la colonne de gauche occupe déjà toute
                             la largeur, un col-md-5 ici cassait la grille (12 + 5 = 17). --}}
                        <div class="col-lg-5 col-md-12 col-sm-12">
                            <div class="order-list">
                                <div class="orderid">
                                    <h4>Panier <span id="cartCountBadge" style="display:none;">0</span></h4>
                                    <h5>Facture n° {{ $numeroFacture }}</h5>
                                </div>
                            </div>
                            <div class="card card-order">
                                <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data" id="formVente">
                                    @csrf
                                    <div class="card-body pt-0">
                                        <div class="product-table" id="product-list">
                                            <h5>Articles sélectionnés</h5>
                                            <div id="emptyCartState">
                                                <i class="fa fa-shopping-basket"></i>
                                                Panier vide — cliquez sur un produit pour l'ajouter
                                            </div>
                                        </div>
                                        {{-- Produit que la boutique n'a pas : pris chez un partenaire et
                                             vendu dans la même facture que les autres articles. --}}
                                        <button type="button" class="btn btn-pos-secondary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#partnerLineModal">
                                            <i class="fa fa-handshake me-1"></i> Produit pris chez un partenaire
                                        </button>
                                    </div>
                                    <div class="split-card">
                                    </div>
                                    <div class="card-body pt-0 pb-3">

                                        {{-- ── Récapitulatif ── --}}
                                        <div class="pos-totals">
                                            <div class="pos-total-row">
                                                <span>Sous-total</span>
                                                <strong id="subtotal">0.00</strong>
                                            </div>
                                            <div class="pos-total-row">
                                                <label for="tax">Autres frais</label>
                                                <input type="text" id="tax" class="form-control pos-total-input" value="0" inputmode="decimal">
                                            </div>
                                            <div class="pos-total-row pos-total-row--final">
                                                <span>Total à payer</span>
                                                <strong id="finalTotal">0.00</strong>
                                                <input type="hidden" id="final_total" name="final_total">
                                            </div>
                                        </div>

                                        {{-- ── Client et magasin ── --}}
                                        <div class="pos-section-title">Client et magasin</div>
                                        <input type="hidden" name="numeroFacture" value="{{ $numeroFacture }}">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label for="customer_id" class="pos-label">Client</label>
                                                <select name="customer_id" id="customer_id" class="form-control">
                                                    <option value="">Sélectionner un client</option>
                                                    @foreach ($customers as $item)
                                                        <option value="{{ $item->id }}" {{ old('customer_id') == $item->id ? 'selected' : '' }}>
                                                            {{ $item->customerName.' '.$item->mark }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12" id="addCustomerButton">
                                                <a href="javascript:void(0);" class="btn btn-adds w-100" data-bs-toggle="modal" data-bs-target="#create">
                                                    <i class="fa fa-plus me-2"></i>Nouveau client
                                                </a>
                                            </div>
                                            <div class="col-12">
                                                {{-- Magasin qui facture et encaisse (caisse). Distinct de
                                                     l'origine du stock de chaque ligne : une vente du siège
                                                     peut sortir de l'Entrepôt 5. Prérempli d'après les lignes. --}}
                                                <label for="store_id" class="pos-label">Magasin de vente (caisse)</label>
                                                <select name="store_id" id="store_id" class="form-control" {{ $userStoreId ? 'readonly' : '' }}>
                                                    <option value="">Automatique (d'après l'origine des produits)</option>
                                                    @if ($userStoreId)
                                                        <option value="{{ $userStoreId }}" selected>{{ App\Models\Store::find($userStoreId)->store_name }}</option>
                                                    @else
                                                        @foreach ($boutiques as $item)
                                                            <option value="{{ $item->id }}">{{ $item->store_name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <small class="text-muted" style="font-size:11.5px;">
                                                    La facture et l'encaissement sont rattachés à ce magasin ; le stock est retiré de l'origine choisie sur chaque ligne.
                                                </small>
                                            </div>
                                        </div>

                                        {{-- ── Paiement ── --}}
                                        <div class="pos-section-title">Paiement</div>
                                        {{-- Chaque champ dans sa colonne : « Notes » et le bouton étaient
                                             posés directement dans le .row, sans col-*, ce qui les sortait
                                             de la grille Bootstrap. --}}
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label for="avance" class="pos-label">Avance versée</label>
                                                <input type="text" id="avance" name="avance" value="0" class="form-control" inputmode="decimal">
                                            </div>
                                            <div class="col-6">
                                                <label for="paid_by" class="pos-label">Mode de paiement</label>
                                                <select name="paid_by" id="paid_by" class="form-control">
                                                    <option value="cash">Espèces</option>
                                                    <option value="check">Chèque</option>
                                                    <option value="orange money">Orange Money</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="notes" class="pos-label">Notes <span class="pos-optional">(facultatif)</span></label>
                                                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Précision sur la vente, la livraison…"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-pos-validate w-100">
                                                    <i class="fa fa-check-circle me-2"></i>Valider la vente
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="create" tabindex="-1" aria-labelledby="create" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau client</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="addCustomerForm">
                            <div class="row">
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="customerName">Customer Name</label>
                                        <input type="text" name="customerName" id="customerName" required>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="mark">Customer Mark</label>
                                        <input type="text" name="mark" id="mark" required>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="text" name="email" id="email">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="tel">Phone</label>
                                        <input type="text" name="tel" id="tel">
                                    </div>
                                </div>
                                <div class="col-lg-12 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" cols="30" rows="10"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-submit me-2">Submit</button>
                                <a class="btn btn-cancel" data-bs-dismiss="modal">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="delete" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Retirer l'article</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="delete-order">
                            <img src="{{ asset('assets/img/icons/close-circle1.svg') }}" alt="img">
                        </div>
                        <div class="para-set text-center">
                            <p>The current order will be deleted as no payment has been <br> made so far.</p>
                        </div>
                        <div class="col-lg-12 text-center">
                            <a class="btn btn-danger me-2">Yes</a>
                            <a class="btn btn-cancel" data-bs-dismiss="modal">No</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="thirdPartyQuickModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content vt-modal">
                    <div class="modal-header vt-header">
                        <div>
                            <h5 class="modal-title">Vente tierce</h5>
                            <p class="vt-sous-titre">Des produits fournis par un partenaire, revendus à votre client.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body vt-body">
                        <form id="quickThirdPartyForm" novalidate>
                            @csrf

                            {{-- ─── Client ─── --}}
                            <section class="vt-bloc">
                                <div class="vt-bloc-tete">
                                    <span class="vt-num">1</span>
                                    <h6>Boutique et client</h6>
                                </div>

                                {{-- La boutique se choisit ici : la fenetre ne depend plus du
                                     panneau de caisse situe derriere elle, qui pouvait etre vide. --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="vt-label">Boutique de vente</label>
                                        <select id="vtBoutique" class="form-control vt-champ">
                                            @if ($userStoreId)
                                                <option value="{{ $userStoreId }}" selected>{{ App\Models\Store::find($userStoreId)?->store_name }}</option>
                                            @else
                                                <option value="">Choisir une boutique…</option>
                                                @foreach ($boutiques as $item)
                                                    <option value="{{ $item->id }}">{{ $item->store_name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                <div class="vt-choix" role="tablist">
                                    <button type="button" class="vt-choix-btn actif" data-mode="existant">Client existant</button>
                                    <button type="button" class="vt-choix-btn" data-mode="nouveau">Nouveau client</button>
                                </div>

                                <div data-panneau="existant">
                                    <select id="vtClientExistant" class="form-control vt-champ">
                                        <option value="">Choisir un client…</option>
                                        @foreach($customers as $item)
                                            <option value="{{ $item->id }}">{{ $item->customerName }}{{ $item->mark ? ' — '.$item->mark : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div data-panneau="nouveau" hidden>
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <label class="vt-label">Nom du client</label>
                                            <input type="text" id="vtNouveauNom" class="form-control vt-champ" placeholder="Ex. Mamadou Diallo">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="vt-label">Téléphone</label>
                                            <input type="text" id="vtNouveauTel" class="form-control vt-champ" placeholder="622 00 00 00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="vt-label">Adresse</label>
                                            <input type="text" id="vtNouveauAdresse" class="form-control vt-champ" placeholder="Madina, Conakry">
                                        </div>
                                    </div>
                                    <p class="vt-aide">Le code client est attribué automatiquement.</p>
                                </div>
                            </section>

                            {{-- ─── Produits ─── --}}
                            <section class="vt-bloc">
                                <div class="vt-bloc-tete">
                                    <span class="vt-num">2</span>
                                    <h6>Produits</h6>
                                    <button type="button" id="vtAjouterLigne" class="vt-btn-ajout">
                                        <i class="fa fa-plus"></i> Ajouter un produit
                                    </button>
                                </div>

                                <div id="vtLignes"></div>
                            </section>

                            {{-- ─── Récapitulatif ─── --}}
                            <section class="vt-bloc vt-bloc--recap">
                                <div class="vt-bloc-tete">
                                    <span class="vt-num">3</span>
                                    <h6>Récapitulatif</h6>
                                </div>

                                <div class="vt-recap">
                                    <table class="vt-table">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th class="vt-c">Qté</th>
                                                <th class="vt-d">Prix unitaire</th>
                                                <th class="vt-d">Total</th>
                                                <th class="vt-d">Marge</th>
                                            </tr>
                                        </thead>
                                        <tbody id="vtRecapCorps">
                                            <tr class="vt-vide"><td colspan="5">Aucun produit pour l'instant.</td></tr>
                                        </tbody>
                                    </table>

                                    <div class="vt-totaux">
                                        <div class="vt-ligne-total">
                                            <span>Articles</span>
                                            <strong id="vtNbArticles">0</strong>
                                        </div>
                                        <div class="vt-ligne-total">
                                            <span>Marge attendue</span>
                                            <strong id="vtMarge" class="vt-marge">0 FG</strong>
                                        </div>
                                        <div class="vt-ligne-total vt-ligne-total--final">
                                            <span>Total à payer</span>
                                            <strong id="vtTotal">0 FG</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 mt-3">
                                    <div class="col-md-4">
                                        <label class="vt-label">Avance versée</label>
                                        <input type="number" step="0.01" min="0" id="vtAvance" value="0" class="form-control vt-champ">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="vt-label">Mode de paiement</label>
                                        <select id="vtPaidBy" class="form-control vt-champ">
                                            <option value="cash">Espèces</option>
                                            <option value="orange money">Orange Money</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="vt-label">Notes <span class="vt-opt">(facultatif)</span></label>
                                        <input type="text" id="vtNotes" class="form-control vt-champ" placeholder="Précision sur la vente…">
                                    </div>
                                </div>
                            </section>
                        </form>

                        <div id="vtErreur" class="vt-erreur" hidden></div>
                    </div>

                    <div class="modal-footer vt-footer">
                        <button type="button" class="btn vt-btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" id="saveQuickThirdParty" class="btn vt-btn-valider">
                            Enregistrer la vente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gabarit d'une ligne produit, cloné par le script. --}}
        <template id="vtGabaritLigne">
            <div class="vt-ligne">
                <div class="vt-ligne-tete">
                    <span class="vt-ligne-num"></span>
                    <button type="button" class="vt-btn-retirer" title="Retirer ce produit">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="vt-label">Libellé du produit</label>
                        <input type="text" class="form-control vt-champ" data-champ="libelle" placeholder="Ex. Tuyau PVC 50 mm">
                    </div>
                    <div class="col-md-3">
                        <label class="vt-label">SKU / Référence</label>
                        <input type="text" class="form-control vt-champ" data-champ="sku" placeholder="TRC-001">
                    </div>
                    <div class="col-md-4">
                        <label class="vt-label">Partenaire qui fournit</label>
                        <div class="d-flex" style="gap:6px;">
                            <select class="form-control vt-champ partner-select" data-champ="partner_id" style="flex:1;">
                                <option value="">Choisir un partenaire…</option>
                                @foreach($partners as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}{{ $p->phone ? ' ('.$p->phone.')' : '' }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="vt-btn-plus btn-add-partner" title="Nouveau partenaire">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="vt-label">Prix donné par l'ami</label>
                        <input type="number" step="0.01" min="0" class="form-control vt-champ" data-champ="purchase_price_from_partner" placeholder="0">
                    </div>
                    <div class="col-md-4">
                        <label class="vt-label">Prix de vente au client</label>
                        <input type="number" step="0.01" min="0" class="form-control vt-champ" data-champ="prix" placeholder="0">
                    </div>
                    <div class="col-md-4">
                        <label class="vt-label">Quantité</label>
                        <input type="number" min="1" step="1" value="1" class="form-control vt-champ" data-champ="quantity">
                    </div>
                </div>
                <div class="vt-ligne-pied">
                    <span class="vt-ligne-marge"></span>
                </div>
            </div>
        </template>

        {{-- Ligne tierce dans la vente en cours : produit du catalogue (même sans
             stock) ou nouveau produit, partenaire et prix payé au partenaire. --}}
        <div class="modal fade" id="partnerLineModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background:#F9F9F3;border-bottom:1px solid #CED5AE;">
                        <h6 class="modal-title" style="color:#3E4C21;"><i class="fa fa-handshake me-2"></i>Produit pris chez un partenaire</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" style="font-size:13px;">
                            Pour un produit que vous n'avez pas en stock : il est ajouté à la vente en cours, sur la même facture.
                            Le client ne voit pas d'où il vient ; le montant dû au partenaire est suivi dans Rapports → Ventes tierces.
                        </p>
                        <div class="alert alert-danger py-2" id="plErreur" style="display:none;font-size:13px;"></div>
                        <div class="form-group mb-2">
                            <label>Produit</label>
                            <select id="plProduit" class="form-control">
                                <option value="">Choisir un produit du catalogue…</option>
                                <option value="__nouveau">+ Nouveau produit (absent du catalogue)</option>
                                @foreach ($produitsTiers as $pt)
                                <option value="{{ $pt['id'] }}" data-prix="{{ $pt['prix'] }}" data-unite="{{ $pt['unite'] }}" data-singulier="{{ $pt['singulier'] }}">{{ $pt['libelle'] }} — {{ $pt['unite'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-2" id="plNouveau" style="display:none;">
                            <div class="col-7">
                                <label>Nom du nouveau produit</label>
                                <input type="text" id="plLibelle" class="form-control" maxlength="225" placeholder="Ex. Robinet mélangeur">
                            </div>
                            <div class="col-5">
                                <label>Unité</label>
                                <select id="plUnite" class="form-control">
                                    @foreach (\App\Models\Product::UNITES as $code => $u)
                                    <option value="{{ $code }}">{{ $u['libelle'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label>Partenaire qui fournit</label>
                            <div class="d-flex gap-2">
                                <select id="plPartenaire" class="form-control partner-select" style="flex:1;">
                                    <option value="">Choisir un partenaire…</option>
                                    @foreach ($partners as $partner)
                                    <option value="{{ $partner->id }}">{{ $partner->name }}{{ $partner->phone ? ' ('.$partner->phone.')' : '' }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm" id="plAjoutPartenaire" style="background:#3E4C21;color:#fff;" title="Nouveau partenaire"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <label>Quantité <small class="text-muted" id="plUniteTexte"></small></label>
                                <input type="number" min="1" step="1" value="1" id="plQuantite" class="form-control">
                            </div>
                            <div class="col-4">
                                <label>Payé au partenaire <small class="text-muted">(par unité)</small></label>
                                <input type="number" min="0" step="any" id="plPrixAmi" class="form-control">
                            </div>
                            <div class="col-4">
                                <label>Prix de vente <small class="text-muted">(par unité)</small></label>
                                <input type="number" min="0" step="any" id="plPrix" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-submit" id="plAjouter">Ajouter à la vente</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quickPartnerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background: #F9F9F3; border-bottom: 1px solid #CED5AE;">
                        <h6 class="modal-title" style="color: #3E4C21;">
                            <i class="fa fa-user-plus me-2"></i>Nouveau Partenaire
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="quickPartnerForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 13px; font-weight: 600;">Nom du partenaire *</label>
                                <input type="text" name="name" id="quickPartnerName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 13px; font-weight: 600;">Téléphone</label>
                                <input type="text" name="phone" id="quickPartnerPhone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 13px; font-weight: 600;">Email</label>
                                <input type="email" name="email" id="quickPartnerEmail" class="form-control">
                            </div>
                            <div class="text-danger" id="quickPartnerError" style="font-size: 12px; display:none;"></div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #CED5AE;">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-sm" id="saveQuickPartner" style="background: #3E4C21; color: white;">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
        <script src="{{ asset('assets/js/feather.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('assets/js/dataTables.bootstrap4.min.js') }}"></script>
        <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/sweetalert/sweetalerts.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
        <script src="{{ asset('assets/js/script.js') }}"></script>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productList = document.getElementById('product-list');

                // Dernier verrou avant envoi. Le contrôle faisant foi reste celui du
                // serveur (SaleController::store) : celui-ci évite juste au vendeur de
                // saisir toute une vente pour se la faire refuser à la validation.
                document.getElementById('formVente')?.addEventListener('submit', function (e) {
                    const ruptures = productList.querySelectorAll('.origin-select[data-stock-error="1"]');
                    if (ruptures.length) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Stock insuffisant',
                            html: 'La quantité demandée dépasse le stock de l’emplacement choisi.<br>Réduisez la quantité ou choisissez une autre origine.',
                            confirmButtonText: 'Corriger',
                            confirmButtonColor: '#3E4C21',
                        });
                        ruptures[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    const fautives = productList.querySelectorAll('.price-field[data-below-cost="1"]');
                    if (!fautives.length) return;

                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Prix trop bas',
                        html: fautives.length > 1
                            ? fautives.length + ' produits sont vendus sous leur prix minimum autorisé (prix de vente minimum ou prix de revient).<br>Corrigez les prix en rouge avant de valider.'
                            : 'Un produit est vendu sous son prix minimum autorisé (prix de vente minimum ou prix de revient).<br>Corrigez le prix en rouge avant de valider.',
                        confirmButtonText: 'Corriger',
                        confirmButtonColor: '#3E4C21',
                    });
                    fautives[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    fautives[0].focus();
                });

                let i = 0;
                let targetPartnerSelect = null;
                const partnersList = @json($partners);
                // Stock disponible par produit et par emplacement accessible, et
                // libellés des emplacements : alimentent le choix d'origine de
                // chaque ligne. Le contrôle qui fait foi reste celui du serveur.
                const stockByProduct = @json($stockByProduct);
                const locationLabels = @json($locationLabels);
                // Unité de chaque produit : la quantité saisie et le prix sont
                // directement dans cette unité (carton, douzaine…), sans conversion.
                const unitesByProduct = @json($unitesByProduct);
                function motUnite(productId, n) {
                    const u = unitesByProduct[productId];
                    if (!u) return '';
                    return n > 1 ? u.pluriel : u.singulier;
                }
                // Origine du stock → magasin qui facture (l'entrepôt d'un magasin
                // facture pour ce magasin). Sert à préremplir le magasin de vente
                // tant que le vendeur ne l'a pas choisi lui-même.
                const caisseParOrigine = @json($caisseParOrigine);
                const caisseSelect = document.getElementById('store_id');
                caisseSelect?.addEventListener('change', function () {
                    this.dataset.manuel = this.value ? '1' : '';
                });
                function synchroniserCaisse() {
                    if (!caisseSelect || caisseSelect.dataset.manuel === '1' || caisseSelect.hasAttribute('readonly')) return;
                    const premiere = document.querySelector('#product-list .origin-select:not([disabled])');
                    const caisse = premiere ? caisseParOrigine[premiere.value] : null;
                    if (caisse && caisseSelect.querySelector('option[value="' + caisse + '"]')) {
                        caisseSelect.value = String(caisse);
                    } else if (!premiere) {
                        caisseSelect.value = '';
                    }
                }

                function originOptions(productId) {
                    const rows = stockByProduct[productId] || [];
                    const preferred = document.getElementById('store_id')?.value;
                    if (!rows.length) {
                        return '<option value="" data-available="0" data-fund="">Aucun stock disponible</option>';
                    }
                    // Présélection : le magasin de vente, aux fonds propres de préférence.
                    let choisi = rows.findIndex(r => String(r.id) === String(preferred) && !r.fund);
                    if (choisi < 0) choisi = rows.findIndex(r => String(r.id) === String(preferred));
                    if (choisi < 0) choisi = rows.findIndex(r => !r.fund);
                    if (choisi < 0) choisi = 0;
                    return rows.map(function (r, idx) {
                        // Emplacement — propriétaire (fonds propres ou fonds d'un associé) — stock
                        const label = (locationLabels[r.id] || ('Emplacement #' + r.id)) + ' — ' + r.owner + ' — ' + r.qty.toLocaleString('fr-FR') + ' ' + (motUnite(productId, r.qty) || 'pièces') + ' disponible' + (r.qty > 1 ? 's' : '');
                        return '<option value="' + r.id + '" data-fund="' + (r.fund || '') + '" data-available="' + r.qty + '"' + (idx === choisi ? ' selected' : '') + '>' + label + '</option>';
                    }).join('');
                }
                const quickPartnerModal = new bootstrap.Modal(document.getElementById('quickPartnerModal'));
                const thirdPartyQuickModalEl = document.getElementById('thirdPartyQuickModal');
                const thirdPartyQuickModal = new bootstrap.Modal(thirdPartyQuickModalEl);

                // Bouton "+" à côté de "Partenaire / Ami" dans la modale "Vente Tierce
                // Rapide" : contrairement à celui des lignes de vente normales (câblé
                // dans initializeRowEvents pour chaque ligne ajoutée dynamiquement),
                // celui-ci est statique dans le HTML et n'avait jamais d'écouteur.
                const thirdPartyQuickAddPartnerBtn = thirdPartyQuickModalEl.querySelector('.btn-add-partner');
                if (thirdPartyQuickAddPartnerBtn) {
                    thirdPartyQuickAddPartnerBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        targetPartnerSelect = thirdPartyQuickModalEl.querySelector('.partner-select');
                        document.getElementById('quickPartnerForm').reset();
                        document.getElementById('quickPartnerError').style.display = 'none';
                        quickPartnerModal.show();
                    });
                }

                // Fonction pour peupler les selects de partenaires
                function populatePartnerSelects() {
                    document.querySelectorAll('.partner-select').forEach(select => {
                        const currentValue = select.value;
                        while (select.options.length > 1) {
                            select.remove(1);
                        }
                        partnersList.forEach(partner => {
                            const phoneText = partner.phone ? ' (' + partner.phone + ')' : '';
                            const option = document.createElement('option');
                            option.value = partner.id;
                            option.textContent = partner.name + phoneText;
                            select.appendChild(option);
                        });
                        if (currentValue) {
                            select.value = currentValue;
                        }
                    });
                }

                // Gestion du clic sur les produits
                document.addEventListener('click', function (event) {
                    const item = event.target.closest('.product-item');
                    if (!item) return;

                    i++;
                    const sku = item.getAttribute('data-sku');
                    const product_id = item.getAttribute('data-id');
                    const numeroFacture = item.getAttribute('data-numeroFacture');
                    const productPrice = parseFloat(item.getAttribute('data-price')) || 0;
                    // Prix de revient : plancher absolu, vendre en dessous est refusé.
                    const productCost = parseFloat(item.getAttribute('data-cost')) || 0;
                    // Prix de vente minimum de la fiche produit (0 = non fixé).
                    const productMinPrice = parseFloat(item.getAttribute('data-min-price')) || 0;
                    const libelle = item.querySelector('.productsetcontent h5').textContent;
                    const imageUrl = item.querySelector('.productsetimg img').src;

                    // Second clic sur une carte deja au panier : on retire la ligne.
                    // Auparavant on sortait sans rien faire — la carte restait
                    // surlignee et il n'y avait aucun moyen de revenir en arriere
                    // depuis la grille des produits.
                    const ligneExistante = document.querySelector('#product-list li[data-sku="' + sku + '"]');
                    if (ligneExistante) {
                        ligneExistante.remove();
                        item.classList.remove('selected');
                        updateTotalPrice();
                        return;
                    }

                    item.classList.add('selected', 'just-added');
                    setTimeout(() => item.classList.remove('just-added'), 500);

                    var partnerOptions = '<option value="">Choisir un partenaire...</option>';
                    partnersList.forEach(function(partner) {
                        var phoneText = partner.phone ? ' (' + partner.phone + ')' : '';
                        partnerOptions += '<option value="' + partner.id + '">' + partner.name + phoneText + '</option>';
                    });

                    const newRow = document.createElement('li');
                    newRow.classList.add('product-row');
                    newRow.setAttribute('data-sku', sku);

                    newRow.innerHTML = '<ul class="product-lists">' +
                        '<input type="hidden" name="sales[' + i + '][product_id]" value="' + product_id + '">' +
                        '<input type="hidden" name="sales[' + i + '][numeroFacture]" value="' + numeroFacture + '">' +
                        '<input type="hidden" name="sales[' + i + '][is_third_party]" value="0" class="third-party-flag">' +
                        '<li>' +
                            '<div class="productimg">' +
                                '<div class="productimgs">' +
                                    '<img src="' + imageUrl + '" alt="img">' +
                                '</div>' +
                                '<div class="productcontet">' +
                                    '<h4>' + libelle + '</h4>' +
                                    '<div class="productlinkset">' +
                                        '<h5>' + sku + '</h5>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</li>' +
                        '<li class="qty-price-group">' +
                            '<div class="quantity-set form-group">' +
                                '<h6>Qté <span style="font-weight:400;color:#6B7A35;">(' + (unitesByProduct[product_id]?.libelle || 'Pièce') + ')</span></h6>' +
                                '<div class="qty-stepper">' +
                                    '<button type="button" class="qty-decrement">-</button>' +
                                    '<input type="number" min="1" name="sales[' + i + '][quantity]" value="1" class="quantity-field form-control">' +
                                    '<button type="button" class="qty-increment">+</button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="price form-group">' +
                                '<h6>Prix de Vente</h6>' +
                                '<input type="text" style="width:150px" name="sales[' + i + '][prix]" value="' + productPrice + '" class="price-field form-control">' +
                                '<div class="text-danger price-error mt-1" style="font-size: 0.9rem; display: none;"></div>' +
                            '</div>' +
                        '</li>' +
                        '<li class="origin-group" style="width:100%; margin-top:8px;">' +
                            '<h6 style="font-size:12px; margin-bottom:4px;">Origine du stock (emplacement — propriétaire)</h6>' +
                            '<input type="hidden" name="sales[' + i + '][fund_id]" class="fund-input" value="">' +
                            '<select name="sales[' + i + '][store_id]" class="form-control origin-select">' +
                                originOptions(product_id) +
                            '</select>' +
                            '<div class="text-danger origin-error mt-1" style="font-size:0.85rem; display:none;"></div>' +
                        '</li>' +
                        '<li style="width:100%; margin-top:8px;">' +
                            '<label style="font-size:13px; cursor:pointer; display:flex; align-items:center;">' +
                                '<input type="checkbox" class="third-party-check" style="margin-right:6px; accent-color:#3E4C21;">' +
                                'Vente Eclaire' +
                            '</label>' +
                            '<div class="third-party-fields mt-2" style="display:none; background:#F9F9F3; padding:10px; border-radius:8px; border:1px solid #CED5AE;">' +
                                '<div style="display:flex; gap:8px; margin-bottom:8px;">' +
                                    '<select name="sales[' + i + '][partner_id]" class="form-control partner-select" style="flex:1;">' +
                                        partnerOptions +
                                    '</select>' +
                                    '<button type="button" class="btn btn-sm btn-add-partner" style="background:#3E4C21; color:#fff; white-space:nowrap;">' +
                                        '<i class="fa fa-plus"></i>' +
                                    '</button>' +
                                '</div>' +
                                '<input type="number" step="0.01" name="sales[' + i + '][purchase_price_from_partner]" placeholder="Prix payé au partenaire (par unité)" class="form-control">' +
                            '</div>' +
                        '</li>' +
                        '<li>' +
                            '<div class="row-total form-group">' +
                                '<h6>Total ligne</h6>' +
                                '<input type="text" name="sales[' + i + '][total_price]" class="form-control row-total-price" readonly>' +
                            '</div>' +
                        '</li>' +
                        '<li>' +
                            '<button type="button" class="row-remove-btn" title="Retirer">' +
                                '<i class="fa fa-trash"></i>' +
                            '</button>' +
                        '</li>' +
                    '</ul>';

                    productList.appendChild(newRow);
                    initializeRowEvents(newRow, productPrice, productCost, productMinPrice);
                });

                // productCost doit être passé en paramètre : initializeRowEvents est
                // déclarée hors du gestionnaire de clic et ne voit donc pas ses variables.
                function initializeRowEvents(row, productPrice, productCost, productMinPrice = 0) {
                    const quantityField = row.querySelector('.quantity-field');
                    const priceField = row.querySelector('.price-field');
                    const rowTotalField = row.querySelector('.row-total-price');
                    const thirdPartyCheck = row.querySelector('.third-party-check');
                    const thirdPartyFields = row.querySelector('.third-party-fields');
                    const thirdPartyFlag = row.querySelector('.third-party-flag');
                    const addPartnerBtn = row.querySelector('.btn-add-partner');
                    const partnerSelect = row.querySelector('.partner-select');
                    const productId = row.querySelector('input[name$="[product_id]"]').value;
                    if (thirdPartyCheck) {
                        thirdPartyCheck.addEventListener('change', function() {
                            if (this.checked) {
                                thirdPartyFields.style.display = 'block';
                                thirdPartyFlag.value = "1";
                            } else {
                                thirdPartyFields.style.display = 'none';
                                thirdPartyFlag.value = "0";
                                if (partnerSelect) partnerSelect.value = '';
                                const purchasePrice = thirdPartyFields.querySelector('input[name*="purchase_price_from_partner"]');
                                if (purchasePrice) purchasePrice.value = '';
                            }
                        });
                    }

                    if (addPartnerBtn) {
                        addPartnerBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            targetPartnerSelect = partnerSelect;
                            document.getElementById('quickPartnerForm').reset();
                            document.getElementById('quickPartnerError').style.display = 'none';
                            quickPartnerModal.show();
                        });
                    }

                    row.querySelector('.qty-increment').addEventListener('click', function() {
                        quantityField.value = parseInt(quantityField.value) + 1;
                        updateRowTotal();
                    });
                    row.querySelector('.qty-decrement').addEventListener('click', function() {
                        quantityField.value = Math.max(1, parseInt(quantityField.value) - 1);
                        updateRowTotal();
                    });

                    const originSelect = row.querySelector('.origin-select');
                    const originGroup = row.querySelector('.origin-group');
                    const originError = row.querySelector('.origin-error');

                    // Contrôle immédiat : quantité demandée > stock de l'origine choisie.
                    // Une vente tierce ne puise pas dans le stock : pas d'origine.
                    function controlerOrigine() {
                        if (!originSelect) return;
                        const tierce = row.querySelector('.third-party-check')?.checked;
                        originGroup.style.display = tierce ? 'none' : '';
                        originSelect.disabled = !!tierce;
                        const opt = originSelect.options[originSelect.selectedIndex];
                        // Propriétaire de la marchandise choisie : envoyé avec la ligne.
                        const fundInput = row.querySelector('.fund-input');
                        if (fundInput) fundInput.value = tierce ? '' : (opt?.dataset.fund || '');
                        const dispo = parseInt(opt?.dataset.available || '0', 10);
                        const qty = parseInt(quantityField.value, 10) || 0;
                        if (!tierce && qty > dispo) {
                            originSelect.dataset.stockError = '1';
                            originSelect.classList.add('is-invalid');
                            originError.textContent = 'Stock insuffisant : ' + dispo.toLocaleString('fr-FR')
                                + ' disponible(s) à cet emplacement, ' + qty.toLocaleString('fr-FR') + ' demandé(s)'
                                + (motUnite(productId, 2) ? ' (' + motUnite(productId, 2) + ')' : '') + '.';
                            originError.style.display = 'block';
                        } else {
                            delete originSelect.dataset.stockError;
                            originSelect.classList.remove('is-invalid');
                            originError.style.display = 'none';
                        }
                        synchroniserCaisse();
                    }

                    function updateRowTotal() {
                        const qty = parseFloat(quantityField.value) || 0;
                        const price = parseFloat(priceField.value) || 0;
                        rowTotalField.value = (qty * price).toFixed(2);
                        controlerOrigine();
                        updateTotalPrice();
                    }

                    originSelect?.addEventListener('change', controlerOrigine);
                    row.querySelector('.third-party-check')?.addEventListener('change', controlerOrigine);

                    quantityField.addEventListener('input', updateRowTotal);
                    // Plancher applicable à la ligne : pour une vente tierce c'est le prix
                    // payé au partenaire, sinon le prix de revient du produit.
                    function plancherDeLaLigne() {
                        if (row.querySelector('.third-party-check')?.checked) {
                            const achat = parseFloat(row.querySelector('input[name*="purchase_price_from_partner"]')?.value) || 0;
                            return { valeur: achat, origine: "prix d'achat au partenaire" };
                        }
                        // Même règle que le serveur : le plus haut du prix de vente
                        // minimum (fiche produit) et du prix de revient.
                        const unite = motUnite(productId, 1) || 'pièce';
                        return productMinPrice > 0 && productMinPrice >= productCost
                            ? { valeur: productMinPrice, origine: 'prix de vente minimum par ' + unite, minimum: true }
                            : { valeur: productCost, origine: 'prix de revient par ' + unite, minimum: false };
                    }

                    // Deux situations distinctes, qu'il ne faut pas confondre :
                    // vendre sous le prix affiché est une remise légitime, vendre sous le
                    // prix de revient est une perte — seule la seconde bloque.
                    function controlerPrix() {
                        const saisi = parseFloat(priceField.value) || 0;
                        const priceError = row.querySelector('.price-error');
                        const plancher = plancherDeLaLigne();

                        if (plancher.valeur > 0 && saisi < plancher.valeur) {
                            priceField.dataset.belowCost = '1';
                            priceField.classList.add('is-invalid');
                            if (priceError) {
                                priceError.textContent = (plancher.minimum ? 'Prix sous le minimum autorisé : ' : 'Vente à perte : minimum ')
                                    + plancher.valeur.toLocaleString('fr-FR') + ' GNF (' + plancher.origine + ')';
                                priceError.style.color = '#d92d20';
                                priceError.style.display = 'block';
                            }
                        } else if (saisi < productPrice) {
                            delete priceField.dataset.belowCost;
                            priceField.classList.remove('is-invalid');
                            if (priceError) {
                                priceError.textContent = 'Remise appliquée — prix affiché : '
                                    + productPrice.toLocaleString('fr-FR') + ' GNF'
                                    + (plancher.minimum ? ' · minimum ' + plancher.valeur.toLocaleString('fr-FR') + ' GNF' : '');
                                priceError.style.color = '#b45309';
                                priceError.style.display = 'block';
                            }
                        } else {
                            delete priceField.dataset.belowCost;
                            priceField.classList.remove('is-invalid');
                            if (priceError) priceError.style.display = 'none';
                        }
                        updateRowTotal();
                    }

                    priceField.addEventListener('input', controlerPrix);
                    row.querySelector('input[name*="purchase_price_from_partner"]')?.addEventListener('input', controlerPrix);
                    row.querySelector('.third-party-check')?.addEventListener('change', controlerPrix);
                    controlerPrix();

                    row.querySelector('.row-remove-btn').addEventListener('click', function() {
                        // La carte correspondante doit perdre son surlignage : sinon
                        // elle reste marquee comme selectionnee alors qu'elle n'est
                        // plus au panier, et le vendeur ne sait plus ou il en est.
                        const carte = document.querySelector('.product-item[data-sku="' + row.getAttribute('data-sku') + '"]');
                        if (carte) {
                            carte.classList.remove('selected');
                        }

                        row.remove();
                        updateTotalPrice();
                    });

                    updateRowTotal();
                }

                // ── Produit pris chez un partenaire, dans la vente en cours ──
                (function () {
                    const modalEl = document.getElementById('partnerLineModal');
                    if (!modalEl) return;
                    const modal = new bootstrap.Modal(modalEl);
                    const champ = id => document.getElementById(id);
                    const echapper = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                    const fmt = n => Number(n).toLocaleString('fr-FR');

                    function produitChoisi() { return champ('plProduit').selectedOptions[0]; }
                    function uniteChoisie() {
                        const opt = produitChoisi();
                        if (opt && opt.value === '__nouveau') {
                            const u = champ('plUnite').selectedOptions[0];
                            return { libelle: u.textContent.trim(), singulier: u.textContent.trim().toLowerCase() };
                        }
                        return opt && opt.value ? { libelle: opt.dataset.unite, singulier: opt.dataset.singulier } : null;
                    }
                    function rafraichir() {
                        const opt = produitChoisi();
                        champ('plNouveau').style.display = opt && opt.value === '__nouveau' ? '' : 'none';
                        const u = uniteChoisie();
                        champ('plUniteTexte').textContent = u ? '(' + u.libelle + ')' : '';
                        if (opt && opt.dataset.prix && !champ('plPrix').value) champ('plPrix').value = opt.dataset.prix;
                    }
                    champ('plProduit').addEventListener('change', rafraichir);
                    champ('plUnite').addEventListener('change', rafraichir);
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery('#plProduit').select2({ dropdownParent: jQuery(modalEl), width: '100%' }).on('change', rafraichir);
                    }
                    champ('plAjoutPartenaire').addEventListener('click', function () {
                        targetPartnerSelect = champ('plPartenaire');
                        document.getElementById('quickPartnerForm').reset();
                        document.getElementById('quickPartnerError').style.display = 'none';
                        quickPartnerModal.show();
                    });

                    function erreur(message) {
                        champ('plErreur').textContent = message;
                        champ('plErreur').style.display = message ? 'block' : 'none';
                    }

                    champ('plAjouter').addEventListener('click', function () {
                        const opt = produitChoisi();
                        const nouveau = opt && opt.value === '__nouveau';
                        const libelle = nouveau ? champ('plLibelle').value.trim() : (opt && opt.value ? opt.textContent.split(' — ')[0].trim() : '');
                        const partenaire = champ('plPartenaire').selectedOptions[0];
                        const qte = parseInt(champ('plQuantite').value, 10) || 0;
                        const prixAmi = parseFloat(champ('plPrixAmi').value) || 0;
                        const prix = parseFloat(champ('plPrix').value) || 0;

                        if (!opt || !opt.value) return erreur('Choisissez le produit.');
                        if (nouveau && !libelle) return erreur('Indiquez le nom du nouveau produit.');
                        if (!partenaire || !partenaire.value) return erreur('Choisissez le partenaire qui fournit le produit.');
                        if (qte < 1) return erreur("La quantité doit être d'au moins 1.");
                        if (prixAmi <= 0) return erreur('Indiquez le prix payé au partenaire.');
                        if (prix <= 0) return erreur('Indiquez le prix de vente.');
                        if (prix < prixAmi) return erreur('Vente à perte : le prix de vente est inférieur au prix payé au partenaire.');
                        erreur('');

                        i++;
                        const u = uniteChoisie();
                        const nomPartenaire = partenaire.textContent.replace(/\s*\(.*\)\s*$/, '').trim();
                        const ligne = document.createElement('li');
                        ligne.classList.add('product-row', 'product-row--tiers');
                        ligne.setAttribute('data-sku', 'tiers-' + i);
                        ligne.innerHTML = '<ul class="product-lists">' +
                            (nouveau
                                ? '<input type="hidden" name="sales[' + i + '][nouveau_libelle]" value="' + echapper(libelle) + '">' +
                                  '<input type="hidden" name="sales[' + i + '][nouvelle_unite]" value="' + echapper(champ('plUnite').value) + '">'
                                : '<input type="hidden" name="sales[' + i + '][product_id]" value="' + echapper(opt.value) + '">') +
                            '<input type="hidden" name="sales[' + i + '][is_third_party]" value="1">' +
                            '<input type="hidden" name="sales[' + i + '][partner_id]" value="' + echapper(partenaire.value) + '">' +
                            '<input type="hidden" name="sales[' + i + '][purchase_price_from_partner]" value="' + prixAmi + '">' +
                            '<li style="width:100%;">' +
                                '<h4 style="font-size:14px;margin-bottom:2px;">' + echapper(libelle) + '</h4>' +
                                '<span class="badge" style="background:#FBF6EA;color:#8A6A24;border:1px solid #C7A150;font-weight:600;white-space:normal;">' +
                                    '<i class="fa fa-handshake me-1"></i>Pris chez ' + echapper(nomPartenaire) + ' · payé ' + fmt(prixAmi) + ' GNF / ' + echapper(u ? u.singulier : 'pièce') +
                                '</span>' +
                            '</li>' +
                            '<li class="qty-price-group">' +
                                '<div class="quantity-set form-group"><h6>Qté <span style="font-weight:400;color:#6B7A35;">(' + echapper(u ? u.libelle : 'Pièce') + ')</span></h6>' +
                                    '<input type="number" min="1" name="sales[' + i + '][quantity]" value="' + qte + '" class="quantity-field form-control"></div>' +
                                '<div class="price form-group"><h6>Prix de Vente</h6>' +
                                    '<input type="text" style="width:150px" name="sales[' + i + '][prix]" value="' + prix + '" class="price-field form-control">' +
                                    '<div class="text-danger price-error mt-1" style="font-size:0.9rem;display:none;"></div></div>' +
                            '</li>' +
                            '<li><div class="row-total form-group"><h6>Total ligne</h6><input type="text" class="form-control row-total-price" readonly></div></li>' +
                            '<li><button type="button" class="row-remove-btn" title="Retirer"><i class="fa fa-trash"></i></button></li>' +
                        '</ul>';
                        productList.appendChild(ligne);

                        const qteChamp = ligne.querySelector('.quantity-field');
                        const prixChamp = ligne.querySelector('.price-field');
                        function recalculer() {
                            const p = parseFloat(prixChamp.value) || 0;
                            const erreurPrix = ligne.querySelector('.price-error');
                            // Plancher : le prix payé au partenaire (même règle que le serveur).
                            if (p < prixAmi) {
                                prixChamp.dataset.belowCost = '1';
                                prixChamp.classList.add('is-invalid');
                                erreurPrix.textContent = 'Vente à perte : minimum ' + fmt(prixAmi) + ' GNF (prix payé au partenaire)';
                                erreurPrix.style.display = 'block';
                            } else {
                                delete prixChamp.dataset.belowCost;
                                prixChamp.classList.remove('is-invalid');
                                erreurPrix.style.display = 'none';
                            }
                            ligne.querySelector('.row-total-price').value = ((parseInt(qteChamp.value, 10) || 0) * p).toFixed(2);
                            updateTotalPrice();
                        }
                        qteChamp.addEventListener('input', recalculer);
                        prixChamp.addEventListener('input', recalculer);
                        ligne.querySelector('.row-remove-btn').addEventListener('click', function () {
                            ligne.remove();
                            updateTotalPrice();
                        });
                        recalculer();

                        // Prêt pour un autre produit, même partenaire.
                        ['plLibelle', 'plPrixAmi', 'plPrix'].forEach(id => champ(id).value = '');
                        champ('plQuantite').value = 1;
                        if (window.jQuery && jQuery.fn.select2) jQuery('#plProduit').val('').trigger('change'); else champ('plProduit').value = '';
                        rafraichir();
                        modal.hide();
                    });
                })();

                function updateTotalPrice() {
                    synchroniserCaisse();
                    let subtotal = 0;
                    const rows = document.querySelectorAll('.product-row');
                    rows.forEach(row => {
                        const rowTotal = parseFloat(row.querySelector('.row-total-price')?.value) || 0;
                        subtotal += rowTotal;
                    });

                    const emptyState = document.getElementById('emptyCartState');
                    const cartCountBadge = document.getElementById('cartCountBadge');
                    if (emptyState) {
                        emptyState.style.display = rows.length === 0 ? 'block' : 'none';
                    }
                    if (cartCountBadge) {
                        if (rows.length > 0) {
                            cartCountBadge.textContent = rows.length;
                            cartCountBadge.style.display = 'inline-block';
                        } else {
                            cartCountBadge.style.display = 'none';
                        }
                    }

                    const subtotalElement = document.getElementById('subtotal');
                    const taxElement = document.getElementById('tax');
                    if (subtotalElement) {
                        subtotalElement.textContent = subtotal.toFixed(2);
                        const taxAmount = parseFloat(taxElement?.value || 0) || 0;
                        const finalTotal = subtotal + taxAmount;
                        const finalTotalElement = document.getElementById('finalTotal');
                        const totalAmountElement = document.getElementById('final_total');
                        if (totalAmountElement) {
                            totalAmountElement.value = finalTotal.toFixed(2);
                        }
                        if (finalTotalElement) {
                            finalTotalElement.textContent = finalTotal.toFixed(2);
                        }
                    }
                }

                const taxInput = document.getElementById('tax');
                if (taxInput) {
                    taxInput.addEventListener('input', updateTotalPrice);
                }

                // Gestion création partenaire
                document.getElementById('saveQuickPartner').addEventListener('click', function () {
                    const form = document.getElementById('quickPartnerForm');
                    const name = document.getElementById('quickPartnerName').value.trim();
                    const errorBox = document.getElementById('quickPartnerError');

                    if (!name) {
                        errorBox.textContent = 'Le nom du partenaire est requis.';
                        errorBox.style.display = 'block';
                        return;
                    }

                    const formData = new FormData(form);
                    const saveBtn = this;
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Enregistrement...';

                    fetch('{{ route("pos.storePartner") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Enregistrer';

                        if (!data.success) {
                            errorBox.textContent = data.message || "Erreur lors de la création du partenaire.";
                            errorBox.style.display = 'block';
                            return;
                        }

                        const newPartner = data.partner;
                        document.querySelectorAll('.partner-select').forEach(select => {
                            const phoneText = newPartner.phone ? ' (' + newPartner.phone + ')' : '';
                            const option = document.createElement('option');
                            option.value = newPartner.id;
                            option.textContent = newPartner.name + phoneText;
                            select.appendChild(option);
                        });

                        if (targetPartnerSelect) {
                            targetPartnerSelect.value = newPartner.id;
                        }

                        quickPartnerModal.hide();
                        form.reset();
                    })
                    .catch(error => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Enregistrer';
                        errorBox.textContent = "Erreur réseau, veuillez réessayer.";
                        errorBox.style.display = 'block';
                        console.error('Erreur:', error);
                    });
                });

                // ── Vente tierce : lignes multiples, client, récapitulatif ──
                const vtLignes    = document.getElementById('vtLignes');
                const vtGabarit   = document.getElementById('vtGabaritLigne');
                const vtRecapCorps = document.getElementById('vtRecapCorps');
                const vtErreur    = document.getElementById('vtErreur');

                const vtFormat = (n) => new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FG';

                // Mode client : existant ou nouveau
                let vtModeClient = 'existant';
                document.querySelectorAll('.vt-choix-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        vtModeClient = btn.dataset.mode;
                        document.querySelectorAll('.vt-choix-btn').forEach(b => b.classList.toggle('actif', b === btn));
                        document.querySelectorAll('[data-panneau]').forEach(function (p) {
                            p.hidden = p.dataset.panneau !== vtModeClient;
                        });
                    });
                });

                function vtValeurs(ligne) {
                    const lire = (champ) => ligne.querySelector('[data-champ="' + champ + '"]');
                    return {
                        el: ligne,
                        libelle: lire('libelle').value.trim(),
                        sku: lire('sku').value.trim(),
                        partner_id: lire('partner_id').value,
                        prix: parseFloat(lire('prix').value) || 0,
                        prixAmi: parseFloat(lire('purchase_price_from_partner').value) || 0,
                        quantite: parseInt(lire('quantity').value, 10) || 0
                    };
                }

                // Renumérote les lignes et redessine le récapitulatif.
                function vtRafraichir() {
                    const lignes = [...vtLignes.querySelectorAll('.vt-ligne')];
                    let total = 0, marge = 0, articles = 0;
                    const rangs = [];

                    lignes.forEach(function (ligne, i) {
                        ligne.querySelector('.vt-ligne-num').textContent = 'Produit ' + (i + 1);
                        ligne.querySelector('.vt-btn-retirer').style.visibility = lignes.length > 1 ? 'visible' : 'hidden';

                        const v = vtValeurs(ligne);
                        const totalLigne = v.prix * v.quantite;
                        const margeLigne = (v.prix - v.prixAmi) * v.quantite;

                        // Vendre sous le prix du partenaire est refusé côté serveur :
                        // on le signale tout de suite plutôt qu'à l'enregistrement.
                        const pied = ligne.querySelector('.vt-ligne-marge');
                        if (v.prix > 0 && v.prixAmi > 0) {
                            const perte = v.prix < v.prixAmi;
                            pied.className = 'vt-ligne-marge ' + (perte ? 'perte' : 'ok');
                            pied.textContent = perte
                                ? 'Vente à perte : le prix est inférieur à celui du partenaire.'
                                : 'Marge sur cette ligne : ' + vtFormat(margeLigne);
                        } else {
                            pied.textContent = '';
                        }

                        if (v.libelle && v.prix > 0 && v.quantite > 0) {
                            total += totalLigne;
                            marge += margeLigne;
                            articles += v.quantite;
                            rangs.push({ libelle: v.libelle, quantite: v.quantite, prix: v.prix, totalLigne, margeLigne });
                        }
                    });

                    vtRecapCorps.innerHTML = rangs.length
                        ? rangs.map(r =>
                            '<tr>' +
                                '<td>' + r.libelle.replace(/</g, '&lt;') + '</td>' +
                                '<td class="vt-c">' + r.quantite + '</td>' +
                                '<td class="vt-d">' + vtFormat(r.prix) + '</td>' +
                                '<td class="vt-d">' + vtFormat(r.totalLigne) + '</td>' +
                                '<td class="vt-d" style="color:' + (r.margeLigne < 0 ? '#b42318' : '#3E4C21') + '">' + vtFormat(r.margeLigne) + '</td>' +
                            '</tr>').join('')
                        : '<tr class="vt-vide"><td colspan="5">Aucun produit pour l\'instant.</td></tr>';

                    document.getElementById('vtNbArticles').textContent = articles;
                    document.getElementById('vtMarge').textContent = vtFormat(marge);
                    document.getElementById('vtTotal').textContent = vtFormat(total);
                }

                function vtAjouterLigne() {
                    const ligne = vtGabarit.content.firstElementChild.cloneNode(true);

                    ligne.querySelectorAll('.vt-champ').forEach(function (champ) {
                        champ.addEventListener('input', vtRafraichir);
                        champ.addEventListener('change', vtRafraichir);
                    });

                    ligne.querySelector('.vt-btn-retirer').addEventListener('click', function () {
                        ligne.remove();
                        vtRafraichir();
                    });

                    ligne.querySelector('.btn-add-partner').addEventListener('click', function (e) {
                        e.preventDefault();
                        targetPartnerSelect = ligne.querySelector('.partner-select');
                        quickPartnerModal.show();
                    });

                    vtLignes.appendChild(ligne);
                    vtRafraichir();
                    ligne.querySelector('[data-champ="libelle"]').focus();
                    return ligne;
                }

                document.getElementById('vtAjouterLigne').addEventListener('click', vtAjouterLigne);

                // Une ligne vierge dès l'ouverture, pour ne pas accueillir le vendeur
                // sur un formulaire vide.
                thirdPartyQuickModalEl.addEventListener('show.bs.modal', function () {
                    if (!vtLignes.querySelector('.vt-ligne')) {
                        vtAjouterLigne();
                    }

                    // Si une boutique est deja choisie en caisse, on la reprend :
                    // le vendeur n'a pas a la ressaisir.
                    const enCaisse = document.getElementById('store_id');
                    const dansFenetre = document.getElementById('vtBoutique');
                    if (enCaisse && enCaisse.value && !dansFenetre.value) {
                        dansFenetre.value = enCaisse.value;
                    }

                    vtRafraichir();
                });

                function vtAfficherErreurs(messages) {
                    vtErreur.innerHTML = messages.length === 1
                        ? messages[0]
                        : 'Corrigez les points suivants :<ul><li>' + messages.join('</li><li>') + '</li></ul>';
                    vtErreur.hidden = false;
                    vtErreur.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                document.getElementById('saveQuickThirdParty').addEventListener('click', function () {
                    const bouton = this;
                    vtErreur.hidden = true;
                    document.querySelectorAll('#thirdPartyQuickModal .vt-invalide').forEach(c => c.classList.remove('vt-invalide'));

                    const erreurs = [];
                    const lignes = [...vtLignes.querySelectorAll('.vt-ligne')].map(vtValeurs);

                    if (!lignes.length) {
                        erreurs.push('Ajoutez au moins un produit.');
                    }

                    lignes.forEach(function (v, i) {
                        const marquer = (champ) => v.el.querySelector('[data-champ="' + champ + '"]').classList.add('vt-invalide');
                        const n = 'Produit ' + (i + 1) + ' : ';
                        if (!v.libelle)      { erreurs.push(n + 'le libellé est obligatoire.'); marquer('libelle'); }
                        if (!v.sku)          { erreurs.push(n + 'la référence est obligatoire.'); marquer('sku'); }
                        if (!v.partner_id)   { erreurs.push(n + 'choisissez le partenaire.'); marquer('partner_id'); }
                        if (v.prix <= 0)     { erreurs.push(n + 'indiquez le prix de vente.'); marquer('prix'); }
                        if (v.prixAmi <= 0)  { erreurs.push(n + "indiquez le prix donné par l'ami."); marquer('purchase_price_from_partner'); }
                        if (v.quantite < 1)  { erreurs.push(n + "la quantité doit être d'au moins 1."); marquer('quantity'); }
                        if (v.prix > 0 && v.prixAmi > 0 && v.prix < v.prixAmi) {
                            erreurs.push(n + 'vente à perte, le prix est inférieur à celui du partenaire.');
                            marquer('prix');
                        }
                    });

                    const boutiqueId = document.getElementById('vtBoutique').value;
                    if (!boutiqueId) {
                        erreurs.push('Choisissez la boutique qui enregistre cette vente.');
                        document.getElementById('vtBoutique').classList.add('vt-invalide');
                    }

                    const clientId = document.getElementById('vtClientExistant').value;
                    const nouveauNom = document.getElementById('vtNouveauNom').value.trim();

                    if (vtModeClient === 'existant' && !clientId) {
                        erreurs.push('Choisissez un client, ou passez à « Nouveau client ».');
                    }
                    if (vtModeClient === 'nouveau' && !nouveauNom) {
                        erreurs.push('Renseignez le nom du nouveau client.');
                        document.getElementById('vtNouveauNom').classList.add('vt-invalide');
                    }

                    if (erreurs.length) {
                        vtAfficherErreurs(erreurs);
                        return;
                    }

                    const donnees = new FormData();
                    donnees.append('store_id', document.getElementById('vtBoutique').value || '');
                    donnees.append('avance', document.getElementById('vtAvance').value || 0);
                    donnees.append('paid_by', document.getElementById('vtPaidBy').value);
                    donnees.append('notes', document.getElementById('vtNotes').value);

                    if (vtModeClient === 'existant') {
                        donnees.append('customer_id', clientId);
                    } else {
                        donnees.append('nouveau_client[customerName]', nouveauNom);
                        donnees.append('nouveau_client[tel]', document.getElementById('vtNouveauTel').value.trim());
                        donnees.append('nouveau_client[address]', document.getElementById('vtNouveauAdresse').value.trim());
                    }

                    lignes.forEach(function (v, i) {
                        donnees.append('lignes[' + i + '][libelle]', v.libelle);
                        donnees.append('lignes[' + i + '][sku]', v.sku);
                        donnees.append('lignes[' + i + '][prix]', v.prix);
                        donnees.append('lignes[' + i + '][purchase_price_from_partner]', v.prixAmi);
                        donnees.append('lignes[' + i + '][partner_id]', v.partner_id);
                        donnees.append('lignes[' + i + '][quantity]', v.quantite);
                    });

                    bouton.disabled = true;
                    bouton.textContent = 'Enregistrement…';

                    fetch('{{ route("pos.storeQuickThirdParty") }}', {
                        method: 'POST',
                        body: donnees,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(function (reponse) {
                        bouton.disabled = false;
                        bouton.textContent = 'Enregistrer la vente';

                        if (reponse.ok && reponse.data.success) {
                            window.location.href = reponse.data.redirect;
                            return;
                        }

                        // Erreurs de validation renvoyées champ par champ
                        const messages = reponse.data.errors
                            ? Object.values(reponse.data.errors).flat()
                            : [reponse.data.message || "Erreur lors de l'enregistrement."];
                        vtAfficherErreurs(messages);
                    })
                    .catch(function (e) {
                        bouton.disabled = false;
                        bouton.textContent = 'Enregistrer la vente';
                        console.error(e);
                        vtAfficherErreurs(['Erreur réseau, veuillez réessayer.']);
                    });
                });

                // Gestion client
                const addCustomerButton = document.getElementById('addCustomerButton');
                const selectCustomer = document.getElementById('customer_id');

                function showHideCustomerButton() {
                    if (!selectCustomer.value || selectCustomer.value === 'Selectionner Client') {
                        addCustomerButton.style.display = 'block';
                    } else {
                        addCustomerButton.style.display = 'none';
                    }
                }

                showHideCustomerButton();
                selectCustomer.addEventListener('change', showHideCustomerButton);

                const addCustomerModal = new bootstrap.Modal(document.getElementById('create'));
                document.getElementById('addCustomerForm').addEventListener('submit', function (e) {
                    e.preventDefault();
                    let formData = new FormData(this);
                    fetch('{{ route("pos.storeCustomer") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const customerSelect = document.getElementById('customer_id');
                            let newOption = document.createElement('option');
                            newOption.value = data.customer.id;
                            newOption.textContent = data.customer.customerName + ' ' + data.customer.mark;
                            customerSelect.appendChild(newOption);
                            customerSelect.value = data.customer.id;
                            showHideCustomerButton();
                            addCustomerModal.hide();
                            document.getElementById('addCustomerForm').reset();
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
                });
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('posSearchInput');
                const clearSearchBtn = document.getElementById('clearSearchBtn');
                const tabContents = document.querySelectorAll('.tab_content');
                const tabItems = document.querySelectorAll('.tab-item');

                document.addEventListener('keydown', function (e) {
                    if (e.key === '/' && document.activeElement !== searchInput && document.activeElement.tagName !== 'TEXTAREA' && document.activeElement.tagName !== 'INPUT') {
                        e.preventDefault();
                        searchInput.focus();
                    }
                });

                function resetToInitialState() {
                    const savedTab = localStorage.getItem('activeTab');
                    tabItems.forEach(tab => tab.classList.remove('active'));
                    tabContents.forEach(tab => {
                        tab.classList.remove('active');
                        tab.style.display = 'none';
                    });
                    let targetTab = tabItems[0];
                    let targetContent = tabContents[0];
                    if (savedTab) {
                        const savedTabItem = document.querySelector('.tab-item[data-tab="' + savedTab + '"]');
                        const savedContent = document.querySelector('.tab_content[data-tab="' + savedTab + '"]');
                        if (savedTabItem && savedContent) {
                            targetTab = savedTabItem;
                            targetContent = savedContent;
                        }
                    }
                    targetTab.classList.add('active');
                    targetContent.classList.add('active');
                    targetContent.style.display = 'block';
                }

                const PAGE_SIZE = 10;
                const pageState = {};

                function paginateTab(tabContent) {
                    const slug = tabContent.getAttribute('data-tab');
                    const pageContainer = tabContent.querySelector('.pos-pagination');
                    const items = Array.from(tabContent.querySelectorAll('.product-item'));
                    if (!items.length) {
                        if (pageContainer) pageContainer.innerHTML = '';
                        return;
                    }
                    const totalPages = Math.ceil(items.length / PAGE_SIZE);
                    let current = pageState[slug] || 1;
                    if (current > totalPages) current = totalPages;
                    pageState[slug] = current;
                    items.forEach((item, idx) => {
                        const page = Math.floor(idx / PAGE_SIZE) + 1;
                        item.classList.toggle('pos-hidden', page !== current);
                    });
                    if (!pageContainer) return;
                    if (totalPages <= 1) {
                        pageContainer.innerHTML = '';
                        return;
                    }
                    let html = '<button type="button" data-page="prev" ' + (current === 1 ? 'disabled' : '') + '>&laquo;</button>';
                    for (let p = 1; p <= totalPages; p++) {
                        html += '<button type="button" data-page="' + p + '" class="' + (p === current ? 'active' : '') + '">' + p + '</button>';
                    }
                    html += '<button type="button" data-page="next" ' + (current === totalPages ? 'disabled' : '') + '>&raquo;</button>';
                    pageContainer.innerHTML = html;
                    pageContainer.querySelectorAll('button').forEach(btn => {
                        btn.addEventListener('click', function () {
                            const val = this.getAttribute('data-page');
                            if (val === 'prev') pageState[slug] = Math.max(1, current - 1);
                            else if (val === 'next') pageState[slug] = Math.min(totalPages, current + 1);
                            else pageState[slug] = parseInt(val);
                            paginateTab(tabContent);
                        });
                    });
                }

                function paginateAllTabs() {
                    tabContents.forEach(paginateTab);
                }

                searchInput.addEventListener('input', function () {
                    const searchTerm = this.value.toLowerCase();
                    clearSearchBtn.style.display = searchTerm ? 'inline' : 'none';
                    if (!searchTerm) {
                        resetToInitialState();
                        paginateAllTabs();
                        return;
                    }
                    tabContents.forEach(tab => {
                        let hasVisibleProducts = false;
                        const products = tab.querySelectorAll('.product-item');
                        const pageContainer = tab.querySelector('.pos-pagination');
                        if (pageContainer) pageContainer.innerHTML = '';
                        products.forEach(product => {
                            const libelle = product.querySelector('.productsetcontent h5')?.textContent.toLowerCase() || '';
                            const sku = product.getAttribute('data-sku')?.toLowerCase() || '';
                            const cat = product.querySelector('.productsetcontent h6')?.textContent.toLowerCase() || '';
                            const match = libelle.includes(searchTerm) || sku.includes(searchTerm) || cat.includes(searchTerm);
                            product.classList.toggle('pos-hidden', !match);
                            if (match) hasVisibleProducts = true;
                        });
                        tab.style.display = hasVisibleProducts ? 'block' : 'none';
                        tab.classList.remove('active');
                    });
                    tabItems.forEach(tab => tab.classList.remove('active'));
                });

                clearSearchBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    clearSearchBtn.style.display = 'none';
                    resetToInitialState();
                    paginateAllTabs();
                    searchInput.focus();
                });

                tabItems.forEach(tab => {
                    tab.addEventListener('click', () => {
                        const target = tab.getAttribute('data-tab');
                        localStorage.setItem('activeTab', target);
                        tabItems.forEach(t => t.classList.remove('active'));
                        tabContents.forEach(c => {
                            c.classList.remove('active');
                            c.style.display = 'none';
                        });
                        tab.classList.add('active');
                        const contentToShow = document.querySelector('.tab_content[data-tab="' + target + '"]');
                        if (contentToShow) {
                            contentToShow.classList.add('active');
                            contentToShow.style.display = 'block';
                        }
                    });
                });

                resetToInitialState();
                paginateAllTabs();
            });
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
        <script>
            $(document).ready(function() {
                $(".owl-carousel").owlCarousel({
                    items: 5,
                    loop: false,
                    margin: 10,
                    nav: true
                });
            });
        </script>
    </body>
</html>