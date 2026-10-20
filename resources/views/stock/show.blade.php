@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>{{ $store->full_name }} @if ($store->is_main)<span class="badge bg-success">Principal</span>@endif</h4>
            <h6>{{ $store->type_label }}{{ $store->address ? ' — '.$store->address : '' }}</h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('stock.index') }}" class="btn btn-cancel">Retour à la vue globale</a>
        </div>
    </div>

    <div class="row">
        @foreach ($sales as $label => $s)
        <div class="col-lg-3 col-sm-6 col-12 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="text-muted" style="font-size:12px;">Ventes — {{ $label }}</div>
                    <div class="h5 mb-0">{{ numberDelimiter($s->amount) }} FG</div>
                    <small class="text-muted">{{ $s->qty_unites }} · marge {{ numberDelimiter($s->margin) }} FG</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-8"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher un produit (libellé ou SKU)"></div>
                        <div class="col-4"><button class="btn btn-primary w-100">Filtrer</button></div>
                    </form>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Produit</th><th>SKU</th><th class="text-end">Quantité</th><th class="text-end">Prix de revient</th><th class="text-end">Prix de vente</th></tr></thead>
                            <tbody>
                                @forelse ($products as $row)
                                <tr>
                                    <td>
                                        @permission('stock.movements')
                                        <a href="{{ route('stock.trace', ['product_id' => $row->product_id]) }}">{{ $row->product?->libelle ?? 'Produit #'.$row->product_id }}</a>
                                        @else
                                        {{ $row->product?->libelle ?? 'Produit #'.$row->product_id }}
                                        @endpermission
                                    </td>
                                    <td>
                                        {{ $row->product?->sku }}
                                        @if ($row->fund_id)<small class="d-block text-muted">{{ \App\Models\Fund::labelFor($row->fund_id) }}</small>@endif
                                    </td>
                                    <td class="text-end {{ $row->quantity < 0 ? 'text-danger' : '' }}">{{ \App\Models\Product::quantiteEnUnite($row->quantity, $row->product?->unite) }}</td>
                                    <td class="text-end">{{ numberDelimiter($row->product?->price_sale ?? 0) }}</td>
                                    <td class="text-end">{{ numberDelimiter($row->product?->price ?? 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">Aucun produit en stock ici.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $products->links() }}
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">Organisation</h6>
                    <p class="mb-1" style="font-size:13px;">Rattaché à : {{ $store->parent?->store_name ?? '—' }}</p>
                    <p class="mb-1" style="font-size:13px;">Responsable : {{ $store->manager?->name ?? '—' }}</p>
                    <p class="mb-1" style="font-size:13px;">Sous-emplacements :
                        @forelse ($store->children as $child)
                            <a href="{{ route('stock.show', $child->id) }}">{{ $child->store_name }}</a>{{ $loop->last ? '' : ', ' }}
                        @empty — @endforelse
                    </p>
                    <p class="mb-0" style="font-size:13px;">Employés affectés :
                        {{ $store->users->pluck('name')->join(', ') ?: '—' }}
                    </p>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2">Derniers mouvements</h6>
                    <ul class="list-unstyled mb-0" style="font-size:12px;">
                        @forelse ($movements as $m)
                        <li class="mb-2">
                            <span class="{{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity > 0 ? '+' : '−' }}{{ \App\Models\Product::quantiteEnUnite(abs($m->quantity), $m->unite ?: $m->product?->unite) }}</span>
                            {{ $m->product?->libelle }} — {{ $m->type_label }}
                            @if ($m->counterpartStore) ({{ $m->quantity < 0 ? 'vers' : 'depuis' }} {{ $m->counterpartStore->store_name }})@endif
                            <br><span class="text-muted">{{ $m->created_at->format('d/m/Y H:i') }} · {{ $m->user?->name ?? 'système' }} · {{ $m->document_ref }}</span>
                        </li>
                        @empty
                        <li class="text-muted">Aucun mouvement enregistré.</li>
                        @endforelse
                    </ul>
                    @permission('stock.movements')
                    <a href="{{ route('stock.movements', ['store_id' => $store->id]) }}" class="d-block mt-2" style="font-size:12px;">Voir tout le journal de cet emplacement</a>
                    @endpermission
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
