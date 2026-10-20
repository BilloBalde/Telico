@extends('layouts.template')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
<style>
    /* PDF/Print: éviter qu'une ligne du tableau soit coupée entre 2 pages */
    @media print {
        .report-box table { border-collapse: collapse; width: 100%; }
        .report-box thead { display: table-header-group; }
        .report-box tfoot { display: table-footer-group; }
        .report-box tr,
        .report-box td,
        .report-box th {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>
<div class="content">
    <div class="page-header d-flex align-items-center justify-content-between">
        <div class="page-title">
            <h4>Rapport des Ventes</h4>
            <h6>{{ $label }}</h6>
            <span class="text-muted">Période: {{ $start->toDateString() }} → {{ $end->toDateString() }}</span>
        </div>
        <div class="page-btn">
            <button id="printReport" class="btn btn-added">
                <img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img">
                Imprimer
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('reports.sales') }}" method="GET" class="no-print">
                <div class="row">
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="period">Période</label>
                            <select id="period" name="period" class="form-control">
                                <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Journalier</option>
                                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                <option value="quarterly" {{ $period === 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                                <option value="semestral" {{ $period === 'semestral' ? 'selected' : '' }}>Semestriel</option>
                                <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Annuel</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="daily">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="{{ request('date', $start->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="weekly">
                        <div class="form-group">
                            <label for="week_start">Un jour de la semaine</label>
                            <input type="date" id="week_start" name="week_start" value="{{ request('week_start', $start->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="monthly">
                        <div class="form-group">
                            <label for="month">Mois</label>
                            <input type="month" id="month" name="month" value="{{ request('month', $start->format('Y-m')) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="quarterly">
                        <div class="form-group">
                            <label for="quarter">Trimestre</label>
                            <select id="quarter" name="quarter" class="form-control">
                                @for ($q = 1; $q <= 4; $q++)
                                    <option value="{{ $q }}" {{ (int) request('quarter', ceil(now()->month / 3)) === $q ? 'selected' : '' }}>T{{ $q }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="semestral">
                        <div class="form-group">
                            <label for="semester">Semestre</label>
                            <select id="semester" name="semester" class="form-control">
                                <option value="1" {{ (int) request('semester', now()->month <= 6 ? 1 : 2) === 1 ? 'selected' : '' }}>S1</option>
                                <option value="2" {{ (int) request('semester', now()->month <= 6 ? 1 : 2) === 2 ? 'selected' : '' }}>S2</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="quarterly,semestral,yearly">
                        <div class="form-group">
                            <label for="year">Année</label>
                            <input type="number" id="year" name="year" class="form-control" value="{{ request('year', now()->year) }}">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="store_id">Boutique</label>
                            <select id="store_id" name="store_id" class="form-control">
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" {{ (int) $storeId === $store->id ? 'selected' : '' }}>
                                        {{ $store->full_name }} ({{ $store->type_label }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Générer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card report-box">
        <div class="card-body">
            <div class="invoice-title-wrapper">
                <div class="invoice-title-row">
                    <div class="invoice-logo">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="{{ $company?->name ?? 'Logo' }}">
                    </div>
                    <h1 class="invoice-title">RAPPORT DES VENTES</h1>
                </div>
                <div class="invoice-subtitle">{{ $label }}</div>
            </div>
            <div class="invoice-number-section">
                <div class="invoice-number-row">
                    <div>
                        <span class="invoice-number-label">Période:</span>
                        <span class="invoice-number-value">{{ $start->toDateString() }} → {{ $end->toDateString() }}</span>
                    </div>
                    <div>
                        <span class="invoice-number-label">Boutique:</span>
                        <span class="invoice-number-value">{{ $stores->firstWhere('id', $storeId)?->store_name ?? 'Toutes' }}</span>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-lg-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <div class="text-muted">Total Quantité</div>
                            <div class="h5 mb-0">{{ \App\Models\Product::resumeQuantites($sales, 'quantity') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <div class="text-muted">Total Vente</div>
                            <div class="h4 mb-0">{{ numberDelimiter($totalAmount) }} FG</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <div class="text-muted">Total Profit</div>
                            <div class="h4 mb-0">{{ numberDelimiter($totalProfit) }} FG</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Facture</th>
                            <th>Boutique</th>
                            <th>Produit</th>
                            <th>Qté</th>
                            <th>Prix</th>
                            <th>Total</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td>{{ $sale->created_at }}</td>
                                <td>{{ $sale->numeroFacture }}</td>
                                <td>{{ $sale->store?->store_name }}</td>
                                <td>{{ $sale->product?->libelle }}@if ($sale->is_third_party) <span class="badge bg-light text-dark" style="border:1px solid #C7A150;font-size:11px;">Pris chez {{ $sale->partner?->name ?? 'un partenaire' }}</span>@endif</td>
                                <td>{{ $sale->quantite_affichee }}</td>
                                <td>{{ numberDelimiter($sale->prix) }} FG</td>
                                <td>{{ numberDelimiter($sale->prixTotal) }} FG</td>
                                <td>{{ numberDelimiter($sale->interet) }} FG</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Aucune vente pour cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_perso')
<script>
    function togglePeriodFields() {
        const period = document.getElementById('period').value;
        document.querySelectorAll('.period-field').forEach(field => {
            const periods = field.dataset.period.split(',');
            field.style.display = periods.includes(period) ? 'block' : 'none';
        });
    }

    document.getElementById('period').addEventListener('change', togglePeriodFields);
    window.addEventListener('load', togglePeriodFields);

    document.getElementById('printReport').addEventListener('click', function () {
        const element = document.querySelector('.report-box');
        const filename = `rapport-ventes-${new Date().toISOString().slice(0, 10)}.pdf`;

        if (!element) {
            console.error('Report box not found');
            return;
        }

        // Create isolated container for PDF
        const pdfContainer = document.createElement('div');
        pdfContainer.style.position = 'absolute';
        pdfContainer.style.left = '0';
        pdfContainer.style.top = '0';
        pdfContainer.style.width = '297mm';
        pdfContainer.style.maxWidth = '297mm';
        pdfContainer.style.background = 'white';
        pdfContainer.style.zIndex = '99999';
        pdfContainer.style.padding = '0';
        pdfContainer.style.margin = '0';
        pdfContainer.style.visibility = 'visible';
        pdfContainer.style.opacity = '1';
        pdfContainer.style.display = 'block';

        const clonedElement = element.cloneNode(true);
        clonedElement.style.position = 'relative';
        clonedElement.style.margin = '0 auto';
        clonedElement.style.padding = '0';
        clonedElement.style.width = '100%';
        clonedElement.style.maxWidth = '297mm';
        clonedElement.style.visibility = 'visible';
        clonedElement.style.opacity = '1';

        pdfContainer.appendChild(clonedElement);
        document.body.appendChild(pdfContainer);
        window.scrollTo(0, 0);

        const opt = {
            margin: [10, 10, 10, 10],
            filename: filename,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape', compress: true },
            pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.report-table tr'] }
        };

        html2pdf().set(opt).from(clonedElement).save().then(function() {
            document.body.removeChild(pdfContainer);
        }).catch(function(error) {
            console.error('PDF generation error:', error);
            if (pdfContainer.parentNode) {
                document.body.removeChild(pdfContainer);
            }
            alert('Erreur lors de la génération du PDF.');
        });
    });
</script>
@endsection
