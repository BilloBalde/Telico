@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Traçabilité d'un produit</h4>
            <h6>Achat → emplacement → transferts → vente → client, dans l'ordre</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-lg-5 col-sm-6">
                    <select name="product_id" class="form-control" required>
                        <option value="">Choisir un produit</option>
                        @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ (string) request('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->libelle }} ({{ $p->sku }}) — {{ $p->unite_libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <input type="text" name="document_ref" value="{{ request('document_ref') }}" class="form-control" placeholder="Limiter à un document (facture, achat…)">
                </div>
                <div class="col-lg-3 col-sm-6"><button class="btn btn-primary w-100">Afficher le parcours</button></div>
            </form>
        </div>
    </div>

    @if ($product)
    <div class="card">
        <div class="card-body">
            <h6 class="mb-2">Où se trouve « {{ $product->libelle }} » aujourd'hui</h6>
            <div class="d-flex flex-wrap gap-2">
                @forelse ($balances as $b)
                    <a href="{{ route('stock.show', $b->store_id) }}" class="badge bg-light text-dark" style="font-size:13px;border:1px solid #E2E3CF;">
                        {{ $b->store?->store_name }} ({{ $b->store?->type_label }}) : {{ $product->quantiteAvecUnite($b->quantity) }}
                    </a>
                @empty
                    <span class="text-muted">Aucun stock dans votre périmètre.</span>
                @endforelse
            </div>
            <div class="mt-2"><strong>Total : {{ $product->quantiteAvecUnite($balances->sum('quantity')) }}</strong></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Étape</th><th>Emplacement</th><th class="text-end">Qté</th><th class="text-end">Stock avant → après</th><th>Détail</th><th>Document</th><th>Par</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            @include('stock.partials.movement-row', ['m' => $m, 'showProduct' => false])
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">Aucun mouvement enregistré pour ce produit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (method_exists($movements, 'links')) {{ $movements->links() }} @endif
        </div>
    </div>
    @endif
</div>
@endsection
