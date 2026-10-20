<!DOCTYPE html>
<html lang="fr">
@include('layouts.head')
<head>
    <link rel="stylesheet" href="{{ asset('assets/css/receipt-invoice-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
</head>
<body>
    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header no-print">
                    <div class="page-title">
                        <h4>Reçu Transaction Client</h4>
                        <h6>{{ $transaction->receipt_number ?? 'N/A' }}</h6>
                    </div>
                    <div class="page-btn">
                        <button id="downloadPdf" class="btn btn-added" title="Télécharger en PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button onclick="window.print()" class="btn btn-primary ms-2" title="Imprimer">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="receipt-invoice-wrapper avoid-page-break" id="receiptContent">
                            <div class="invoice-title-wrapper">
                                <div class="invoice-title-row">
                                    <div class="invoice-logo">
                                        <img src="{{ asset('companies/' . ($company?->logo ?? 'telico.png')) }}" alt="{{ $company?->name ?? 'Logo' }}">
                                    </div>
                                    <h1 class="invoice-title">REÇU TRANSACTION</h1>
                                </div>
                                <div class="invoice-subtitle">Original</div>
                            </div>

                            <div class="invoice-number-section">
                                <div class="invoice-number-row">
                                    <div>
                                        <span class="invoice-number-label">Reçu N°:</span>
                                        <span class="invoice-number-value">#{{ $transaction->receipt_number ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="invoice-number-label">Date:</span>
                                        <span class="invoice-number-value">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="client-info-box">
                                <div class="client-info-row">
                                    <div>
                                        <strong>Client:</strong> {{ $customer?->customerName ?? 'N/A' }}
                                    </div>
                                    <div>
                                        <strong>Téléphone:</strong> {{ $customer?->tel ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="client-info-row">
                                    <div>
                                        <strong>Adresse:</strong> {{ $customer?->address ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <div class="invoice-details">
                                <div class="details-row">
                                    <span>Montant reçu:</span>
                                    <span>{{ numberDelimiter($transaction->versement) }} FG</span>
                                </div>
                                {{-- La colonne `balance` est le solde AVANT le versement ; le reçu
                                     l'affichait comme « balance restante ». --}}
                                <div class="details-row">
                                    <span>Solde avant versement:</span>
                                    <span>{{ numberDelimiter($transaction->solde_avant) }} FG</span>
                                </div>
                                <div class="details-row">
                                    <span><strong>Reste à payer après ce versement:</strong></span>
                                    <span><strong>{{ numberDelimiter($transaction->solde_apres) }} FG</strong></span>
                                </div>
                                <div class="details-row">
                                    <span>Caissier:</span>
                                    <span>{{ $user?->name ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>Mode de paiement</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $transaction->paid_by ?? 'N/A' }}</td>
                                        <td>{{ $transaction->note ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            @if ($transaction->payments->isNotEmpty())
                                <!-- Factures réglées par ce versement (de la plus ancienne à la plus récente) -->
                                <table class="invoice-table">
                                    <thead>
                                        <tr>
                                            <th>FACTURE</th>
                                            <th>ARTICLES</th>
                                            <th>MONTANT AFFECTÉ</th>
                                            <th>RESTE SUR LA FACTURE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transaction->payments->sortBy('id') as $paiement)
                                        <tr>
                                            <td>{{ $paiement->facture?->numero_facture ?? '—' }}</td>
                                            <td>{{ $paiement->facture?->quantite_resumee ?? '—' }}</td>
                                            <td>{{ numberDelimiter($paiement->versement) }} FG</td>
                                            <td>{{ (float) $paiement->reste > 0 ? numberDelimiter($paiement->reste) . ' FG' : 'Soldée' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            <div class="amount-in-words">
                                <strong>Montant en lettres:</strong><br>
                                {{ ucfirst(numberToWords($transaction->versement)) }} Francs Guinéens
                            </div>

                            <div class="signature-section">
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p><strong>CACHET & SIGNATURE DU CLIENT</strong></p>
                                    <p>Nom et cachet du client</p>
                                </div>

                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p><strong>POUR {{ strtoupper($company?->name ?? 'TELICO') }}</strong></p>
                                    <p>Le Gérant / Signature autorisée</p>
                                    <p>Nom et signature du responsable</p>
                                </div>
                            </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.getElementById('downloadPdf');
            const element = document.getElementById('receiptContent');
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.querySelector('.page-header');

            if (!downloadBtn || !element) {
                console.error('Éléments requis non trouvés');
                return;
            }

            downloadBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (typeof html2pdf === 'undefined') {
                    alert('La bibliothèque PDF n\'est pas encore chargée. Veuillez patienter quelques secondes et réessayer.');
                    return;
                }

                const filename = 'Recu_Transaction_{{ $transaction->receipt_number ?? 'recu' }}.pdf';

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
                clonedElement.id = 'receiptContentClone';
                clonedElement.classList.add('pdf-mode');
                clonedElement.style.position = 'relative';
                clonedElement.style.margin = '0 auto';
                clonedElement.style.padding = '0';
                clonedElement.style.width = '100%';
                clonedElement.style.maxWidth = '190mm';
                clonedElement.style.visibility = 'visible';
                clonedElement.style.opacity = '1';
                clonedElement.style.transform = 'scale(0.92)';
                clonedElement.style.transformOrigin = 'top center';

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

                window.scrollTo(0, 0);

                if (header) header.style.display = 'none';
                if (sidebar) sidebar.style.display = 'none';
                if (pageHeader) pageHeader.style.display = 'none';

                setTimeout(function() {
                    // Une page A4 (marges de 8 mm) contient un contenu dont la hauteur
                    // vaut au plus 281/194 de sa largeur. Avec la liste des factures
                    // réglées, le reçu peut dépasser : légèrement → mise en page élargie
                    // pour tenir sur une page ; beaucoup → plusieurs pages. Rien n'est coupé.
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
                        pdfContainer.style.width = pdfContainer.style.maxWidth = '210mm';
                        clonedElement.style.width = '100%';
                        clonedElement.style.maxWidth = '190mm';
                    }

                    const opt = {
                        margin: [8, 8, 8, 8],
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

                    const btn = downloadBtn;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Génération...';
                    btn.disabled = true;

                    html2pdf().set(opt).from(clonedElement).toPdf().get('pdf').then(function(pdf) {
                        // Pages en trop supprimées seulement quand le reçu tient sur une
                        // page (page blanche finale) : sinon le reçu serait tronqué.
                        if (unePage) {
                            const totalPages = pdf.internal.getNumberOfPages();
                            for (let i = totalPages; i > 1; i--) {
                                pdf.deletePage(i);
                            }
                        }
                        pdf.save(filename);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        document.body.removeChild(pdfContainer);
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.style.background = '';
                    }).catch(function(error) {
                        console.error('Erreur lors de la génération du PDF:', error);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
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
