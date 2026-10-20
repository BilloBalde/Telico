@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Stock par emplacement</h4>
            <h6>Vue globale des magasins, stocks internes et entrepôts</h6>
        </div>
        <div class="page-btn d-flex gap-2">
            @permission('stock.movements')
            <a href="{{ route('stock.movements') }}" class="btn btn-added">Journal des mouvements</a>
            @endpermission
            @permission('stock.adjust')
            <a href="{{ route('stock.adjust.form') }}" class="btn btn-added">Inventaire / ajustement</a>
            @endpermission
        </div>
    </div>
    @include('layouts.flash')

    <div class="row">
        @foreach ([
            ['Stock total', $totals['stock_unites']],
            ['Stock magasins', $totals['stock_magasins_unites']],
            ['Stock entrepôts', $totals['stock_entrepots_unites']],
            ['Valeur au prix de revient', numberDelimiter($totals['value']) . ' FG'],
            ['Ventes du jour', numberDelimiter($totals['sales_today']) . ' FG'],
            ['dont ventes directes entrepôts', numberDelimiter($totals['direct_today']) . ' FG'],
        ] as [$label, $value])
        <div class="col-lg-2 col-sm-4 col-6 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="text-muted" style="font-size:12px;">{{ $label }}</div>
                    <div class="h5 mb-0">{{ $value }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-lg-3 col-sm-6">
                    <select name="type" class="form-control" onchange="this.form.submit()">
                        <option value="">Tous les types</option>
                        @foreach (\App\Models\Store::TYPES as $value => $label)
                            <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Emplacement</th>
                            <th>Type</th>
                            <th>Rattaché à</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Produits en stock</th>
                            <th class="text-end">Valeur (revient)</th>
                            <th class="text-end">Ventes du jour</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('stock.show', $row->store->id) }}"><strong>{{ $row->store->store_name }}</strong></a>
                                @if ($row->store->is_main)<span class="badge bg-success ms-1">Principal</span>@endif
                                @if ((int) $row->store->status !== 1)<span class="badge bg-secondary ms-1">Inactif</span>@endif
                            </td>
                            <td>{{ $row->store->type_label }}</td>
                            <td>{{ $row->store->parent?->store_name ?? '—' }}</td>
                            <td class="text-end">{{ $row->qty_unites }}</td>
                            <td class="text-end">{{ $row->products }}</td>
                            <td class="text-end">{{ numberDelimiter($row->value) }} FG</td>
                            <td class="text-end">{{ numberDelimiter($row->sales_today) }} FG @if ($row->qty_today)<small class="d-block text-muted">{{ $row->ventes_unites }}</small>@endif</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">Aucun emplacement dans votre périmètre.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-muted mb-0" style="font-size:12px;">
                Valeur calculée au dernier prix de revient connu de chaque produit ; un produit sans coût d'achat enregistré compte pour 0.
            </p>
        </div>
    </div>
</div>
@endsection
