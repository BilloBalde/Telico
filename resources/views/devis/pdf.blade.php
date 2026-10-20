{{-- resources/views/devis/pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<head>
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/receipt-invoice-style.css') }}">
    <style>
        /* Style spécifique au Devis - Exactement comme Bon de Commande */
        #devisContent {
            position: relative;
            overflow: hidden;
            color: #000000 !important;
            font-family: 'Arial', sans-serif;
        }
        
        #devisContent * {
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
        
        .devis-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .devis-header h1 {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #000 !important;
        }
        
        .devis-header h3 {
            font-size: 18px;
            color: #555 !important;
            margin-top: 5px;
        }
        
        .devis-reference {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #C7A150;
            font-size: 16px;
        }
        
        .devis-reference strong {
            font-weight: 600;
            min-width: 120px;
            display: inline-block;
        }
        
        .client-info {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .client-info h4 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }
        
        .client-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .client-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .client-info-item .label {
            font-weight: 600;
            font-size: 13px;
            color: #666 !important;
            margin-bottom: 3px;
        }
        
        .client-info-item .value {
            font-size: 15px;
            font-weight: 500;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        
        .products-table th {
            background: #e9ecef !important;
            color: #000 !important;
            font-weight: 600;
            text-align: center;
            padding: 12px 8px;
            border: 1px solid #ccc;
        }
        
        .products-table td {
            padding: 10px 8px;
            border: 1px solid #ccc;
            text-align: center;
        }
        
        .products-table td:first-child {
            font-weight: 500;
        }
        
        .products-table td:nth-child(2) {
            text-align: left;
        }
        
        .summary-section {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .summary-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        
        .summary-label {
            font-weight: 600;
            min-width: 150px;
            text-align: right;
            margin-right: 20px;
        }
        
        .summary-value {
            min-width: 150px;
            text-align: right;
            font-weight: 500;
        }
        
        .summary-total {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 8px;
            font-size: 13px;
            color: #666 !important;
        }
        
        .conditions {
            margin-top: 30px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
            font-size: 12px;
            color: #666 !important;
        }
        
        .conditions ul {
            margin-top: 5px;
            padding-left: 20px;
        }
        
        .conditions li {
            margin-bottom: 3px;
        }
        
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 60px;
            font-weight: bold;
            transform: rotate(-45deg);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            white-space: nowrap;
            color: #999 !important;
            z-index: 0;
            pointer-events: none;
        }
        
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
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
        }
        
        .status-draft { background: #6c757d; color: white !important; }
        .status-sent { background: #17a2b8; color: white !important; }
        .status-accepted { background: #28a745; color: white !important; }
        .status-rejected { background: #dc3545; color: white !important; }
        .status-expired { background: #ffc107; color: #212529 !important; }
        .status-validated { background: #28a745; color: white !important; }
        
        @media print {
            #devisContent table { 
                border-collapse: collapse; 
                width: 100%; 
            }
            #devisContent thead { 
                display: table-header-group; 
            }
            #devisContent tfoot { 
                display: table-footer-group; 
            }
            #devisContent tr,
            #devisContent td,
            #devisContent th {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .no-print { 
                display: none !important; 
            }
        }
    </style>
</head>
<body>
    <div id="global-loader" class="no-print">
        <div class="whirly-loader"></div>
    </div>

    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header no-print">
                    <div class="page-title">
                        <h4>Devis</h4>
                        <h6>{{ $devis->numero_devis }}</h6>
                    </div>
                    <div class="page-btn">
                        <button id="downloadPdf" class="btn btn-added" title="Télécharger en PDF">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger PDF
                        </button>
                        <a href="{{ route('devis.show', $devis->id) }}" class="btn btn-cancel">
                            <i class="fas fa-arrow-left me-2"></i> Retour
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        @php
                            // $company vient de DeviController
                            $statusLabels = [
                                'draft' => 'BROUILLON',
                                'sent' => 'ENVOYÉ',
                                'accepted' => 'ACCEPTÉ',
                                'rejected' => 'REJETÉ',
                                'expired' => 'EXPIRÉ',
                                'validated' => 'VALIDÉ'
                            ];
                            $statusClass = $devis->status ?? 'draft';

                            // Cette vue sert à la fois d'aperçu navigateur et de source pour le PDF
                            // (dompdf). Un chemin public_path() casserait l'aperçu navigateur, et un
                            // asset() nécessite que dompdf refasse une requête HTTP vers le logo (lent
                            // et pas fiable) — on encode donc l'image en base64 pour que ça marche
                            // pareil dans les deux contextes, sans dépendance réseau.
                            $logoPath = null;
                            if ($company && $company->logo && file_exists(public_path('companies/'.$company->logo))) {
                                $logoPath = public_path('companies/'.$company->logo);
                            }
                            $logoDataUri = $logoPath
                                ? 'data:' . (mime_content_type($logoPath) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($logoPath))
                                : null;
                        @endphp

                        <div id="devisContent">
                            <!-- Filigrane -->
                            <div class="watermark">DEVIS</div>

                            <!-- En-tête avec logo -->
                            <div class="devis-header">
                                <div class="document-logo">
                                    @if($logoDataUri)
                                        <img src="{{ $logoDataUri }}" alt="{{ $company->name ?? 'Logo' }}">
                                    @endif
                                    <div class="document-title">
                                        <h1>DEVIS</h1>
                                        <h3>{{ $company?->name ?? 'TELICO' }}</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Référence -->
                            <div class="devis-reference">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div><strong>N° Devis:</strong> {{ $devis->numero_devis }}</div>
                                    <div>
                                        <span class="status-badge status-{{ $statusClass }}">{{ $statusLabels[$statusClass] ?? $statusClass }}</span>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-top: 8px;">
                                    <div><strong>Date:</strong> {{ $devis->created_at->format('d/m/Y H:i') }}</div>
                                    <div><strong>Valable jusqu'au:</strong> {{ $devis->valid_until ? $devis->valid_until->format('d/m/Y') : 'Non spécifié' }}</div>
                                </div>
                            </div>

                            <!-- Informations Client -->
                            <div class="client-info">
                                <h4>INFORMATIONS CLIENT</h4>
                                <div class="client-info-grid">
                                    <div class="client-info-item">
                                        <span class="label">Nom / Raison Sociale</span>
                                        <span class="value">{{ $devis->customer->customerName ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-item">
                                        <span class="label">Marque / Enseigne</span>
                                        <span class="value">{{ $devis->customer->mark ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-item">
                                        <span class="label">Téléphone</span>
                                        <span class="value">{{ $devis->customer->tel ?? 'N/A' }}</span>
                                    </div>
                                    <div class="client-info-item">
                                        <span class="label">Adresse</span>
                                        <span class="value">{{ $devis->customer->address ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tableau des produits -->
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">N°</th>
                                        <th style="width: 40%">Désignation du produit</th>
                                        <th style="width: 10%">Qté</th>
                                        <th style="width: 20%">Prix Unitaire</th>
                                        <th style="width: 25%">Montant Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($devis->lines as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td style="text-align: left;">{{ $item->product->libelle ?? 'Produit supprimé' }}</td>
                                        <td>{{ $item->quantite_affichee }}</td>
                                        <td>{{ number_format($item->unit_price, 0, ',', ' ') }} FG</td>
                                        <td>{{ number_format($item->total_price, 0, ',', ' ') }} FG</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Résumé des montants -->
                            <div class="summary-section">
                                <div class="summary-row summary-total">
                                    <span class="summary-label">TOTAL DEVIS:</span>
                                    <span class="summary-value">{{ number_format($devis->total_amount, 0, ',', ' ') }} FG</span>
                                </div>
                            </div>

                            <!-- Montant en lettres -->
                            <div style="margin: 20px 0; font-style: italic;">
                                <strong>Arrêté le présent devis à la somme de :</strong><br>
                                <em>{{ ucfirst(numberToWords($devis->total_amount)) }} Francs Guinéens</em>
                            </div>

                            <!-- Notes -->
                            @if($devis->notes)
                            <div style="margin: 20px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #C7A150;">
                                <strong>Notes:</strong><br>
                                {{ $devis->notes }}
                            </div>
                            @endif

                            <!-- Conditions -->
                            <div class="conditions">
                                <strong>Conditions générales :</strong>
                                <ul>
                                    <li>Ce devis est valable jusqu'à la date indiquée</li>
                                    <li>Délai de livraison : à convenir avec le service commercial</li>
                                    <li>Règlement : à la livraison ou selon échéancier convenu</li>
                                    <li>Réserve de propriété : les marchandises restent la propriété du vendeur jusqu'au paiement intégral</li>
                                </ul>
                            </div>

                            <!-- Signatures -->
                            <div class="footer-section">
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <strong>Signature du Client</strong><br>
                                    <small>(Bon pour accord)</small>
                                </div>
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <strong>Pour {{ $company?->name ?? 'TELICO' }}</strong><br>
                                    <small>{{ $devis->createdBy?->name ?? auth()->user()->name }}</small>
                                </div>
                            </div>

                            <!-- Pied de page -->
                            <div style="margin-top: 30px; text-align: center; font-size: 11px; color: #999 !important; border-top: 1px solid #eee; padding-top: 10px;">
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
            const element = document.getElementById('devisContent');
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.querySelector('.page-header');

            if (!downloadBtn || !element) return;

            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (typeof html2pdf === 'undefined') {
                    alert('La bibliothèque PDF n\'est pas encore chargée. Veuillez rafraîchir la page.');
                    return;
                }

                const filename = 'Devis_{{ $devis->numero_devis }}.pdf';

                // Container isolé pour la capture
                const pdfContainer = document.createElement('div');
                pdfContainer.style.position = 'absolute';
                pdfContainer.style.left = '0';
                pdfContainer.style.top = '0';
                pdfContainer.style.width = '210mm';
                pdfContainer.style.maxWidth = '210mm';
                pdfContainer.style.background = 'white';
                pdfContainer.style.zIndex = '99999';
                pdfContainer.style.padding = '0';
                pdfContainer.style.margin = '0';
                pdfContainer.style.visibility = 'visible';
                pdfContainer.style.opacity = '1';
                pdfContainer.style.display = 'flex';
                pdfContainer.style.justifyContent = 'center';

                const clonedElement = element.cloneNode(true);
                clonedElement.style.position = 'relative';
                clonedElement.style.margin = '0 auto';
                clonedElement.style.padding = '0';
                clonedElement.style.width = '100%';
                clonedElement.style.maxWidth = '190mm';
                clonedElement.style.visibility = 'visible';
                clonedElement.style.opacity = '1';

                pdfContainer.appendChild(clonedElement);
                document.body.appendChild(pdfContainer);
                window.scrollTo(0, 0);

                // Masquer les éléments non nécessaires
                if (header) header.style.display = 'none';
                if (sidebar) sidebar.style.display = 'none';
                if (pageHeader) pageHeader.style.display = 'none';

                const btn = downloadBtn;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Génération...';
                btn.disabled = true;

                const opt = {
                    margin: [10, 10, 10, 10],
                    filename: filename,
                    enableLinks: false,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 1.5, useCORS: true, backgroundColor: '#ffffff' },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
                };

                html2pdf().set(opt).from(clonedElement).save().then(function() {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (pdfContainer.parentNode) document.body.removeChild(pdfContainer);
                    if (header) header.style.display = '';
                    if (sidebar) sidebar.style.display = '';
                    if (pageHeader) pageHeader.style.display = '';
                }).catch(function() {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (pdfContainer.parentNode) document.body.removeChild(pdfContainer);
                    if (header) header.style.display = '';
                    if (sidebar) sidebar.style.display = '';
                    if (pageHeader) pageHeader.style.display = '';
                });
            });
        });
    </script>
</body>
</html>