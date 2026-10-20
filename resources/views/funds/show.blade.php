@extends('layouts.template')
@section('content')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
@endphp
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Relevé — {{ $fund->name }}</h4>
            <h6>{{ $fund->associate_name }}{{ $fund->phone ? ' · '.$fund->phone : '' }} · partage du bénéfice : {{ $pct($fund->owner_share) }} % patron / {{ $pct($fund->associate_share) }} % associé</h6>
        </div>
        @if ($canManage)
        <div class="page-btn d-flex gap-2">
            <a href="{{ route('associates.edit', $fund) }}" class="btn btn-added">Modifier</a>
            <a href="{{ route('associates.index') }}" class="btn btn-cancel">Tous les associés</a>
        </div>
        @endif
    </div>
    @include('layouts.flash')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- Synthèse --}}
    <div class="row">
        <div class="col-lg-4 col-md-6 d-flex">
            <div class="card flex-fill"><div class="card-body">
                <h6 class="mb-3">Capital de l'associé</h6>
                <table class="table table-sm mb-0">
                    <tr><td>Apports</td><td class="text-end">{{ $fmt($s['apports']) }}</td></tr>
                    <tr><td>Capital remboursé</td><td class="text-end">− {{ $fmt($s['capital_rembourse']) }}</td></tr>
                    <tr class="fw-bold"><td>Capital restant dû</td><td class="text-end">{{ $fmt($s['capital_restant']) }}</td></tr>
                    <tr><td class="text-muted">Disponible pour de nouveaux achats</td><td class="text-end text-muted">{{ $fmt($s['capital_disponible']) }}</td></tr>
                </table>
            </div></div>
        </div>
        <div class="col-lg-4 col-md-6 d-flex">
            <div class="card flex-fill"><div class="card-body">
                <h6 class="mb-3">Marchandises et ventes</h6>
                <table class="table table-sm mb-0">
                    <tr><td>Achats financés</td><td class="text-end">{{ $fmt($s['achats']) }}</td></tr>
                    <tr><td>Stock restant</td><td class="text-end">{{ \App\Models\Product::resumeQuantites($stock, 'quantity') }} · {{ $fmt($s['stock_valeur']) }}</td></tr>
                    <tr><td>Ventes facturées</td><td class="text-end">{{ $fmt($s['ventes_facturees']) }}</td></tr>
                    <tr><td>dont encaissées</td><td class="text-end">{{ $fmt($s['ventes_encaissees']) }}</td></tr>
                    <tr><td class="text-muted">Reste à encaisser (clients)</td><td class="text-end text-muted">{{ $fmt($s['reste_a_encaisser']) }}</td></tr>
                </table>
            </div></div>
        </div>
        <div class="col-lg-4 col-md-12 d-flex">
            <div class="card flex-fill"><div class="card-body">
                <h6 class="mb-3">Résultat (à l'encaissement)</h6>
                <table class="table table-sm mb-0">
                    <tr><td>Bénéfice sur ventes encaissées</td><td class="text-end">{{ $fmt($s['benefice_encaisse']) }}</td></tr>
                    <tr><td>Pertes / écarts de stock</td><td class="text-end {{ $s['ecarts_stock'] < 0 ? 'text-danger' : '' }}">{{ $fmt($s['ecarts_stock']) }}</td></tr>
                    <tr class="fw-bold"><td>Résultat à partager</td><td class="text-end {{ $s['resultat'] < 0 ? 'text-danger' : 'text-success' }}">{{ $fmt($s['resultat']) }}</td></tr>
                    <tr><td>Part associé ({{ $pct($fund->associate_share) }} %)</td><td class="text-end">{{ $fmt($s['part_associe']) }}</td></tr>
                    <tr><td>Part patron ({{ $pct($fund->owner_share) }} %)</td><td class="text-end">{{ $fmt($s['part_patron']) }}</td></tr>
                    <tr><td class="text-muted">Bénéfice en attente (ventes non payées)</td><td class="text-end text-muted">{{ $fmt($s['benefice_en_attente']) }}</td></tr>
                </table>
            </div></div>
        </div>
    </div>

    <div class="row">
        @foreach ([
            ['Compte de l\'associé', $s['tresorerie'], 'Doit se trouver sur son compte à part'],
            ['Capital à rembourser', $s['capital_restant'], 'Remboursé : '.$fmt($s['capital_rembourse'])],
            ['Bénéfice à verser à l\'associé', $s['benefice_a_verser'], 'Déjà versé : '.$fmt($s['benefice_verse'])],
            ['Part du patron restante', $s['part_patron_restante'], 'Déjà prélevée : '.$fmt($s['part_patron_prelevee'])],
        ] as [$titre, $valeur, $detail])
        <div class="col-lg-3 col-sm-6 d-flex">
            <div class="card flex-fill"><div class="card-body">
                <div class="text-muted" style="font-size:12px;">{{ $titre }}</div>
                <div class="h5 mb-0 {{ $valeur < 0 ? 'text-danger' : '' }}">{{ $fmt($valeur) }} FG</div>
                <small class="text-muted">{{ $detail }}</small>
            </div></div>
        </div>
        @endforeach
    </div>

    {{-- Mouvements du compte (patron uniquement) --}}
    @if ($canManage)
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Enregistrer un mouvement sur le compte de l'associé</h6>
            <div class="row g-3">
                <div class="col-lg-4">
                    <form method="POST" action="{{ route('associates.transactions.store', $fund) }}" class="border rounded p-3 h-100">
                        @csrf <input type="hidden" name="kind" value="apport">
                        <strong>Apport de capital</strong>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control mt-2" placeholder="Montant (FG)" required>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-control mt-2" required>
                        <input type="text" name="notes" class="form-control mt-2" placeholder="Note (facultatif)">
                        <button class="btn btn-submit btn-sm mt-2">Enregistrer l'apport</button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <form method="POST" action="{{ route('associates.transactions.store', $fund) }}" class="border rounded p-3 h-100">
                        @csrf <input type="hidden" name="kind" value="versement">
                        <strong>Versement à l'associé</strong>
                        <small class="d-block text-muted">Répartissez le montant versé : remboursement de capital et / ou bénéfice.</small>
                        <label class="mt-2 mb-0" style="font-size:12px;">Part remboursement de capital (max {{ $fmt($s['capital_restant']) }})</label>
                        <input type="number" step="0.01" min="0" name="capital" class="form-control" placeholder="0">
                        <label class="mt-2 mb-0" style="font-size:12px;">Part bénéfice (max {{ $fmt(max(0, $s['benefice_a_verser'])) }})</label>
                        <input type="number" step="0.01" min="0" name="benefice" class="form-control" placeholder="0">
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-control mt-2" required>
                        <input type="text" name="notes" class="form-control mt-2" placeholder="Note (facultatif)">
                        <button class="btn btn-submit btn-sm mt-2">Enregistrer le versement</button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <form method="POST" action="{{ route('associates.transactions.store', $fund) }}" class="border rounded p-3 h-100">
                        @csrf <input type="hidden" name="kind" value="prelevement">
                        <strong>Prélèvement de la part du patron</strong>
                        <small class="d-block text-muted">Disponible : {{ $fmt(max(0, $s['part_patron_restante'])) }} FG</small>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control mt-2" placeholder="Montant (FG)" required>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-control mt-2" required>
                        <input type="text" name="notes" class="form-control mt-2" placeholder="Note (facultatif)">
                        <button class="btn btn-submit btn-sm mt-2">Enregistrer le prélèvement</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6 class="mb-3">Mouvements du compte</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Type</th><th class="text-end">Montant</th><th>Par</th></tr></thead>
                        <tbody>
                            @forelse ($transactions as $t)
                            <tr>
                                <td>{{ $t->date->format('d/m/Y') }}</td>
                                <td>{{ $t->type_label }}@if ($t->notes)<small class="d-block text-muted">{{ $t->notes }}</small>@endif</td>
                                <td class="text-end {{ $t->type === 'apport' ? 'text-success' : '' }}">{{ $t->type === 'apport' ? '+' : '−' }} {{ $fmt($t->amount) }}</td>
                                <td>{{ $t->user?->name ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Aucun mouvement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h6 class="mb-3">Achats financés</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Référence</th><th>Destination</th><th class="text-end">Montant</th></tr></thead>
                        <tbody>
                            @forelse ($purchases as $p)
                            <tr class="{{ $p->status === 'cancelled' ? 'text-muted text-decoration-line-through' : '' }}">
                                <td>{{ optional($p->date_emis)->format('d/m/Y') }}</td>
                                <td>@if ($canManage)<a href="{{ route('purchase-orders.show', $p->id) }}">{{ $p->reference }}</a>@else{{ $p->reference }}@endif</td>
                                <td>{{ $p->store?->store_name }}</td>
                                <td class="text-end">{{ $fmt($p->total_landed_cost_gnf) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Aucun achat financé.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card"><div class="card-body">
                <h6 class="mb-3">Marchandises de l'associé en stock</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Produit</th><th>Emplacement</th><th class="text-end">Qté</th><th class="text-end">Valeur</th></tr></thead>
                        <tbody>
                            @forelse ($stock as $r)
                            <tr>
                                <td>{{ $r->libelle }}</td>
                                <td>{{ $r->store_name }}</td>
                                <td class="text-end">{{ \App\Models\Product::quantiteEnUnite($r->quantity, $r->unite) }}</td>
                                <td class="text-end">{{ $fmt($r->quantity * ($costs[$r->product_id] ?? 0)) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Aucun stock.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-body">
                <h6 class="mb-3">Ventes des marchandises de l'associé</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Facture</th><th>Produit</th><th class="text-end">Qté</th><th class="text-end">Vente</th><th class="text-end">Coût</th><th class="text-end">Encaissé</th></tr></thead>
                        <tbody>
                            @forelse (collect($s['lignes'])->sortByDesc('id') as $l)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($l->created_at)->format('d/m/Y') }}</td>
                                <td>{{ $l->numeroFacture }}</td>
                                <td>{{ $productNames[$l->product_id] ?? '#'.$l->product_id }}</td>
                                <td class="text-end">{{ \App\Models\Product::quantiteEnUnite($l->quantity, $l->unite ?? null) }}</td>
                                <td class="text-end">{{ $fmt($l->prixTotal) }}</td>
                                <td class="text-end">{{ $fmt($l->cout) }}</td>
                                <td class="text-end">{{ round($l->ratio * 100) }} %</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted">Aucune vente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    </div>
    <p class="text-muted" style="font-size:12px;">
        Montants en FG. Coût = coût de revient des achats financés par l'associé (achat, transport, douane). Le bénéfice d'une vente n'est compté
        qu'au fur et à mesure de son encaissement ; un paiement partiel du client est réparti au prorata sur les lignes de la facture.
    </p>
</div>
@endsection
