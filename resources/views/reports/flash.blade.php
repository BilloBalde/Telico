@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Ventes flash</h4>
            <h6>Ventes encaissées en caisse (point de vente), ventes tierces comprises — {{ $label }}</h6>
            <span class="text-muted">Période : {{ $start->format('d/m/Y H:i') }} → {{ $end->format('d/m/Y H:i') }}</span>
        </div>
    </div>

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
                    <label>Vendeur</label>
                    <select name="user_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($vendeurs as $v)
                        <option value="{{ $v->id }}" {{ (string) request('user_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
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
                <div class="col-lg-2 col-sm-4">
                    <label>Type d'emplacement</label>
                    <select name="type" class="form-control">
                        <option value="">Tous</option>
                        @foreach (\App\Models\Store::TYPES as $value => $text)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-4">
                    <label>Produit</label>
                    <select name="product_id" class="form-control">
                        <option value="">Tous</option>
                        @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ (string) request('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->libelle }}</option>
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
        @foreach ([['Ventes flash', $totals['nb']], ['Chiffre d\'affaires', numberDelimiter($totals['ca']).' FG'], ['Quantités vendues', $totals['qty_unites']], ['Marge', numberDelimiter($totals['marge']).' FG']] as [$t, $v])
        <div class="col-lg-3 col-sm-6 d-flex">
            <div class="card flex-fill"><div class="card-body">
                <div class="text-muted" style="font-size:12px;">{{ $t }}</div>
                <div class="h5 mb-0">{{ $v }}</div>
            </div></div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6>Par vendeur</h6>
                <table class="table">
                    <thead><tr><th>Vendeur</th><th class="text-end">Ventes</th><th class="text-end">CA</th><th class="text-end">Qté</th><th class="text-end">Marge</th></tr></thead>
                    <tbody>
                        @forelse ($byVendeur->sortByDesc('ca') as $r)
                        <tr>
                            <td>{{ $userNames[$r->user_id] ?? 'Non renseigné' }}</td>
                            <td class="text-end">{{ $r->nb }}</td>
                            <td class="text-end">{{ numberDelimiter($r->ca) }}</td>
                            <td class="text-end">{{ $qtyVendeur[$r->user_id ?? ''] ?? '0' }}</td>
                            <td class="text-end">{{ numberDelimiter($r->marge) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Aucune vente flash sur la période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6>Par emplacement</h6>
                <table class="table">
                    <thead><tr><th>Emplacement</th><th class="text-end">Ventes</th><th class="text-end">CA</th><th class="text-end">Qté</th><th class="text-end">Marge</th></tr></thead>
                    <tbody>
                        @forelse ($byStore->sortByDesc('ca') as $r)
                        <tr>
                            <td>{{ $storeNames[$r->store_id]?->store_name ?? 'Emplacement #'.$r->store_id }} <small class="text-muted">({{ $storeNames[$r->store_id]?->type_label }})</small></td>
                            <td class="text-end">{{ $r->nb }}</td>
                            <td class="text-end">{{ numberDelimiter($r->ca) }}</td>
                            <td class="text-end">{{ $qtyStore[$r->store_id] ?? '0' }}</td>
                            <td class="text-end">{{ numberDelimiter($r->marge) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <h6>Détail des lignes</h6>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Date et heure</th><th>Facture</th><th>Vendeur</th><th>Emplacement</th><th>Produit</th><th class="text-end">Qté</th><th class="text-end">Prix</th><th class="text-end">Montant</th><th>Client</th></tr></thead>
                <tbody>
                    @forelse ($details as $s)
                    <tr>
                        <td>{{ $s->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $s->numeroFacture }}</td>
                        <td>{{ $s->user?->name ?? '—' }}</td>
                        <td>{{ $s->store?->store_name }}</td>
                        <td>{{ $s->product?->libelle }}@if ($s->is_third_party) <span class="badge bg-light text-dark" style="border:1px solid #C7A150;font-size:11px;">Vente tierce</span>@endif</td>
                        <td class="text-end">{{ $s->quantite_affichee }}</td>
                        <td class="text-end">{{ numberDelimiter($s->prix) }}</td>
                        <td class="text-end">{{ numberDelimiter($s->prixTotal) }}</td>
                        <td>{{ $customers[$s->facture_customer_id] ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">Aucune ligne.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $details->links() }}
        <p class="text-muted mb-0" style="font-size:12px;">
            Seules les factures émises depuis la mise en place de ce suivi portent leur canal : les ventes plus anciennes n'apparaissent pas ici
            (elles restent dans le rapport des ventes).
        </p>
    </div></div>
</div>
@endsection

@section('script_perso')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const period = document.getElementById('period');
        function toggle() {
            document.querySelectorAll('.period-field').forEach(f => f.style.display = f.dataset.period === period.value ? '' : 'none');
        }
        period.addEventListener('change', toggle);
        toggle();
    });
</script>
@endsection
