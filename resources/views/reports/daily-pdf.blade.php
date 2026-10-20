<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    @php
        // $period vient du contrôleur en anglais (daily, weekly…) : on l'affiche en français.
        $libellePeriode = [
            'daily' => 'journalier', 'weekly' => 'hebdomadaire', 'monthly' => 'mensuel',
            'quarterly' => 'trimestriel', 'semestral' => 'semestriel', 'semi-annual' => 'semestriel',
            'yearly' => 'annuel', 'custom' => 'personnalisé',
        ][$period ?? 'daily'] ?? '';
    @endphp
    <title>Rapport {{ $libellePeriode }} — {{ $label ?? '' }}</title>

    <style>
        @page {
            margin: 25mm 30mm;
            size: A4;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 10px;
            color: #1F2414;
            line-height: 1.4;
            background: #ffffff;
            padding: 5mm;
            padding-bottom: 80px;
        }

        /* Header 3 columns */
        .invoice-head-3col {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 18px;
            border-bottom: 3px solid #3E4C21;
            padding-bottom: 12px;
        }
        .invoice-head-left,
        .invoice-head-center,
        .invoice-head-right {
            display: table-cell;
            vertical-align: top;
            padding: 0 8px;
        }
        .invoice-head-left {
            width: 35%;
        }
        .invoice-head-center {
            width: 30%;
            text-align: center;
        }
        .invoice-head-right {
            width: 35%;
            text-align: right;
        }

        .invoice-head-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
        }

        .invoice-head-brand {
            display: table;
            padding: 3px 0;
        }

        .invoice-head-brand-cell {
            display: table-cell;
            vertical-align: middle;
        }

        .invoice-head-brand-cell.logo-cell {
            width: 70px;
            padding-right: 10px;
        }

        .invoice-head-brandtext > div {
            margin-bottom: 3px;
        }

        .invoice-head-company {
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 700;
            color: #323D1A;
            font-size: 18px;
            line-height: 1.2;
        }

        .invoice-head-sub {
            font-size: 10px;
            color: #66695A;
            line-height: 1.4;
        }

        .invoice-head-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 700;
            font-size: 20px;
            letter-spacing: 0.5px;
            color: #3E4C21;
            text-transform: uppercase;
            margin-bottom: 3px;
            line-height: 1.1;
        }

        .invoice-head-period {
            font-size: 10px;
            font-weight: 700;
            color: #66695A;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }

        .invoice-head-original {
            font-size: 9.5px;
            font-weight: 600;
            color: #9A9C8A;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        .invoice-head-clientname {
            font-weight: 700;
            color: #1F2414;
            font-size: 13px;
            margin-bottom: 3px;
            line-height: 1.2;
        }

        .invoice-head-clientline {
            font-size: 10px;
            color: #66695A;
            line-height: 1.3;
        }

        /* Bandeau numéro / période */
        .invoice-number-section {
            background: #F4F5EC;
            padding: 12px 14px;
            border: 1px solid #CED5AE;
            border-left: 3px solid #3E4C21;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .invoice-number-row {
            display: table;
            width: 100%;
        }

        .invoice-number-row > div {
            display: table-cell;
            vertical-align: middle;
            width: 33.33%;
        }

        .invoice-number-row > div:nth-child(2) {
            text-align: center;
        }

        .invoice-number-row > div:last-child {
            text-align: right;
        }

        .invoice-number-label {
            font-weight: 700;
            color: #3E4C21;
            margin-right: 5px;
            font-size: 10px;
        }

        .invoice-number-value {
            font-weight: 700;
            color: #1F2414;
            font-size: 11px;
        }

        /* Cartes récapitulatives */
        .summary-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 22px;
            border-spacing: 8px;
        }

        .summary-row {
            display: table-row;
        }

        .summary-card {
            display: table-cell;
            padding: 14px 10px;
            text-align: center;
            vertical-align: middle;
            border-radius: 10px;
            color: #ffffff;
        }

        .summary-card.tone-1 { background-color: #3E4C21; } /* Ventes */
        .summary-card.tone-2 { background-color: #6B7A35; } /* Paiements encaissés */
        .summary-card.tone-3 { background-color: #d92d20; } /* Non payé — attention */
        .summary-card.tone-4 { background-color: #283114; } /* Achats */
        .summary-card.tone-5 { background-color: #66695A; } /* Dépenses */
        .summary-card.tone-6 {
            background-color: #1C230D;
        }
        .summary-card.tone-6.negative {
            background-color: #b91c1c;
        }

        .summary-card h3 {
            font-size: 10.5px;
            color: #ffffff;
            opacity: 0.9;
            margin-bottom: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-card .value {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 3px;
        }

        .summary-card .sub-label {
            font-size: 9px;
            color: #ffffff;
            opacity: 0.85;
            margin-top: 2px;
            font-weight: 500;
            line-height: 1.3;
        }

        /* Titres de section */
        .section-title {
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 700;
            color: #323D1A;
            font-size: 14px;
            margin-bottom: 12px;
            margin-top: 18px;
            padding-bottom: 6px;
            border-bottom: 2px solid #3E4C21;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
            background: #ffffff;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        table th {
            background: #323D1A !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 10px 8px;
            text-align: left;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: table-cell;
        }

        table th.text-right {
            text-align: right;
        }

        table td {
            padding: 9px 8px;
            border-bottom: 1px solid #E6E9D5;
            font-size: 10px;
            color: #1F2414;
            display: table-cell;
        }

        table td.text-right {
            text-align: right;
            font-weight: 600;
        }

        table tbody tr:nth-child(even) {
            background-color: #F9F9F3;
        }

        .total-row {
            background: #F4F5EC !important;
        }

        .total-row td {
            border-top: 2px solid #3E4C21;
            border-bottom: none;
            font-size: 11px;
            padding: 11px 8px;
            color: #323D1A;
        }

        /* Montant en lettres */
        .amount-words-box {
            background: #F9F9F3;
            border-left: 4px solid #3E4C21;
            border-radius: 0 10px 10px 0;
            padding: 14px 16px;
            margin: 18px 0;
        }

        .amount-words-title {
            font-weight: 700;
            color: #3E4C21;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .amount-words-text {
            font-size: 11px;
            color: #1F2414;
            font-weight: 600;
            line-height: 1.5;
        }

        .montant-chiffres {
            font-size: 9px;
            margin-top: 5px;
            color: #66695A;
            font-style: italic;
        }

        /* Signatures */
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 20px;
            margin-bottom: 18px;
            border-spacing: 12px;
        }

        .signature-left,
        .signature-right {
            display: table-cell;
            width: 50%;
            padding: 16px;
            border: 1px solid #CED5AE;
            vertical-align: top;
            border-radius: 10px;
            background: #F4F5EC;
        }

        .signature-title {
            font-weight: 700;
            color: #323D1A;
            font-size: 11px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .signature-line {
            border-top: 1px solid #CED5AE;
            margin-top: 45px;
            padding-top: 6px;
            font-size: 10px;
            color: #66695A;
            text-align: center;
        }

        /* Pied de page */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding-top: 14px;
            border-top: 2px solid #3E4C21;
            text-align: center;
            font-size: 10px;
            color: #66695A;
            background: #F4F5EC;
            padding: 14px;
            border-radius: 8px 8px 0 0;
            margin: 0;
            box-sizing: border-box;
        }

        .footer-contact {
            margin-bottom: 6px;
            font-weight: 600;
            color: #1F2414;
        }

        .footer-thanks {
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 700;
            color: #323D1A;
            margin-top: 8px;
            font-size: 11px;
            font-style: italic;
        }

        .footer-date {
            margin-top: 8px;
            font-size: 10px;
            color: #9A9C8A;
        }

        /* Badge de période */
        .period-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-left: 8px;
            vertical-align: middle;
            background: #F4F5EC;
            color: #3E4C21;
            border: 1px solid #CED5AE;
        }
    </style>
</head>
<body>

    @php
        // $company est fourni par le View Composer (AppServiceProvider)

        // Variables sécurisées avec des valeurs par défaut
        $period = $period ?? 'daily';
        $storeId = $storeId ?? null;
        $label = $label ?? '';
        $profit = $profit ?? 0;
        $totalVentes = $totalVentes ?? 0;
        $totalPayesVentes = $totalPayesVentes ?? 0;
        $totalEncaisse = $totalEncaisse ?? 0;
        $totalReste = $totalReste ?? 0;
        $totalAchats = $totalAchats ?? 0;
        $totalDepenses = $totalDepenses ?? 0;
        $breakdown = $breakdown ?? collect();
        $dailyExpenses = $dailyExpenses ?? collect();
        $stores = $stores ?? collect();

        // Filtrer les éléments null ou invalides
        $breakdown = $breakdown->filter(function ($item) {
            return is_array($item) || (is_object($item) && method_exists($item, 'toArray'));
        })->values();

        // Déterminer le titre selon la période
        $periodTitle = 'QUOTIDIEN';
        $subLabel = 'du jour';
        $badgeText = 'Quotidien';

        switch($period) {
            case 'daily':
                $periodTitle = 'QUOTIDIEN';
                $subLabel = 'du jour';
                $badgeText = 'Quotidien';
                break;
            case 'weekly':
                $periodTitle = 'HEBDOMADAIRE';
                $subLabel = 'de la semaine';
                $badgeText = 'Hebdo';
                break;
            case 'monthly':
                $periodTitle = 'MENSUEL';
                $subLabel = 'du mois';
                $badgeText = 'Mensuel';
                break;
            case 'annual':
                $periodTitle = 'ANNUEL';
                $subLabel = 'de l\'année';
                $badgeText = 'Annuel';
                break;
        }

        function nombreEnLettres($nombre) {
            if (!is_numeric($nombre)) {
                return "Montant invalide";
            }

            $nombre = intval($nombre);

            if ($nombre == 0) {
                return "zéro";
            }

            return number_format($nombre, 0, ',', ' ') . " Francs Guinéens";
        }

        // Convertir le profit en lettres
        $profitEnLettres = nombreEnLettres($profit);
    @endphp

    <div class="invoice-head-3col">
        <div class="invoice-head-left">
            <div class="invoice-head-brand">
                <div class="invoice-head-brand-cell logo-cell">
                    @if($company?->logo)
                        <img src="{{ public_path('companies/'.$company->logo) }}" alt="{{ $company?->name ?? 'Logo' }}" class="invoice-head-logo">
                    @endif
                </div>
                <div class="invoice-head-brand-cell">
                    <div class="invoice-head-brandtext">
                        <div class="invoice-head-company">{{ $company?->name ?? 'TELICO' }}</div>
                        @if(!empty($company?->address))
                            <div class="invoice-head-sub">{{ $company->address }}</div>
                        @endif
                        @if(!empty($company?->phone))
                            <div class="invoice-head-sub">{{ $company->phone }}</div>
                        @endif
                        @if(!empty($company?->email))
                            <div class="invoice-head-sub">{{ $company->email }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-head-center">
            <div class="invoice-head-title">
                RAPPORT {{ $periodTitle }}
                <span class="period-badge">{{ $badgeText }}</span>
            </div>
            <div class="invoice-head-period">
                Période: {{ $label }}
            </div>
            <div class="invoice-head-original">Original</div>
        </div>

        <div class="invoice-head-right">
            <div class="invoice-head-clientname">{{ $stores->firstWhere('id', $storeId)?->store_name ?? 'Toutes les boutiques' }}</div>
            <div class="invoice-head-clientline">Type: {{ ucfirst($period) }}</div>
            <div class="invoice-head-clientline">Généré le: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="invoice-number-section">
        <div class="invoice-number-row">
            <div>
                <span class="invoice-number-label">Rapport N°:</span>
                <span class="invoice-number-value">#{{ strtoupper(substr($period, 0, 1)) }}R-{{ now()->format('YmdHis') }}</span>
            </div>
            <div>
                <span class="invoice-number-label">Période:</span>
                <span class="invoice-number-value">{{ $label }}</span>
            </div>
            <div>
                <span class="invoice-number-label">Généré le:</span>
                <span class="invoice-number-value">{{ now()->translatedFormat('d F Y, H:i') }}</span>
            </div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-card tone-1">
                <h3>Ventes</h3>
                <div class="value">{{ number_format($totalVentes, 0, '.', ' ') }} FG</div>
                <div class="sub-label">Payé {{ $subLabel }}: {{ number_format($totalPayesVentes ?? 0, 0, '.', ' ') }} FG</div>
            </div>
            <div class="summary-card tone-2">
                <h3>Paiements encaissés</h3>
                <div class="value">{{ number_format($totalEncaisse, 0, '.', ' ') }} FG</div>
                <div class="sub-label">Période: paiements enregistrés</div>
            </div>
            <div class="summary-card tone-3">
                <h3>Non payé (reste)</h3>
                <div class="value">{{ number_format($totalReste, 0, '.', ' ') }} FG</div>
                <div class="sub-label">Dettes sur factures</div>
            </div>
        </div>
        <div class="summary-row">
            <div class="summary-card tone-4">
                <h3>Achats</h3>
                <div class="value">{{ number_format($totalAchats, 0, '.', ' ') }} FG</div>
                <div class="sub-label">Total achats (achats simples + commandes)</div>
            </div>
            <div class="summary-card tone-5">
                <h3>Dépenses</h3>
                <div class="value">{{ number_format($totalDepenses, 0, '.', ' ') }} FG</div>
                <div class="sub-label">Total dépenses</div>
            </div>
            <div class="summary-card tone-6 {{ $profit < 0 ? 'negative' : '' }}">
                <h3>Solde (approx.)</h3>
                <div class="value">{{ number_format($profit, 0, '.', ' ') }} FG</div>
                <div class="sub-label">Ventes - Achats - Dépenses</div>
            </div>
        </div>
    </div>

    <div class="section-title">Détails par boutique</div>
    <table>
        <thead>
            <tr>
                <th>Boutique</th>
                <th class="text-right">Achats (FG)</th>
                <th class="text-right">Ventes (FG)</th>
                <th class="text-right">Paiements (FG)</th>
                <th class="text-right">Non payé (FG)</th>
                <th class="text-right">Dépenses (FG)</th>
                <th class="text-right">Solde (FG)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($breakdown as $row)
                @php
                    $storeName = $row['store']->store_name ?? 'N/A';
                    $achats = $row['achats'] ?? 0;
                    $ventes = $row['ventes'] ?? 0;
                    $encaisse = $row['encaisse'] ?? 0;
                    $reste = $row['reste'] ?? 0;
                    $depenses = $row['depenses'] ?? 0;
                    $profitRow = $row['profit'] ?? 0;
                @endphp
                <tr>
                    <td style="font-weight: 600;">{{ $storeName }}</td>
                    <td class="text-right">{{ number_format($achats, 0, '.', ' ') }}</td>
                    <td class="text-right">{{ number_format($ventes, 0, '.', ' ') }}</td>
                    <td class="text-right">{{ number_format($encaisse, 0, '.', ' ') }}</td>
                    <td class="text-right">{{ number_format($reste, 0, '.', ' ') }}</td>
                    <td class="text-right">{{ number_format($depenses, 0, '.', ' ') }}</td>
                    <td class="text-right" style="color: {{ $profitRow >= 0 ? '#059669' : '#b91c1c' }}; font-weight: 700;">
                        {{ number_format($profitRow, 0, '.', ' ') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #9A9C8A;">Aucune donnée pour cette période.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($totalAchats, 0, '.', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalVentes, 0, '.', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalEncaisse, 0, '.', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalReste, 0, '.', ' ') }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDepenses, 0, '.', ' ') }}</strong></td>
                <td class="text-right" style="color: {{ $profit >= 0 ? '#059669' : '#b91c1c' }};"><strong>{{ number_format($profit, 0, '.', ' ') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="amount-words-box">
        <div class="amount-words-title">Solde total en lettres</div>
        <div class="amount-words-text">
            Arrêté à la somme de : <strong>{{ $profitEnLettres }} Francs Guinéens GNF</strong>
            <div class="montant-chiffres">
                (En chiffres : {{ number_format($profit, 0, ',', ' ') }} FG)
            </div>
        </div>
    </div>

    @if($dailyExpenses->count() > 0)
        @php
            $expenseTitle = 'Dépenses';
            if ($period === 'daily') {
                $expenseTitle = 'Dépenses quotidiennes';
            } elseif ($period === 'weekly') {
                $expenseTitle = 'Dépenses hebdomadaires';
            } elseif ($period === 'monthly') {
                $expenseTitle = 'Dépenses mensuelles';
            } elseif ($period === 'annual') {
                $expenseTitle = 'Dépenses annuelles';
            }
        @endphp

        <div class="section-title">{{ $expenseTitle }}</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Nombre de dépenses</th>
                    <th class="text-right">Total {{ $period === 'daily' ? 'du jour' : 'de la période' }} (FG)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyExpenses as $dailyExpense)
                    @php
                        $dateObj = \Carbon\Carbon::parse($dailyExpense->expense_date ?? now());
                        $dayTotal = (float) ($dailyExpense->total_amount ?? 0);
                        $expenseCount = (int) ($dailyExpense->expense_count ?? 0);
                    @endphp
                    <tr>
                        <td style="font-weight: 600;">
                            @if($period === 'daily')
                                {{ $dateObj->translatedFormat('l d F Y') }}
                            @elseif($period === 'weekly')
                                Semaine du {{ $dateObj->format('d/m/Y') }}
                            @elseif($period === 'monthly')
                                {{ $dateObj->translatedFormat('F Y') }}
                            @else
                                {{ $dateObj->format('Y') }}
                            @endif
                        </td>
                        <td class="text-right">{{ $expenseCount }}</td>
                        <td class="text-right">{{ number_format($dayTotal, 0, '.', ' ') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ $dailyExpenses->sum('expense_count') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($dailyExpenses->sum('total_amount'), 0, '.', ' ') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="signature-section">
        <div class="signature-left">
            <div class="signature-title">Cachet & Signature</div>
            <div class="signature-line">Nom et cachet</div>
        </div>
        <div class="signature-right">
            <div class="signature-title">Pour {{ $company?->name ?? 'TELICO' }}</div>
            <div class="signature-line">Le Gérant/Signature autorisée</div>
            <div class="signature-line" style="margin-top: 8px;">Nom et signature du responsable</div>
        </div>
    </div>

    <div class="footer">
        @if(!empty($company?->address))
            <div class="footer-contact">{{ $company->address }}</div>
        @endif
        <div class="footer-thanks">Merci pour votre confiance et à bientôt!</div>
        <div class="footer-date">Rapport {{ $period }} généré le {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
