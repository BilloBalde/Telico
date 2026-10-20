{{-- resources/views/factures/bon_de_commande.blade.php --}}
<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<head>
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
    
    <style>
        /* Style spécifique au Bon de Commande - Version compacte avec lisibilité améliorée */
        #bonDeCommandeContent {
            position: relative;
            overflow: visible;
            color: #000000 !important;
            font-family: 'Arial', sans-serif;
            background: white;
            padding: 8px;
            font-size: 20px;      /* augmenté de 10px à 11px */
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        
        #bonDeCommandeContent * {
            color: #000000 !important;
        }
        
        /* Boutons (inchangés) */
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
        .btn i { margin-right: 5px; }
        .btn-added { background-color: #3E4C21; color: white; }
        .btn-added:hover { background-color: #ff820e; }
        .btn-cancel { background-color: #6c757d; color: white; }
        .btn-cancel:hover { background-color: #5a6268; }
        
        /* En-tête compact */
        .bon-header {
            text-align: center;
            margin-bottom: 12px;      /* légère augmentation */
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
        }
        .bon-header h1 {
            font-size: 15px;          /* augmenté de 16px à 18px */
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 2px 0;
        }
        .bon-header h3 {
            font-size: 15px;          /* augmenté de 11px à 12px */
            margin: 2px 0;
        }
        .document-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        .document-logo img {
            max-height: 40px;          /* légère augmentation */
            width: auto;
            margin-right: 10px;
        }
        .document-title {
            text-align: left;
        }
        
        /* Référence compacte */
        .bon-reference {
            background: #f5f5f5;
            padding: 6px 10px;        /* légère augmentation */
            margin-bottom: 10px;
            border-left: 3px solid #333;
            font-size: 15px;          /* augmenté de 9px à 10px */
        }
        .bon-reference strong {
            font-weight: 600;
            min-width: 100px;
            display: inline-block;
        }
        
        /* Infos client compactes */
        .client-info-bon {
            background: #f9f9f9;
            padding: 8px 12px;        /* légère augmentation */
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .client-info-bon h4 {
            font-size: 15px;          /* augmenté de 11px à 12px */
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        .client-info-bon-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }
        .client-info-bon-item .label {
            font-weight: 600;
            font-size: 10px;           /* augmenté de 8px à 9px */
            margin-bottom: 1px;
        }
        .client-info-bon-item .value {
            font-size: 10px;          /* augmenté de 9px à 10px */
            font-weight: 500;
        }
        
        /* Tableau compact */
        .bon-products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9.5px;         /* augmenté de 8.5px à 9.5px */
        }
        .bon-products-table th {
            background: #e9ecef !important;
            font-weight: 600;
            text-align: center;
            padding: 6px 4px;          /* légère augmentation */
            border: 1px solid #ccc;
        }
        .bon-products-table td {
            padding: 5px 4px;          /* légère augmentation */
            border: 1px solid #ccc;
            text-align: center;
        }
        .bon-products-table td:nth-child(2) {
            text-align: left;
        }
        
        /* Résumé compact */
        .bon-summary {
            margin-top: 10px;
            padding: 8px 12px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .bon-summary-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
            font-size: 12px;           /* augmenté de 9px à 10px */
        }
        .bon-summary-label {
            font-weight: 600;
            min-width: 120px;
            text-align: right;
            margin-right: 15px;
        }
        .bon-summary-value {
            min-width: 120px;
            text-align: right;
            font-weight: 500;
        }
        .bon-summary-total {
            font-size: 12px;           /* augmenté de 11px à 12px */
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 6px;
            margin-top: 6px;
        }
        
        /* Montant en lettres compact */
        .amount-letters {
            margin: 8px 0;
            font-style: italic;
            font-size: 12px;          /* augmenté de 8.5px à 9.5px */
        }
        
        /* Conditions compactes */
        .bon-conditions {
            margin-top: 8px;
            padding: 8px 10px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
            font-size: 10px;          /* augmenté de 7.5px à 8.5px */
        }
        .bon-conditions ul {
            margin-top: 4px;
            padding-left: 18px;
        }
        .bon-conditions li {
            margin-bottom: 2px;
        }
        
        /* Signatures compactes */
        .bon-footer {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .bon-signature {
            width: 180px;
            text-align: center;
        }
        .bon-signature-line {
            border-top: 1px solid #333;
            margin-top: 25px;          /* légère augmentation */
            padding-top: 5px;
            font-size: 9px;            /* augmenté de 8px à 9px */
        }
        .bon-signature-line small {
            font-size: 8px;            /* augmenté de 7px à 8px */
        }
        
        /* Pied de page compact */
        .bon-footer-text {
            margin-top: 10px;
            text-align: center;
            font-size: 9px;          /* augmenté de 6.5px à 7.5px */
            border-top: 1px solid #eee;
            padding-top: 5px;
            color: #999 !important;
        }
        
        /* Filigrane */
        .bon-watermark {
            position: absolute;
            opacity: 0.08;
            font-size: 28px;
            font-weight: bold;
            transform: rotate(-45deg);
            top: 40%;
            left: 40%;
            transform: translate(-50%, -50%) rotate(-45deg);
            white-space: nowrap;
            color: #999 !important;
            z-index: 0;
            pointer-events: none;
        }
        
        /* ============================================
           STYLES POUR L'IMPRESSION (fond blanc)
           ============================================ */
        @media print {
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
            #bonDeCommandeContent {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                width: 100%;
            }
            
            #bonDeCommandeContent {
                padding: 5px !important;
            }
            
            .bon-products-table,
            .bon-products-table tr,
            .bon-products-table td,
            .bon-products-table th {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            thead {
                display: table-header-group;
            }
            
            .bon-footer,
            .bon-signature,
            .bon-conditions {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* ============================================
           STYLES POUR LA GÉNÉRATION PDF (fond blanc)
           ============================================ */
        .pdf-mode body,
        .pdf-mode .main-wrapper,
        .pdf-mode .page-wrapper,
        .pdf-mode .content,
        .pdf-mode .card,
        .pdf-mode .card-body,
        .pdf-mode #bonDeCommandeContent {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        .pdf-mode #bonDeCommandeContent {
            padding: 5px !important;
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
                        <h4>Bon de Commande</h4>
                        <h6>Détails du bon de commande</h6>
                    </div>
                    <div class="page-btn">
                        <button id="printBon" class="btn btn-added" title="Imprimer">
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

                <div class="card">
                    <div class="card-body">
                        {{-- $company est fourni par le View Composer (AppServiceProvider) --}}
                        <div id="bonDeCommandeContent">
                            <!-- Filigrane -->
                            <div class="bon-watermark">BON DE COMMANDE</div>

                            <!-- En-tête avec logo -->
                            <div class="bon-header">
                                <div class="document-logo">
                                    @if($company && $company->logo)
                                        <img src="{{ asset('companies/'.$company->logo) }}" alt="{{ $company->name ?? 'Logo' }}">
                                    @else
                                        <img src="{{ asset('companies/telico.png') }}" alt="{{ config('app.name') }}">
                                    @endif
                                    <div class="document-title">
                                        <h1>BON DE COMMANDE</h1>
                                        <h3>{{ $company?->name ?? 'TELICO' }}</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Référence -->
                            <div class="bon-reference">
                                <div style="display: flex; justify-content: space-between;">
                                    <div><strong>N° Bon de Commande:</strong> BC-{{ $facture }}</div>
                                    <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($laFacture->created_at)->format('d/m/Y H:i') }}</div>
                                </div>
                                <div style="margin-top: 4px;">
                                    <strong>Référence Facture:</strong> {{ $facture }}
                                </div>
                            </div>

                            <!-- Informations Client -->
                            <div class="client-info-bon">
                                <h4>INFORMATIONS CLIENT</h4>
                                <div class="client-info-bon-grid">
                                    <div class="client-info-bon-item">
                                        <span class="label">Nom / Raison Sociale</span>
                                        <span class="value">{{ $customer->customerName ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-bon-item">
                                        <span class="label">Marque / Enseigne</span>
                                        <span class="value">{{ $customer->mark ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-bon-item">
                                        <span class="label">Téléphone</span>
                                        <span class="value">{{ $customer->tel ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-bon-item">
                                        <span class="label">Email</span>
                                        <span class="value">{{ $customer->email ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-bon-item">
                                        <span class="label">Adresse</span>
                                        <span class="value">{{ $customer->address ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-bon-item">
                                        <span class="label">Boutique / Magasin</span>
                                        <span class="value">{{ $laFacture->store?->store_name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tableau des produits -->
                            <table class="bon-products-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">N°</th>
                                        <th style="width: 35%">Désignation du produit</th>
                                        <th style="width: 15%">Qté</th>
                                        <th style="width: 20%">Prix Unitaire</th>
                                        <th style="width: 25%">Montant Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td style="text-align: left;">{{ $item->produit }}</td>
                                        <td>{{ $item->quantite_affichee }}</td>
                                        <td>{{ number_format($item->prix_affiche, 0, ',', ' ') }} FG</td>
                                        <td>{{ number_format($item->prixTotal, 0, ',', ' ') }} FG</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Résumé des montants -->
                            <div class="bon-summary">
                                <div class="bon-summary-row">
                                    <span class="bon-summary-label">Articles :</span>
                                    <span class="bon-summary-value">{{ \App\Models\Product::resumeQuantites($invoice, 'quantity') }}</span>
                                </div>
                                <div class="bon-summary-row bon-summary-total">
                                    <span class="bon-summary-label">GRAND TOTAL:</span>
                                    <span class="bon-summary-value">{{ number_format($laFacture->montant_total, 0, ',', ' ') }} FG</span>
                                </div>
                                
                                @if($laFacture->avance > 0)
                                <div class="bon-summary-row">
                                    <span class="bon-summary-label">Acompte versé :</span>
                                    <span class="bon-summary-value">{{ number_format($laFacture->avance, 0, ',', ' ') }} FG</span>
                                </div>
                                <div class="bon-summary-row">
                                    <span class="bon-summary-label">Reste à payer :</span>
                                    <span class="bon-summary-value">{{ number_format($laFacture->montant_total - $laFacture->avance, 0, ',', ' ') }} FG</span>
                                </div>
                                @endif
                            </div>

                            <!-- Montant en lettres -->
                            <div class="amount-letters">
                                <strong>Arrêté le présent bon de commande à la somme de :</strong><br>
                                <em>{{ ucfirst(numberToWords($laFacture->montant_total)) }} Francs Guinéens</em>
                            </div>

                            <!-- Conditions -->
                            <div class="bon-conditions">
                                <strong>Conditions générales :</strong>
                                <ul>
                                    <li>La commande est ferme et définitive à compter de la date d'émission</li>
                                    <li>Délai de livraison : à convenir avec le service commercial</li>
                                    <li>Règlement : à la livraison ou selon échéancier convenu</li>
                                    <li>Réserve de propriété : les marchandises restent la propriété du vendeur jusqu'au paiement intégral</li>
                                </ul>
                            </div>

                            <!-- Signatures -->
                            <div class="bon-footer">
                                <div class="bon-signature">
                                    <div class="bon-signature-line">
                                        <strong>Signature du Client</strong><br>
                                    </div>
                                </div>
                                <div class="bon-signature">
                                    <div class="bon-signature-line">
                                        <strong>Pour {{ $company?->name ?? 'TELICO' }}</strong><br>
                                        <small>{{ auth()->user()->name }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Pied de page -->
                            <div class="bon-footer-text">
                                <p>Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }} - {{ $company?->address ?? '' }} - Tél: {{ $company?->phone ?? '' }} | Email: {{ $company?->email ?? '' }}</p>
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
            const printBtn = document.getElementById('printBon');
            const element = document.getElementById('bonDeCommandeContent');
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.querySelector('.page-header');

            if (!downloadBtn || !element || !printBtn) return;

            // Impression
            printBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });

            // PDF
            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (typeof html2pdf === 'undefined') {
                    alert('La bibliothèque PDF n\'est pas encore chargée. Veuillez rafraîchir la page.');
                    return;
                }

                const filename = 'Bon_Commande_{{ $facture }}.pdf';

                // Ajouter classe pour fond blanc
                document.body.classList.add('pdf-mode');

                // Masquer interface
                if (header) header.style.display = 'none';
                if (sidebar) sidebar.style.display = 'none';
                if (pageHeader) pageHeader.style.display = 'none';

                const btn = downloadBtn;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Génération...';
                btn.disabled = true;

                const opt = {
                    margin: [5, 8, 5, 8],   // marges conservées
                    filename: filename,
                    enableLinks: false,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 1.5, useCORS: true, backgroundColor: '#ffffff' },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true },
                    pagebreak: { mode: 'css', avoid: ['tr', '.bon-footer', '.bon-conditions'] }
                };

                html2pdf().set(opt).from(element).save()
                    .then(function() {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.classList.remove('pdf-mode');
                    })
                    .catch(function() {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.classList.remove('pdf-mode');
                        alert('Erreur lors de la génération du PDF.');
                    });
            });
        });
    </script>
</body>
</html>