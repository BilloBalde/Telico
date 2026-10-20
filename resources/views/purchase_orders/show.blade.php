<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            :root {
                --po-primary: #3E4C21;
                --po-primary-light: #F4F5EC;
                --po-ink: #1F2414;
                --po-muted: #66695A;
                --po-border: #E6E9D5;
            }

            .po-hero {
                background: linear-gradient(135deg, #3E4C21 0%, #323D1A 100%);
                border-radius: 16px;
                padding: 28px 32px;
                color: #fff;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
                margin-bottom: 24px;
                box-shadow: 0 10px 30px -12px rgba(163, 78, 31, 0.5);
            }
            .po-hero-title {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .po-hero-icon {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.18);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                flex-shrink: 0;
            }
            .po-hero h4 {
                color: #fff;
                margin: 0;
                font-size: 22px;
                font-weight: 700;
            }
            .po-hero-sub {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: 4px;
                font-size: 13px;
                opacity: 0.9;
            }
            .po-hero-actions {
                display: flex;
                gap: 10px;
            }
            .po-hero-actions a {
                border-radius: 10px;
                padding: 10px 18px;
                font-weight: 600;
                font-size: 14px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .po-hero-actions a:hover {
                transform: translateY(-2px);
            }
            .po-btn-edit {
                background: #fff;
                color: var(--po-primary) !important;
            }
            .po-btn-back {
                background: rgba(255, 255, 255, 0.15);
                color: #fff !important;
                border: 1px solid rgba(255, 255, 255, 0.4);
            }

            .po-status {
                padding: 5px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.2px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .po-status-pending { background: #fff4de; color: #b5790a; }
            .po-status-received { background: #e2f5e9; color: #218c58; }
            .po-status-cancelled { background: #fdecea; color: #cb3a3a; }
            .po-hero .po-status {
                background: rgba(255, 255, 255, 0.2);
                color: #fff;
            }

            .po-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
                gap: 16px;
                margin-bottom: 24px;
            }
            .po-stat-card {
                background: #fff;
                border: 1px solid var(--po-border);
                border-radius: 14px;
                padding: 18px;
                display: flex;
                align-items: center;
                gap: 14px;
                transition: box-shadow 0.15s ease, transform 0.15s ease;
            }
            .po-stat-card:hover {
                box-shadow: 0 8px 20px -10px rgba(43, 36, 32, 0.2);
                transform: translateY(-2px);
            }
            .po-stat-icon {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                flex-shrink: 0;
            }
            .po-stat-icon.tone-store { background: #eaf2ff; color: #2e6bd6; }
            .po-stat-icon.tone-origin { background: #F4F5EC; color: var(--po-primary); }
            .po-stat-icon.tone-currency { background: #eafaf0; color: #21a366; }
            .po-stat-icon.tone-cbm { background: #f3ecff; color: #7c4fe0; }
            .po-stat-icon.tone-status { background: #fff4de; color: #b5790a; }
            .po-stat-icon.tone-date { background: #e7f7fb; color: #1594b3; }
            .po-stat-icon.tone-transport { background: #eaf2ff; color: #2e6bd6; }
            .po-stat-icon.tone-customs { background: #fdeeea; color: #d4562e; }
            .po-stat-icon.tone-fees { background: #f5f0e8; color: #8a6d3b; }
            .po-stat-icon.tone-notes { background: #f1f1f1; color: #6b6b6b; }

            .po-stat-label {
                display: block;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: var(--po-muted);
                margin-bottom: 3px;
            }
            .po-stat-value {
                font-size: 15px;
                font-weight: 700;
                color: var(--po-ink);
                line-height: 1.2;
            }

            .po-section-title {
                font-size: 15px;
                font-weight: 700;
                color: var(--po-ink);
                margin: 4px 0 14px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .po-section-title i {
                color: var(--po-primary);
            }

            .po-table-card {
                background: #fff;
                border: 1px solid var(--po-border);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 6px 20px -14px rgba(43, 36, 32, 0.25);
            }
            .po-table {
                width: 100%;
                border-collapse: collapse;
            }
            .po-table thead th {
                background: #F9F9F3;
                color: var(--po-muted);
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.4px;
                font-weight: 700;
                padding: 14px 16px;
                border-bottom: 1px solid var(--po-border);
                white-space: nowrap;
            }
            .po-table tbody td {
                padding: 14px 16px;
                border-bottom: 1px solid #E6E9D5;
                font-size: 14px;
                color: var(--po-ink);
                white-space: nowrap;
            }
            .po-table tbody tr:last-child td {
                border-bottom: none;
            }
            .po-table tbody tr:hover {
                background: #F9F9F3;
            }
            .po-product-cell {
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 600;
            }
            .po-product-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--po-primary);
                flex-shrink: 0;
            }
            .po-cbm-badge {
                display: inline-block;
                background: #f3ecff;
                color: #7c4fe0;
                border-radius: 8px;
                padding: 3px 9px;
                font-size: 12.5px;
                font-weight: 600;
            }
            .po-landed-cost {
                color: var(--po-primary);
                font-weight: 800;
                font-size: 14.5px;
            }
            .po-table-responsive {
                overflow-x: auto;
            }

            .po-grand-total {
                background: linear-gradient(135deg, #F4F5EC 0%, #E6E9D5 100%);
                border-top: 1px solid var(--po-border);
                padding: 22px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            .po-grand-total-label {
                font-size: 14px;
                color: var(--po-ink);
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .po-grand-total-value {
                font-size: 24px;
                font-weight: 800;
                color: var(--po-primary);
            }
        </style>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>
        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')

            <div class="page-wrapper">
                <div class="content">
                    @include('layouts.flash')

                    @php
                        $statusLabels = ['pending' => 'En attente', 'received' => 'Reçu', 'cancelled' => 'Annulé'];
                        $statusIcons = ['pending' => 'fa-hourglass-half', 'received' => 'fa-check-circle', 'cancelled' => 'fa-ban'];
                        $grandTotal = $order->items->sum(function ($item) {
                            return $item->landed_unit_cost_gnf * $item->quantity;
                        });
                    @endphp

                    <div class="po-hero">
                        <div class="po-hero-title">
                            <div class="po-hero-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div>
                                <h4>Achat {{ $order->reference }}</h4>
                                <div class="po-hero-sub">
                                    <span>{{ $order->origin === 'chine' ? '🇨🇳 Import Chine' : '🇬🇳 Achat local Guinée' }}</span>
                                    <span>&middot;</span>
                                    <span class="po-status po-status-{{ $order->status }}">
                                        <i class="fas {{ $statusIcons[$order->status] ?? 'fa-circle' }}"></i>
                                        {{ $statusLabels[$order->status] ?? $order->status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="po-hero-actions">
                            @if($order->status !== 'cancelled')
                            <a class="po-btn-edit" href="{{ route('purchase-orders.edit', $order->id) }}">
                                <i class="fas fa-pen"></i> Modifier
                            </a>
                            @endif
                            <a class="po-btn-back" href="{{ route('purchase-orders.index') }}">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </div>
                    </div>

                    <div class="po-stats-grid">
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-store"><i class="fas fa-store"></i></div>
                            <div>
                                <span class="po-stat-label">Magasin</span>
                                <span class="po-stat-value">{{ $order->store->store_name ?? $order->store->name ?? '—' }}</span>
                            </div>
                        </div>
                        @if($order->origin === 'chine')
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-currency"><i class="fas fa-money-bill-wave"></i></div>
                            <div>
                                <span class="po-stat-label">Devise / Taux</span>
                                <span class="po-stat-value">{{ $order->currency_code }} = {{ number_format($order->exchange_rate_used, 2) }} GNF</span>
                            </div>
                        </div>
                        @endif
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-cbm"><i class="fas fa-cube"></i></div>
                            <div>
                                <span class="po-stat-label">CBM total</span>
                                <span class="po-stat-value">{{ $order->total_cbm ? number_format($order->total_cbm, 3) : '—' }}</span>
                            </div>
                        </div>
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-date"><i class="fas fa-calendar-alt"></i></div>
                            <div>
                                <span class="po-stat-label">Date émission</span>
                                <span class="po-stat-value">{{ \Illuminate\Support\Carbon::parse($order->date_emis)->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-transport"><i class="fas fa-truck"></i></div>
                            <div>
                                <span class="po-stat-label">Transport</span>
                                <span class="po-stat-value">{{ number_format($order->transport_cost_gnf, 0, ',', ' ') }} GNF</span>
                            </div>
                        </div>
                        @if($order->origin === 'chine')
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-customs"><i class="fas fa-stamp"></i></div>
                            <div>
                                <span class="po-stat-label">Douane</span>
                                <span class="po-stat-value">{{ number_format($order->customs_cost_gnf, 0, ',', ' ') }} GNF</span>
                            </div>
                        </div>
                        @endif
                        @if($order->notes)
                        <div class="po-stat-card">
                            <div class="po-stat-icon tone-notes"><i class="fas fa-sticky-note"></i></div>
                            <div>
                                <span class="po-stat-label">Notes</span>
                                <span class="po-stat-value">{{ $order->notes }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="po-section-title"><i class="fas fa-boxes"></i> Détail des produits</div>

                    <div class="po-table-card">
                        <div class="po-table-responsive">
                            <table class="po-table">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Quantité</th>
                                        @if($order->origin === 'chine')
                                        <th>Prix unitaire ({{ $order->currency_code }})</th>
                                        @endif
                                        <th>Prix unitaire (GNF)</th>
                                        @if($order->origin === 'chine')
                                        <th>CBM / carton</th>
                                        <th>CBM ligne</th>
                                        <th>Fret alloué</th>
                                        <th>Douane allouée</th>
                                     
                                        @endif
                                        <th>Coût de revient par unité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="po-product-cell">
                                                <span class="po-product-dot"></span>
                                                {{ $item->product->libelle ?? '—' }}
                                            </div>
                                        </td>
                                        <td>{{ $item->quantite_affichee }}</td>
                                        @if($order->origin === 'chine')
                                        <td>{{ number_format($item->unit_price_foreign, 2) }}</td>
                                        @endif
                                        <td>{{ number_format($item->unit_price_gnf, 0, ',', ' ') }} <small class="text-muted">/ {{ $item->mot_unite }}</small></td>
                                        @if($order->origin === 'chine')
                                        <td>{{ $item->cbm_per_carton ? number_format($item->cbm_per_carton, 4) : '—' }}</td>
                                        <td><span class="po-cbm-badge">{{ number_format($item->line_total_cbm, 3) }}</span></td>
                                        <td>{{ number_format($item->allocated_freight_gnf, 0, ',', ' ') }}</td>
                                        <td>{{ number_format($item->allocated_customs_gnf, 0, ',', ' ') }}</td>
                                        @endif
                                        <td><span class="po-landed-cost">{{ number_format($item->landed_unit_cost_gnf, 0, ',', ' ') }} GNF</span> <small class="text-muted">/ {{ $item->mot_unite }}</small></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="po-grand-total">
                            <div class="po-grand-total-label">
                                <i class="fas fa-hand-holding-usd"></i> Coût de revient total de l'arrivage
                            </div>
                            <div class="po-grand-total-value">
                                {{ number_format($order->total_landed_cost_gnf ?? 0, 0, ',', ' ') }} GNF
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>
