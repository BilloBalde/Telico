<!DOCTYPE html>
<html lang="fr">
@include('layouts.head')
<head>
    <link rel="stylesheet" href="{{ asset('assets/css/receipt-invoice-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
    <style>
        /* Mentions internes (produit pris chez un partenaire) : à l'écran pour
           le personnel, jamais sur le reçu imprimé ni sur le PDF remis au client. */
        .mention-interne { display: block; color: #8A6A24; font-size: 11px; }
        .pdf-mode .mention-interne { display: none !important; }
        @media print { .mention-interne { display: none !important; } }
    </style>
</head>
<body>
    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header no-print">
                    <div class="page-title">
                        <h4>Reçu de Paiement</h4>
                        <h6>{{ $payment->receipt_number ?? 'N/A' }}</h6>
                    </div>
                    <div class="page-btn">
                        <button id="downloadPdf" class="btn btn-added" title="Télécharger en PDF">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger PDF
                        </button>
                       
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="receipt-invoice-wrapper avoid-page-break has-watermark" id="receiptContent">
                            <div class="document-watermark" aria-hidden="true">
                            </div>
                            <!-- Titre principal avec style bleu -->
                            <div class="invoice-title-wrapper">
                                <div class="invoice-title-row">
                                    <div class="invoice-logo">
                                        {{-- Logo de l'entreprise enregistré en base, jamais un
                                             fichier figé : un changement depuis la fiche
                                             entreprise doit se répercuter partout. --}}
                                        <img src="{{ asset('companies/' . ($company?->logo ?? 'telico.png')) }}" alt="{{ $company?->name ?? 'Logo' }}">
                                    </div>
                                    <h1 class="invoice-title">REÇU DE PAIEMENT</h1>
                                </div>
                                <div class="invoice-subtitle">Original</div>
                            </div>
                            
                            <!-- Section numéro de reçu et date -->
                            <div class="invoice-number-section">
                                <div class="invoice-number-row">
                                    <div>
                                        <span class="invoice-number-label">Reçu N°:</span>
                                        <span class="invoice-number-value">#{{ $payment->receipt_number ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="invoice-number-label">Date:</span>
                                        <span class="invoice-number-value">{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations du client comme dans l'image -->
                            <div class="client-info-box">
                                <div class="client-info-row">
                                    <div>
                                        <strong>Client:</strong> {{ $customer?->customerName ?? 'N/A' }}
                                    </div>
                                    <div>
                                        <strong>Téléphone:</strong> {{ $customer?->phone ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="client-info-row">
                                    <div>
                                        <strong>Adresse:</strong> {{ $customer?->address ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Détails de la facture -->
                            @if($facture)
                            <div class="invoice-details">
                                <div class="details-row">
                                    <span>Facture N°:</span>
                                    <span><strong>#{{ $facture->numero_facture }}</strong></span>
                                </div>
                                <div class="details-row">
                                    <span>Date de la facture:</span>
                                    <span>{{ \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            @endif

                            <!-- Tableau des détails de paiement -->
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>DÉSIGNATION</th>
                                        <th>MODE DE PAIEMENT</th>
                                        <th>MONTANT (GNF)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Paiement de la facture {{ $facture?->numero_facture ?? 'N/A' }}</td>
                                        <td>{{ $payment->paid_by ?? 'N/A' }}</td>
                                        <td>{{ number_format($payment->versement, 0, '.', ' ') }} FG</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="invoice-total">
                                        <td colspan="3"><strong>TOTAL</strong></td>
                                        <td><strong>{{ number_format($payment->versement, 0, '.', ' ') }} FG</strong></td>
                                    </tr>
                                </tfoot>
                            </table>

                            @if($facture)
                                <!-- Résumé de la facture -->
                                <div class="invoice-details">
                                    <div class="details-row">
                                        <span>Montant total de la facture:</span>
                                        <span>{{ number_format($facture->montant_total, 0, '.', ' ') }} FG</span>
                                    </div>
                                    <div class="details-row">
                                        <span>Montant payé:</span>
                                        <span>{{ number_format($payment->versement, 0, '.', ' ') }} FG</span>
                                    </div>
                                    <div class="details-row">
                                        <span>Reste à payer:</span>
                                        <span><strong>{{ number_format($payment->reste ?? $facture->reste, 0, '.', ' ') }} FG</strong></span>
                                    </div>
                                </div>
                            @endif

                            @if($lignes->isNotEmpty())
                                <!-- Articles de la facture, dans l'unité de chaque produit -->
                                <table class="invoice-table">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>ARTICLE DE LA FACTURE</th>
                                            <th>QUANTITÉ</th>
                                            <th>PRIX PAR UNITÉ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lignes as $ligne)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $ligne->produit ?? 'Produit #'.$ligne->product_id }}
                                                @if ($ligne->is_third_party)<small class="mention-interne" data-html2canvas-ignore="true"><i class="fa fa-handshake"></i> Pris chez {{ $ligne->partner?->name ?? 'un partenaire' }} — payé {{ number_format($ligne->purchase_price_from_partner, 0, '.', ' ') }} FG / {{ $ligne->mot_unite }}</small>@endif</td>
                                            <td>{{ $ligne->quantite_affichee }}</td>
                                            <td>{{ number_format($ligne->prix, 0, '.', ' ') }} FG / {{ $ligne->mot_unite }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            <!-- Montant en lettres comme dans l'image -->
                            <div class="amount-in-words">
                                <strong>Montant en lettres:</strong><br>
                                {{ ucfirst(numberToWords($payment->versement)) }} Francs Guinéens
                            </div>

                            <!-- Section des signatures -->
                            <div class="signature-section">
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p><strong>SIGNATURE DU CLIENT</strong></p>
                                </div>
                                
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p><strong>POUR {{ strtoupper($company?->name ?? 'TELICO') }}</strong></p>
                                </div>
                            </div>

                            <!-- Informations de l'entreprise en bas -->
                            <div class="company-info">
                                <h4>{{ $company?->name ?? 'TELICO' }}</h4>
                                <p>{{ $company?->address ?? 'Madina, Ecole Gare Routière Dabola Camion, Conakry' }}</p>
                                <p>Tél: {{ $company?->phone ?? '' }} | Email: {{ $company?->email ?? '' }}</p>
                                <p>Merci pour votre confiance et à bientôt !</p>
                                <p>Reçu généré le {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    <script>
        // Génération PDF conforme aux normes
        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.getElementById('downloadPdf');
            const element = document.getElementById('receiptContent');
            const mainWrapper = document.querySelector('.main-wrapper');
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.querySelector('.page-header');
            
            if (!downloadBtn || !element) {
                console.error('Éléments requis non trouvés');
                return;
            }
            
            // Vérifier que html2pdf est chargé
            function checkHtml2Pdf() {
                if (typeof html2pdf === 'undefined') {
                    console.error('Bibliothèque html2pdf non chargée');
                    setTimeout(checkHtml2Pdf, 100);
                    return false;
                }
                return true;
            }
            
            if (!checkHtml2Pdf()) {
                setTimeout(function() {
                    if (typeof html2pdf === 'undefined') {
                        alert('La bibliothèque PDF n\'est pas chargée. Veuillez rafraîchir la page.');
                        return;
                    }
                }, 2000);
            }
            
            downloadBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (typeof html2pdf === 'undefined') {
                    alert('La bibliothèque PDF n\'est pas encore chargée. Veuillez patienter quelques secondes et réessayer.');
                    return;
                }
                
                const filename = 'Recu_Paiement_{{ $payment->receipt_number ?? 'recu' }}.pdf';
                
                // Créer un conteneur isolé pour le PDF (visible mais hors écran)
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
                
                // Cloner le contenu avec deep clone pour inclure tous les styles
                const clonedElement = element.cloneNode(true);
                clonedElement.id = 'receiptContentClone';
                clonedElement.classList.add('pdf-mode');
                // Reçu remis au client : aucune mention interne dans le PDF.
                clonedElement.querySelectorAll('.mention-interne').forEach(m => m.remove());
                clonedElement.style.position = 'relative';
                clonedElement.style.margin = '0 auto';
                clonedElement.style.padding = '0';
                clonedElement.style.width = '100%';
                clonedElement.style.maxWidth = '190mm';
                clonedElement.style.visibility = 'visible';
                clonedElement.style.opacity = '1';
                clonedElement.style.transform = 'scale(0.92)';
                clonedElement.style.transformOrigin = 'top center';
                
                // S'assurer que toutes les images sont chargées et visibles
                const images = clonedElement.querySelectorAll('img');
                images.forEach(img => {
                    if (img.src) {
                        img.style.display = 'block';
                        img.style.maxWidth = '100%';
                        img.style.visibility = 'visible';
                        img.style.opacity = '1';
                    }
                });
                
                pdfContainer.appendChild(clonedElement);
                document.body.appendChild(pdfContainer);
                
                // Faire défiler vers le haut pour s'assurer que le conteneur est visible
                window.scrollTo(0, 0);
                
                // Masquer les éléments non nécessaires
                if (header) header.style.display = 'none';
                if (sidebar) sidebar.style.display = 'none';
                if (pageHeader) pageHeader.style.display = 'none';
                
                // Attendre que le clone soit rendu
                setTimeout(function() {
                    // Une page A4 (marges de 8 mm) contient un contenu dont la hauteur
                    // vaut au plus 281/194 de sa largeur. Avec la liste des articles,
                    // un reçu peut dépasser : légèrement → on élargit la mise en page
                    // pour qu'il tienne sur une page (un peu plus petit) ; beaucoup →
                    // le PDF prend plusieurs pages. Plus rien n'est coupé.
                    const capacite = 281 / 194;
                    let largeurMm = 190;
                    let unePage = true;
                    for (let essai = 0; essai < 4; essai++) {
                        const ratio = clonedElement.scrollHeight / clonedElement.scrollWidth;
                        if (ratio <= capacite) break;
                        const nouvelle = largeurMm * (ratio / capacite) * 1.02;
                        if (nouvelle > 190 * 1.3) { unePage = false; break; }
                        largeurMm = nouvelle;
                        pdfContainer.style.width = pdfContainer.style.maxWidth = (largeurMm + 20) + 'mm';
                        clonedElement.style.width = clonedElement.style.maxWidth = largeurMm + 'mm';
                    }
                    if (!unePage) {
                        largeurMm = 190;
                        pdfContainer.style.width = pdfContainer.style.maxWidth = '210mm';
                        clonedElement.style.width = '100%';
                        clonedElement.style.maxWidth = '190mm';
                    }

                    // Configuration PDF : une page A4 si possible, sinon plusieurs
                    const opt = {
                        margin: [8, 8, 8, 8], // Marges réduites pour tenir sur une page
                        filename: filename,
                        enableLinks: false,
                        image: { 
                            type: 'jpeg', 
                            quality: 0.98 
                        },
                        html2canvas: { 
                            scale: 1.5,
                            useCORS: true,
                            letterRendering: true,
                            logging: false,
                            backgroundColor: '#ffffff',
                            allowTaint: false,
                            scrollX: 0,
                            scrollY: 0,
                            windowWidth: clonedElement.scrollWidth,
                            windowHeight: clonedElement.scrollHeight
                        },
                        jsPDF: { 
                            unit: 'mm', 
                            format: 'a4',
                            orientation: 'portrait',
                            compress: true
                        },
                        pagebreak: unePage ? {
                            mode: ['avoid-all'],
                            avoid: '.receipt-invoice-wrapper, .invoice-title, .invoice-number-section, .client-info-box, .invoice-table, .amount-in-words, .signature-section, .company-info'
                        } : {
                            // Reçu long : coupure entre les lignes, jamais au milieu d'une ligne.
                            mode: ['css', 'legacy'],
                            avoid: ['tr', '.amount-in-words', '.signature-section', '.company-info']
                        }
                    };
                    
                    // État de chargement
                    const btn = downloadBtn;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Génération...';
                    btn.disabled = true;
                    
                    // Générer le PDF depuis le clone isolé
                    html2pdf().set(opt).from(clonedElement).toPdf().get('pdf').then(function(pdf) {
                        // Pages en trop supprimées seulement quand le reçu tient sur
                        // une page (page blanche finale) : sinon, on garderait un reçu tronqué.
                        if (unePage) {
                            const totalPages = pdf.internal.getNumberOfPages();
                            for (let i = totalPages; i > 1; i--) {
                                pdf.deletePage(i);
                            }
                        }
                        pdf.save(filename);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        // Nettoyer : supprimer le conteneur et restaurer l'affichage
                        document.body.removeChild(pdfContainer);
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.style.background = '';
                    }).catch(function(error) {
                        console.error('Erreur lors de la génération du PDF:', error);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        // Nettoyer même en cas d'erreur
                        if (pdfContainer.parentNode) {
                            document.body.removeChild(pdfContainer);
                        }
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.style.background = '';
                        
                        alert('Erreur lors de la génération du PDF: ' + (error.message || 'Erreur inconnue'));
                    });
                }, 300);
            });
        });
    </script>
</body>
</html>