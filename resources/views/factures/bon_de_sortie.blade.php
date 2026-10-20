{{-- resources/views/factures/bon_de_sortie.blade.php --}}
<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<head>
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
    <style>
        /* Style spécifique au Bon de Sortie */
        #bonDeSortieContent {
            position: relative;
            overflow: visible;
            color: #000000 !important;
            font-family: 'Arial', sans-serif;
            background: white;
            padding: 20px;
        }
        
        #bonDeSortieContent * {
            color: #000000 !important;
        }
        
        /* Style pour les boutons inline */
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
            background-color: #ff820e;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        
        .bon-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .bon-header h1 {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000 !important;
            margin: 5px 0;
        }
        
        .bon-header h3 {
            font-size: 16px;
            color: #555 !important;
            margin: 5px 0;
        }
        
        /* Style pour le logo dans l'en-tête du document */
        .document-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .document-logo img {
            max-height: 50px;
            width: auto;
            margin-right: 15px;
        }
        
        .document-title {
            text-align: left;
        }
        
        /* Style spécifique au Bon de Sortie */
        .sortie-header {
            background: #f0f0f0;
            padding: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
        }
        
        .sortie-badge {
            background: #dc3545;
            color: white !important;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .sortie-badge * {
            color: white !important;
        }
        
        .info-block {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #333;
            font-size: 14px;
        }
        
        .info-block h4 {
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-item .label {
            font-weight: 600;
            font-size: 12px;
            color: #666 !important;
            margin-bottom: 2px;
        }
        
        .info-item .value {
            font-size: 14px;
            font-weight: 500;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
        }
        
        .products-table th {
            background: #e9ecef !important;
            color: #000 !important;
            font-weight: 600;
            text-align: center;
            padding: 8px 4px;
            border: 1px solid #ccc;
        }
        
        .products-table td {
            padding: 6px 4px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }
        
        .products-table td:nth-child(3) {
            text-align: left;
        }
        
        .products-table img {
            max-width: 40px;
            max-height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Update your delivery-info styles with this */
        .delivery-info {
            margin-top: 20px;
            padding: 15px 0;
            border-top: 1px solid #ccc;
            font-size: 12px;
        }

        .delivery-info strong {
            display: block;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .delivery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 8px;
        }

        .delivery-grid > div {
            min-width: 0; /* Prevents overflow on small screens */
        }

        .delivery-grid .label {
            font-weight: 600;
            font-size: 11px;
            color: #666 !important;
            display: block;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .delivery-grid .value {
            font-size: 12px;
            min-height: 20px;
            padding-bottom: 2px;
            width: 100%;
            word-break: break-word;
        }

        /* Mobile styles */
        @media screen and (max-width: 768px) {
            .delivery-grid {
                grid-template-columns: 1fr; /* Stack vertically on mobile */
                gap: 15px;
            }
            
            .delivery-grid .label {
                white-space: normal; /* Allow label to wrap */
                font-size: 12px;
            }
            
            .delivery-grid .value {
                width: 100%;
            }
        }

        /* Tablet styles */
        @media screen and (min-width: 769px) and (max-width: 1024px) {
            .delivery-grid {
                gap: 15px;
            }
        }
        
        /* Conteneur pour les signatures - ne jamais couper */
        .signature-container {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin-top: 50px;
            margin-bottom: 20px;
            display: block;
            width: 100%;
        }
        
        .footer-section {
            display: flex;
            justify-content: space-between;
            padding: 20px 0 10px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            width: 100%;
            margin-top: 30px;
            padding-top: 8px;
        }
        
        .signature-box strong {
            display: block;
            margin-top: 5px;
            font-size: 12px;
        }
        
        .signature-box small {
            font-size: 10px;
            color: #666 !important;
        }
        
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 30px;
            font-weight: bold;
            transform: rotate(-45deg);
            top: 30%;
            left: 30%;
            transform: translate(-50%, -50%) rotate(-45deg);
            white-space: nowrap;
            color: #999 !important;
            z-index: 0;
            pointer-events: none;
        }
        
        .page-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666 !important;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        .page-footer p {
            margin: 2px 0;
        }
        
        /* Force un saut de page avant si nécessaire */
        .force-page-break-before {
            page-break-before: always !important;
            break-before: page !important;
        }

        /* ============================================
           STYLES POUR L'IMPRESSION (fond blanc)
           ============================================ */
        /* Mentions internes : jamais sur le bon imprimé ni sur le PDF. */
        body.pdf-mode .mention-interne { display: none !important; }

        @media print {
            .mention-interne { display: none !important; }
            /* Cacher les éléments d'interface */
            #global-loader,
            .header,
            .sidebar,
            .page-header,
            .no-print {
                display: none !important;
            }
            
            /* Forcer tous les conteneurs à fond blanc */
            body,
            .main-wrapper,
            .page-wrapper,
            .content,
            .card,
            .card-body,
            #bonDeSortieContent {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                width: 100%;
            }
            
            #bonDeSortieContent {
                padding: 5px !important;
            }
            
            .products-table {
                page-break-inside: auto;
            }
            
            .products-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tfoot {
                display: table-footer-group;
            }
            
            /* Forcer les signatures à rester ensemble */
            .signature-container,
            .footer-section,
            .signature-box,
            .page-footer {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-before: auto !important;
                page-break-after: auto !important;
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
        .pdf-mode #bonDeSortieContent {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        .pdf-mode #bonDeSortieContent {
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
                        <h4>Bon de Sortie</h4>
                        <h6>Bon de sortie / Bon de livraison</h6>
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
                        <div id="bonDeSortieContent">
                            <!-- Filigrane -->
                            <div class="watermark">BON DE SORTIE</div>

                            <!-- En-tête avec logo -->
                            <div class="bon-header">
                                <div class="document-logo">
                                    @if($company && $company->logo)
                                        <img src="{{ asset('companies/'.$company->logo) }}" alt="{{ $company->name ?? 'Logo' }}">
                                    @else
                                        <img src="{{ asset('companies/telico.png') }}" alt="{{ config('app.name') }}">
                                    @endif
                                    <div class="document-title">
                                        <h1>BON DE SORTIE</h1>
                                        <h3>{{ $company?->name ?? 'TELICO' }}</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Badge de sortie -->
                            <div class="sortie-header">
                                <div class="sortie-badge">BON DE SORTIE / BON DE LIVRAISON</div>
                                <div style="display: flex; justify-content: space-between;">
                                    <div><strong>N° Bon de Sortie:</strong> BS-{{ $facture }}</div>
                                    <div><strong>Date de sortie:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                                    <div><strong>Référence Facture:</strong> {{ $facture }}</div>
                                    <div><strong>Date Facture:</strong> {{ \Carbon\Carbon::parse($laFacture->created_at)->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>

                            <!-- Informations Client et Livraison -->
                            <div class="info-block">
                                <h4>INFORMATIONS DE LIVRAISON</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="label">Client</span>
                                        <span class="value">{{ $customer->customerName ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Marque / Enseigne</span>
                                        <span class="value">{{ $customer->mark ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Téléphone</span>
                                        <span class="value">{{ $customer->tel ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Adresse de livraison</span>
                                        <span class="value">{{ $customer->address ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tableau des produits sortis -->
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">N°</th>
                                        <th style="width: 10%">Photo</th>
                                        <th style="width: 60%">Désignation du produit</th>
                                        <th style="width: 25%">Quantité</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @if($item->product?->image)
                                                <img src="{{ asset('products/'.$item->product->image) }}" alt="{{ $item->produit }}">
                                            @else
                                                <img src="{{ asset(\App\Models\Product::IMAGE_PAR_DEFAUT) }}" alt="No Image">
                                            @endif
                                        </td>
                                        <td style="text-align: left;">{{ $item->produit }}<br><small style="color:#6b7280;">Conditionnement : {{ $item->unite_detail }}</small>
                                            {{-- Mention pour le magasinier, à l'écran seulement : le bon imprimé
                                                 ou téléchargé peut être remis au client, qui ne doit pas voir
                                                 chez quel partenaire l'article a été pris. --}}
                                            @if ($item->is_third_party)<small class="mention-interne" data-html2canvas-ignore="true" style="display:block;color:#8A6A24;font-weight:600;">À récupérer chez le partenaire : {{ $item->partner?->name ?? '—' }}{{ $item->partner?->phone ? ' ('.$item->partner->phone.')' : '' }}</small>@endif</td>
                                        <td>{{ $item->quantite_affichee }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                {{-- Total par unité : cartons et pièces ne s'additionnent pas. --}}
                                <tfoot>
                                    <tr>
                                        <td colspan="3" style="text-align: right;"><strong>Total des articles sortis</strong></td>
                                        <td><strong>{{ \App\Models\Product::resumeQuantites($invoice, 'quantity') }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <!-- Informations de livraison -->
                            <div class="delivery-info">
                                <strong>INFORMATIONS COMPLÉMENTAIRES</strong>
                                <div class="delivery-grid">
                                    <div>
                                        <span class="label">Transporteur</span>
                                        <div class="value">____________________________________</div>
                                    </div>
                                    <div>
                                        <span class="label">Véhicule / Immatriculation</span>
                                        <div class="value">____________________________________</div>
                                    </div>
                                    <div>
                                        <span class="label">Chauffeur / Livreur</span>
                                        <div class="value">____________________________________</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conteneur pour les signatures (ne jamais couper) -->
                            <div class="signature-container">
                                <!-- Signatures - Design minimaliste : juste une ligne et du texte -->
                                <div style="display: flex; justify-content: space-between; margin-top: 60px; margin-bottom: 30px; page-break-inside: avoid;">
                                    <!-- Client -->
                                    <div style="width: 220px; text-align: center;">
                                        <div style="border-top: 1px solid black; margin-bottom: 8px;"></div>
                                        <strong style="font-size: 12px;">Signature du Client</strong><br>
                                        <span style="font-size: 10px; color: #666;">(Bon de réception)</span>
                                    </div>
                                    
                                    <!-- Entreprise -->
                                    <div style="width: 220px; text-align: center;">
                                        <div style="border-top: 1px solid black; margin-bottom: 8px;"></div>
                                        <strong style="font-size: 12px;">Pour {{ $company?->name ?? 'TELICO' }}</strong><br>
                                        <span style="font-size: 10px; color: #666;">Responsable magasin / Livreur</span>
                                    </div>
                                </div>

                                <!-- Pied de page -->
                                <div class="page-footer">
                                    <p>Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
                                    <p>{{ $company?->address ?? '' }} - Tél: {{ $company?->phone ?? '' }}</p>
                                    <p>Email: {{ $company?->email ?? '' }}</p>
                                    <p>Ce document atteste de la sortie des marchandises de nos stocks</p>
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
        const printBtn = document.getElementById('printBon');
        const element = document.getElementById('bonDeSortieContent');
        const header = document.querySelector('header');
        const sidebar = document.querySelector('.sidebar');
        const pageHeader = document.querySelector('.page-header');

        if (!downloadBtn || !element || !printBtn) return;

        // Gestionnaire pour le bouton Imprimer
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // Gestionnaire pour le bouton PDF
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (typeof html2pdf === 'undefined') {
                alert('La bibliothèque PDF n\'est pas encore chargée. Veuillez rafraîchir la page.');
                return;
            }

            const filename = 'Bon_Sortie_{{ $facture }}.pdf';

            // Sauvegarder l'état original
            const originalHeaderDisplay = header ? header.style.display : '';
            const originalSidebarDisplay = sidebar ? sidebar.style.display : '';
            const originalPageHeaderDisplay = pageHeader ? pageHeader.style.display : '';

            // Masquer les éléments non nécessaires
            if (header) header.style.display = 'none';
            if (sidebar) sidebar.style.display = 'none';
            if (pageHeader) pageHeader.style.display = 'none';

            // Ajouter une classe pour forcer le fond blanc pendant la capture
            document.body.classList.add('pdf-mode');

            // Options pour html2pdf
            const opt = {
                margin: [10, 10, 10, 10],
                filename: filename,
                image: { 
                    type: 'jpeg', 
                    quality: 0.95 
                },
                html2canvas: { 
                    scale: 1.5,
                    useCORS: true, 
                    logging: false,
                    letterRendering: true,
                    backgroundColor: '#ffffff',  // Fond blanc forcé
                    allowTaint: false,
                    foreignObjectRendering: false
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true,
                    precision: 16
                },
                pagebreak: { 
                    mode: ['css', 'legacy'],
                    before: '.page-break-before',
                    after: '.page-break-after',
                    avoid: ['tr', 'img', '.signature-container', '.footer-section', '.signature-box', '.page-footer']
                },
                enableLinks: false
            };

            // Générer le PDF
            html2pdf().set(opt).from(element).save()
                .then(function() {
                    console.log('PDF généré avec succès');
                    // Restaurer l'état original
                    document.body.classList.remove('pdf-mode');
                    if (header) header.style.display = originalHeaderDisplay;
                    if (sidebar) sidebar.style.display = originalSidebarDisplay;
                    if (pageHeader) pageHeader.style.display = originalPageHeaderDisplay;
                })
                .catch(function(error) {
                    console.error('Erreur lors de la génération du PDF:', error);
                    document.body.classList.remove('pdf-mode');
                    if (header) header.style.display = originalHeaderDisplay;
                    if (sidebar) sidebar.style.display = originalSidebarDisplay;
                    if (pageHeader) pageHeader.style.display = originalPageHeaderDisplay;
                    alert('Une erreur est survenue lors de la génération du PDF. Veuillez réessayer.');
                });
        });
    });
    </script>
</body>
</html>