<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        .page-btn {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .btn i {
            margin-right: 5px;
        }

        .btn-added {
            background-color: #3E4C21;
            color: white;
        }

        .btn-added:hover {
            background-color: #323D1A;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
        }

        /* ============================= */
        /* FACTURE — design TELICO */
        /* ============================= */
        #invoiceContent {
            position: relative;
            overflow: hidden;
            color: #1c1917 !important;
            font-family: 'Source Sans 3', Arial, sans-serif;
            background: #ffffff;
            max-width: 850px;
            margin: 0 auto;
            padding: 40px 44px;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        #invoiceContent * {
            color: #1c1917 !important;
        }

        .invoice-watermark {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .invoice-watermark .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            max-width: 60%;
            height: auto;
            opacity: 0.04;
        }

        .facture-inner {
            position: relative;
            z-index: 1;
        }

        /* En-tête */
        .facture-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding-bottom: 22px;
            border-bottom: 3px solid #3E4C21;
            margin-bottom: 24px;
        }

        .facture-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .facture-brand img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border-radius: 10px;
        }

        .facture-brand-name {
            font-family: 'Cinzel', Georgia, serif;
            font-size: 22px;
            font-weight: 700;
            color: #92400e !important;
        }

        .facture-brand-sub {
            font-size: 11.5px;
            color: #78716c !important;
            margin-top: 2px;
            line-height: 1.5;
        }

        .facture-heading {
            text-align: right;
        }

        .facture-heading .label {
            font-family: 'Cinzel', Georgia, serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #3E4C21 !important;
            text-transform: uppercase;
        }

        .facture-heading .meta {
            margin-top: 8px;
            font-size: 12.5px;
            color: #57534e !important;
        }

        .facture-heading .meta strong {
            color: #1c1917 !important;
        }

        .facture-heading .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: #F4F5EC;
            color: #b45309 !important;
            border: 1px solid #CED5AE;
        }

        /* Cartes d'info */
        .facture-info-grid {
            display: flex;
            gap: 16px;
            margin-bottom: 26px;
        }

        .info-card {
            flex: 1;
            background: #F4F5EC;
            border: 1px solid #CED5AE;
            border-radius: 12px;
            padding: 16px 18px;
        }

        .info-card .info-title {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #b45309 !important;
            margin-bottom: 10px;
        }

        .info-card .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            padding: 3px 0;
        }

        .info-card .info-row .k {
            color: #78716c !important;
        }

        .info-card .info-row .v {
            font-weight: 600;
            text-align: right;
        }

        /* Tableau produits */
        .invoice-products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            font-size: 12.5px;
        }

        .invoice-products-table thead th {
            background: #92400e !important;
            color: #ffffff !important;
            font-weight: 600;
            text-align: left;
            padding: 11px 12px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .invoice-products-table thead th:nth-child(3),
        .invoice-products-table thead th:nth-child(4),
        .invoice-products-table thead th:nth-child(5) {
            text-align: right;
        }

        .invoice-products-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1e9dd;
        }

        .invoice-products-table tbody tr:nth-child(even) {
            background: #fdfaf5;
        }

        .invoice-products-table td:nth-child(3),
        .invoice-products-table td:nth-child(4),
        .invoice-products-table td:nth-child(5) {
            text-align: right;
        }

        .invoice-products-table tfoot .invoice-total td {
            padding: 14px 12px;
            background: #F4F5EC;
            border-top: 2px solid #3E4C21;
            font-size: 14px;
        }

        .invoice-products-table tfoot .invoice-total td:last-child {
            color: #92400e !important;
            font-weight: 800;
        }

        /* Montant en lettres */
        .amount-in-words {
            background: #faf9f7;
            border-left: 4px solid #3E4C21;
            border-radius: 0 10px 10px 0;
            padding: 14px 18px;
            font-size: 12.5px;
            margin-bottom: 30px;
        }

        .amount-in-words strong {
            display: block;
            font-size: 10.5px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #b45309 !important;
            margin-bottom: 4px;
        }

        .amount-in-words em {
            font-style: italic;
        }

        /* Signatures */
        .signature-section {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            margin-top: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .signature-box {
            flex: 1 1 200px;
            min-width: 200px;
            text-align: center;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signature-line {
            width: 100%;
            height: 46px;
            border-bottom: 1.5px solid #d6ccc0;
            margin-bottom: 8px;
        }

        .signature-box p {
            margin: 0;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.4px;
            color: #57534e !important;
        }

        /* Pied de page */
        .company-info {
            text-align: center;
            border-top: 1px solid #f1e9dd;
            padding-top: 16px;
            font-size: 11px;
            color: #78716c !important;
        }

        .company-info h4 {
            font-family: 'Cinzel', Georgia, serif;
            font-size: 14px;
            color: #92400e !important;
            margin-bottom: 3px;
        }

        .company-info p {
            margin: 2px 0;
        }

        /* Styles pour l'impression */
        @media print {
            .mention-interne { display: none !important; }
            #global-loader,
            .header,
            .sidebar,
            .page-header,
            .no-print {
                display: none !important;
            }

            body,
            .main-wrapper,
            .page-wrapper,
            .content,
            .card,
            .card-body,
            #invoiceContent {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                width: 100%;
            }

            #invoiceContent {
                padding: 15px;
                margin: 0;
                border-radius: 0;
            }

            .invoice-products-table {
                width: 100%;
                border-collapse: collapse;
            }

            .invoice-products-table thead {
                display: table-header-group;
            }

            .invoice-products-table tfoot {
                display: table-footer-group;
            }

            .invoice-products-table tr {
                page-break-inside: avoid;
            }

            .signature-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div id="global-loader">
        <div class="whirly-loader"></div>
    </div>

    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header no-print">
                    <div class="page-title">
                        <h4>Détails de Vente</h4>
                        <h6>Voir les détails de vente</h6>
                    </div>
                    <div class="page-btn">
                        <button id="printInvoice" class="btn btn-added" title="Imprimer">
                            <i class="fas fa-print me-2"></i> Imprimer
                        </button>
                        <button id="downloadPdf" class="btn btn-added" title="Télécharger en PDF">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger PDF
                        </button>
                        <a href="{{ route('factures.index') }}" class="btn btn-cancel">
                            <i class="fas fa-arrow-left me-2"></i> Retour
                        </a>
                    </div>
                </div>

                <div class="card" style="background:#f8f5f0;">
                    <div class="card-body">
                        {{-- $company est fourni par le View Composer (AppServiceProvider) --}}
                        <div id="invoiceContent">
                            <div class="invoice-watermark" aria-hidden="true">
                                <img class="watermark-logo" src="{{ asset('companies/' . ($company?->logo ?? 'telico.png')) }}" alt="">
                            </div>

                            <div class="facture-inner">
                                <!-- En-tête -->
                                <div class="facture-header">
                                    <div class="facture-brand">
                                        <img src="{{ asset('companies/' . ($company?->logo ?? 'telico.png')) }}" alt="{{ $company?->name ?? 'TELICO' }}">
                                        <div>
                                            <div class="facture-brand-name">{{ $company?->name ?? 'TELICO' }}</div>
                                            <div class="facture-brand-sub">
                                                @if(!empty($company?->address)) {{ $company->address }}<br>@endif
                                                Tél: {{ $company?->phone ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="facture-heading">
                                        <div class="label">Facture</div>
                                        <div class="meta">
                                            <strong>N° {{ $facture }}</strong><br>
                                            {{ \Carbon\Carbon::parse($laFacture->created_at)->format('d/m/Y à H:i') }}
                                        </div>
                                        <div class="badge">{{ $laFacture->statut ?? 'N/A' }}</div>
                                    </div>
                                </div>

                                <!-- Infos client / vente -->
                                <div class="facture-info-grid">
                                    <div class="info-card">
                                        <div class="info-title">Facturé à</div>
                                        <div class="info-row"><span class="k">Client</span><span class="v">{{ $customer->customerName ?? 'N/A' }}</span></div>
                                        <div class="info-row"><span class="k">Téléphone</span><span class="v">{{ $customer->tel ?? 'N/A' }}</span></div>
                                        <div class="info-row"><span class="k">Adresse</span><span class="v">{{ $customer->address ?? 'N/A' }}</span></div>
                                    </div>
                                    <div class="info-card">
                                        <div class="info-title">Détails de la vente</div>
                                        <div class="info-row"><span class="k">Boutique</span><span class="v">{{ $laFacture->store?->description ?? $laFacture->store?->store_name ?? 'N/A' }}</span></div>
                                        <div class="info-row"><span class="k">Gérant</span><span class="v">{{ auth()->user()->name }}</span></div>
                                        <div class="info-row"><span class="k">Livraison</span><span class="v">{{ $laFacture->livraison == 'livré' ? 'Livré' : 'Non livré' }}</span></div>
                                    </div>
                                </div>

                                <!-- Tableau produits -->
                                <table class="invoice-products-table">
                                    <thead>
                                        <tr>
                                            <th style="width:6%">N°</th>
                                            <th style="width:39%">Produit</th>
                                            <th style="width:15%">Quantité</th>
                                            <th style="width:20%">Prix Unitaire</th>
                                            <th style="width:20%">Montant Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoice as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                {{-- Mention interne : visible à l'écran, jamais sur la facture imprimée pour le client. --}}
                                                <td>{{ $item->produit }}@if ($item->is_third_party)<small class="mention-interne" data-html2canvas-ignore="true" style="display:block;color:#8A6A24;"><i class="fa fa-handshake"></i> Pris chez {{ $item->partner?->name ?? 'un partenaire' }} — payé {{ numberDelimiter($item->purchase_price_from_partner) }} FG / {{ $item->mot_unite }}</small>@endif</td>
                                                <td>{{ $item->quantite_affichee }}</td>
                                                <td>{{ numberDelimiter($item->prix_affiche) }} FG</td>
                                                <td>{{ numberDelimiter($item->prixTotal) }} FG</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="invoice-total">
                                            <td colspan="4">GRAND TOTAL</td>
                                            <td>{{ numberDelimiter($laFacture->montant_total) }} FG</td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <!-- Montant en lettres -->
                                <div class="amount-in-words">
                                    <strong>Montant en lettres</strong>
                                    <em>
                                        Arrêtée à la somme de {{ ucfirst(numberToWords($laFacture->montant_total)) }} Francs Guinéens (GNF)
                                    </em>
                                </div>

                                <!-- Signatures -->
                                <div class="signature-section">
                                    <div class="signature-box">
                                        <div class="signature-line"></div>
                                        <p>Signature du client</p>
                                    </div>
                                    <div class="signature-box">
                                        <div class="signature-line"></div>
                                        <p>Pour {{ strtoupper($company?->name ?? 'TELICO') }}</p>
                                    </div>
                                </div>

                                <!-- Pied de page -->
                                <div class="company-info">
                                    <h4>{{ $company?->name ?? 'TELICO' }}</h4>
                                    <p>{{ $company?->address ?? '' }}</p>
                                    <p>Tél: {{ $company?->phone ?? '' }} | Email: {{ $company?->email ?? '' }}</p>
                                    <p>Facture générée le {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.getElementById('downloadPdf');
            const printBtn = document.getElementById('printInvoice');
            const element = document.getElementById('invoiceContent');

            if (!downloadBtn || !element || !printBtn) return;

            printBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });

            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();

                const btn = downloadBtn;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Génération...';
                btn.disabled = true;

                // Facture remise au client : les mentions internes (produit pris
                // chez un partenaire, prix payé) ne doivent jamais apparaître dans
                // le PDF. Le PDF est fabriqué à partir de l'écran : on les retire
                // le temps de la génération, puis on les remet.
                const mentions = [...element.querySelectorAll('.mention-interne')];
                const affichages = mentions.map(m => m.style.getPropertyValue('display'));
                mentions.forEach(m => m.style.setProperty('display', 'none', 'important'));
                const remettreMentions = () => mentions.forEach((m, i) => m.style.setProperty('display', affichages[i]));

                const opt = {
                    margin: [10, 10, 15, 10],
                    filename: 'Facture_{{ $facture }}.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        scrollY: 0
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait'
                    },
                    pagebreak: {
                        mode: ['css', 'legacy']
                    }
                };

                html2pdf()
                    .set(opt)
                    .from(element)
                    .save()
                    .then(() => {
                        remettreMentions();
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    })
                    .catch(() => {
                        remettreMentions();
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
            });
        });
    </script>
</body>
</html>
