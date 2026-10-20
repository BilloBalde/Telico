@extends('layouts.template')
@section('content')
@php $fg = fn ($n) => numberDelimiter($n) . ' FG'; @endphp
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Ventes tierces</h4>
            <h6>Produits pris chez un partenaire et revendus — {{ $label }}</h6>
            <span class="text-muted">Période : {{ $start->format('d/m/Y') }} → {{ $end->format('d/m/Y') }}</span>
        </div>
        <div class="page-btn d-flex flex-wrap gap-2">
            {{-- Exports : mêmes filtres que l'écran. --}}
            <a href="{{ route('reports.tiers.pdf', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="fa fa-file-pdf me-1"></i> PDF
            </a>
            <a href="{{ route('reports.tiers.excel', request()->query()) }}" class="btn btn-outline-secondary">
                <i class="fa fa-file-excel me-1"></i> Excel
            </a>
            @if ($peutRegler && $tousPartenaires->isNotEmpty())
            <button type="button" class="btn btn-added" data-bs-toggle="modal" data-bs-target="#reglementModal">
                <i class="fa fa-hand-holding-usd me-1"></i> Enregistrer un règlement
            </button>
            @endif
        </div>
    </div>

    @include('layouts.flash')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-lg-2 col-sm-4">
                    <label>Période</label>
                    <select name="period" id="period" class="form-control">
                        @foreach (['daily' => 'Jour', 'weekly' => 'Semaine', 'monthly' => 'Mois', 'yearly' => 'Année'] as $value => $text)
                        <option value="{{ $value }}" {{ $period === $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-4 period-field" data-period="daily">
                    <label>Date</label>
                    <input type="date" name="date" value="{{ request('date', $start->toDateString()) }}" class="form-control">
                </div>
                <div class="col-lg-2 col-sm-4 period-field" data-period="weekly">
                    <label>Un jour de la semaine</label>
                    <input type="date" name="week_start" value="{{ request('week_start', $start->toDateString()) }}" class="form-control">
                </div>
                <div class="col-lg-2 col-sm-4 period-field" data-period="monthly">
                    <label>Mois</label>
                    <input type="month" name="month" value="{{ request('month', $start->format('Y-m')) }}" class="form-control">
                </div>
                <div class="col-lg-2 col-sm-4 period-field" data-period="yearly">
                    <label>Année</label>
                    <input type="number" name="year" value="{{ request('year', $start->year) }}" class="form-control">
                </div>
                <div class="col-lg-2 col-sm-4">
                    <label>Partenaire</label>
                    <select name="partner_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($tousPartenaires as $p)
                        <option value="{{ $p->id }}" {{ (string) request('partner_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-4">
                    <label>Emplacement</label>
                    <select name="store_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($stores as $s)
                        <option value="{{ $s->id }}" {{ (string) request('store_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->store_name }} ({{ $s->type_label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-4 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Afficher</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach (array_filter([
            ['Ventes tierces (période)', $fg($totals['ca'])],
            ['Dû aux partenaires (période)', $fg($totals['du'])],
            ['Marge (période)', $fg($totals['marge'])],
            $peutRegler ? ['Reste à payer (tout)', $fg($totals['solde'])] : null,
        ]) as [$titre, $valeur])
        <div class="col-lg-3 col-sm-6 col-12 d-flex">
            <div class="card flex-fill"><div class="card-body">
                <div class="text-muted" style="font-size:12px;">{{ $titre }}</div>
                <div class="h5 mb-0">{{ $valeur }}</div>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="card"><div class="card-body">
        <h6>Par partenaire</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Partenaire</th>
                        <th class="text-end">Lignes</th>
                        <th class="text-end">Quantités</th>
                        <th class="text-end">Ventes</th>
                        <th class="text-end">Dû (période)</th>
                        <th class="text-end">Marge</th>
                        @if ($peutRegler)
                        <th class="text-end">Dû (tout)</th>
                        <th class="text-end">Réglé</th>
                        <th class="text-end">Reste à payer</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partenaires as $r)
                    <tr>
                        <td>
                            <strong>{{ $r->partner->name }}</strong>
                            @if ($r->partner->phone)<small class="d-block text-muted">{{ $r->partner->phone }}</small>@endif
                        </td>
                        <td class="text-end">{{ $r->periode->nb ?? 0 }}</td>
                        <td class="text-end">{{ $r->qty }}</td>
                        <td class="text-end">{{ $fg($r->periode->ca ?? 0) }}</td>
                        <td class="text-end">{{ $fg($r->periode->du ?? 0) }}</td>
                        <td class="text-end">{{ $fg($r->periode->marge ?? 0) }}</td>
                        @if ($peutRegler)
                        <td class="text-end">{{ $fg($r->du) }}</td>
                        <td class="text-end">{{ $fg($r->regle) }}</td>
                        <td class="text-end">
                            <strong class="{{ $r->solde > 0.01 ? 'text-danger' : 'text-success' }}">{{ $fg(max(0, $r->solde)) }}</strong>
                            @if ($r->solde > 0.01)
                            <button type="button" class="btn btn-sm btn-link p-0 d-block ms-auto" data-bs-toggle="modal" data-bs-target="#reglementModal"
                                    data-partenaire="{{ $r->partner->id }}" data-solde="{{ round($r->solde) }}">Régler</button>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="{{ $peutRegler ? 9 : 6 }}" class="text-center text-muted">Aucune vente tierce sur la période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>

    <div class="card"><div class="card-body">
        <h6>Produits pris chez les partenaires</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th><th>Facture</th><th>Vendeur</th><th>Emplacement</th><th>Produit</th><th>Partenaire</th>
                        <th class="text-end">Qté</th><th class="text-end">Payé au partenaire</th><th class="text-end">Prix de vente</th>
                        <th class="text-end">Dû</th><th class="text-end">Vente</th><th class="text-end">Marge</th><th>Client</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($details as $s)
                    <tr>
                        <td style="white-space:nowrap;">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('voirSales', $s->numeroFacture) }}">{{ $s->numeroFacture }}</a></td>
                        <td>{{ $s->user?->name ?? '—' }}</td>
                        <td>{{ $s->store?->store_name }}</td>
                        <td>{{ $s->product?->libelle ?? 'Produit #'.$s->product_id }}</td>
                        <td>{{ $s->partner?->name ?? '—' }}</td>
                        <td class="text-end">{{ $s->quantite_affichee }}</td>
                        <td class="text-end">{{ numberDelimiter($s->purchase_price_from_partner) }} <small class="text-muted">/ {{ $s->mot_unite }}</small></td>
                        <td class="text-end">{{ numberDelimiter($s->prix) }} <small class="text-muted">/ {{ $s->mot_unite }}</small></td>
                        <td class="text-end">{{ numberDelimiter($s->du_partenaire) }}</td>
                        <td class="text-end">{{ numberDelimiter($s->prixTotal) }}</td>
                        <td class="text-end">{{ numberDelimiter($s->interet) }}</td>
                        <td>{{ $customers[$s->facture_customer_id] ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="13" class="text-center text-muted">Aucune ligne.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $details->links() }}
    </div></div>

    @if ($peutRegler)
    <div class="card"><div class="card-body">
        <h6>Règlements aux partenaires (période)</h6>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date</th><th>Partenaire</th><th class="text-end">Montant</th><th>Mode</th><th>Note</th><th>Enregistré par</th></tr></thead>
                <tbody>
                    @forelse ($reglements as $r)
                    <tr>
                        <td>{{ $r->paid_at->format('d/m/Y') }}</td>
                        <td>{{ $r->partner?->name }}</td>
                        <td class="text-end">{{ $fg($r->amount) }}</td>
                        <td>{{ $r->mode_label }}</td>
                        <td>{{ $r->note }}</td>
                        <td>{{ $r->user?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Aucun règlement sur la période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>

    <div class="modal fade" id="reglementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('reports.tiers.settle') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Règlement à un partenaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Partenaire</label>
                        <select name="partner_id" id="reglementPartenaire" class="form-control" required>
                            @foreach ($tousPartenaires as $p)
                            <option value="{{ $p->id }}" {{ (string) old('partner_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 form-group">
                            <label>Montant (FG)</label>
                            <input type="number" min="1" step="any" name="amount" id="reglementMontant" value="{{ old('amount') }}" class="form-control" required>
                        </div>
                        <div class="col-6 form-group">
                            <label>Date</label>
                            <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mode</label>
                        <select name="mode" class="form-control">
                            @foreach (\App\Models\PartnerSettlement::MODES as $code => $libelle)
                            <option value="{{ $code }}" {{ old('mode') === $code ? 'selected' : '' }}>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Note</label>
                        <input type="text" name="note" value="{{ old('note') }}" class="form-control" maxlength="500" placeholder="Ex. paiement des 3 robinets de mardi">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@section('script_perso')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const period = document.getElementById('period');
        function toggle() {
            document.querySelectorAll('.period-field').forEach(function (el) {
                el.style.display = el.dataset.period === period.value ? '' : 'none';
            });
        }
        period.addEventListener('change', toggle);
        toggle();

        // « Régler » sur une ligne : partenaire et reste à payer pré-remplis.
        document.getElementById('reglementModal')?.addEventListener('show.bs.modal', function (e) {
            const bouton = e.relatedTarget;
            if (bouton && bouton.dataset.partenaire) {
                document.getElementById('reglementPartenaire').value = bouton.dataset.partenaire;
                document.getElementById('reglementMontant').value = bouton.dataset.solde;
            }
        });
        @if ($errors->has('amount') || $errors->has('partner_id') || $errors->has('paid_at'))
            new bootstrap.Modal(document.getElementById('reglementModal')).show();
        @endif
    });
</script>
@endsection
