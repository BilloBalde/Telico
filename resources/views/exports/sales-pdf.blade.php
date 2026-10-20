{{-- resources/views/exports/sales-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Export</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3E4C21;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #3E4C21;
            font-size: 24px;
            margin: 0 0 5px 0;
        }
        
        .header h3 {
            color: #666;
            font-size: 16px;
            margin: 0;
            font-weight: normal;
        }

        .bon-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .bon-header h1 {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #000 !important;
        }
        
        .bon-header h3 {
            font-size: 18px;
            color: #555 !important;
            margin-top: 5px;
        }
        
        /* Style pour le logo dans l'en-tête du document */
        .document-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .document-logo img {
            max-height: 60px;
            width: auto;
            margin-right: 15px;
        }
        
        .document-title {
            text-align: left;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
            font-size: 11px;
        }
        
        .filter-info {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3E4C21;
            font-size: 11px;
        }
        
        .filter-info strong {
            color: #3E4C21;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        th {
            background: #3E4C21;
            color: white;
            font-weight: bold;
            padding: 10px 8px;
            text-align: center;
            border: 1px solid #283114;
        }
        
        td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        td.product-info {
            text-align: left;
        }
        
        .product-name {
            font-weight: bold;
            color: #333;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        tr:hover {
            background: #F9F9F3;
        }
        
        .total-row {
            background: #F4F5EC !important;
            font-weight: bold;
        }
        
        .total-row td {
            border-top: 2px solid #3E4C21;
        }
        
        .summary-box {
            margin-top: 30px;
            padding: 15px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        
        .summary-value {
            color: #333;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        
        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #dff0d8;
            color: #3c763d;
        }
        
        .badge-warning {
            background: #fcf8e3;
            color: #8a6d3b;
        }
        
        .badge-danger {
            background: #f2dede;
            color: #a94442;
        }
        
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    @php
        // $company vient de SaleController::exportPdf()
        $totalAmount = $sales->sum('prixTotal');
        $totalInterest = $sales->sum('interet');
        $totalQuantity = $sales->sum('quantity');
    @endphp

    @php
        // Dompdf résout mal les chemins Windows passés tels quels à public_path()
        // (mélange de \ et /, caractères non-ASCII dans le chemin utilisateur) — le
        // logo n'apparaissait pas dans le PDF. Encodage base64 pour s'affranchir
        // totalement de la résolution de chemin par Dompdf.
        $logoPath = ($company && $company->logo && file_exists(public_path('companies/'.$company->logo)))
            ? public_path('companies/'.$company->logo)
            : null;
        $logoDataUri = $logoPath
            ? 'data:'.mime_content_type($logoPath).';base64,'.base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <!-- Header -->
    <div class="bon-header">
        <div class="document-logo">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="{{ $company->name ?? 'Logo' }}">
            @endif
            <div class="document-title">
                <h1>RAPPORT DES VENTES</h1>
                <h3>{{ $company?->name ?? 'TELICO' }}</h3>
            </div>
        </div>
    </div>

    <!-- Company Info -->
    <div class="company-info">
        <p>{{ $company?->address ?? '' }} | Tél: {{ $company?->phone ?? '' }} | Email: {{ $company?->email ?? '' }}</p>
    </div>

    <!-- Filter Info -->
    @if(request()->anyFilled(['numeroFacture', 'product_id', 'created_at']))
    <div class="filter-info">
        <strong>Filtres appliqués:</strong>
        @if(request('numeroFacture'))
            <span>Facture: {{ request('numeroFacture') }}</span> | 
        @endif
        @if(request('product_id') && $produits)
            <span>Produit: {{ $produits->firstWhere('id', request('product_id'))?->libelle ?? request('product_id') }}</span> | 
        @endif
        @if(request('created_at'))
            <span>Date: {{ \Carbon\Carbon::parse(request('created_at'))->format('d/m/Y') }}</span>
        @endif
    </div>
    @endif

    <!-- Date Range -->
    <div style="margin-bottom: 15px; text-align: right;">
        <strong>Période:</strong> Du {{ now()->startOfMonth()->format('d/m/Y') }} au {{ now()->format('d/m/Y') }}
        <br>
        <strong>Date d'export:</strong> {{ now()->format('d/m/Y H:i:s') }}
    </div>

    <!-- Sales Table -->
    <table>
        <thead>
            <tr>
                <th width="5%">N°</th>
                <th width="12%">N° Facture</th>
                <th width="25%">Produit</th>
                <th width="8%">Qté</th>
                <th width="12%">Prix par unité</th>
                <th width="12%">Prix Total</th>
                <th width="12%">Intérêt</th>
                <th width="14%">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $sale->numeroFacture }}</td>
                <td class="product-info">
                    <span class="product-name">{{ $sale->produit }}</span>
                    @if ($sale->is_third_party)<br><small style="color:#8A6A24;">Pris chez {{ $sale->partner?->name ?? 'un partenaire' }}</small>@endif
                </td>
                <td>{{ $sale->quantite_affichee }}</td>
                <td>{{ number_format($sale->prix, 0, ',', ' ') }} FG</td>
                <td>{{ number_format($sale->prixTotal, 0, ',', ' ') }} FG</td>
                <td>{{ number_format($sale->interet, 0, ',', ' ') }} FG</td>
                <td>{{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 30px;">
                    Aucune vente trouvée
                </td>
            </tr>
            @endforelse
        </tbody>
        
        @if($sales->count() > 0)
        <!-- Totaux -->
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right; font-weight: bold;">TOTAUX</td>
                <td style="font-weight: bold;">{{ $totalQuantity }}</td>
                <td></td>
                <td style="font-weight: bold;">{{ number_format($totalAmount, 0, ',', ' ') }} FG</td>
                <td style="font-weight: bold;">{{ number_format($totalInterest, 0, ',', ' ') }} FG</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Summary Box -->
    <div class="summary-box">
        <h4 style="margin-top: 0; color: #3E4C21;">RÉSUMÉ</h4>
        <div class="summary-row">
            <span class="summary-label">Nombre total de ventes:</span>
            <span class="summary-value">{{ $sales->count() }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Quantité totale vendue:</span>
            <span class="summary-value">{{ \App\Models\Product::resumeQuantites($sales, 'quantity') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Chiffre d'affaires total:</span>
            <span class="summary-value">{{ number_format($totalAmount, 0, ',', ' ') }} FG</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Intérêt total généré:</span>
            <span class="summary-value">{{ number_format($totalInterest, 0, ',', ' ') }} FG</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Marge moyenne:</span>
            <span class="summary-value">
                @if($totalAmount > 0)
                    {{ number_format(($totalInterest / $totalAmount) * 100, 2) }}%
                @else
                    0%
                @endif
            </span>
        </div>
    </div>

    <!-- Montant en lettres (optionnel) -->
    @if($totalAmount > 0)
    <div style="margin: 20px 0; font-style: italic; font-size: 11px;">
        <strong>Arrêté le présent rapport à la somme totale de:</strong><br>
        <em>{{ ucfirst(numberToWords($totalAmount)) }} Francs Guinéens</em>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signature">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Établi par</strong><br>
                <small>{{ auth()->user()->name }}</small>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Approuvé par</strong><br>
                <small>Direction</small>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>