@php
    // Quantité vendable : somme des emplacements accessibles à l'utilisateur
    // (son magasin, ses stocks internes et entrepôts rattachés ; tout pour
    // une vue globale). L'emplacement exact se choisit ensuite ligne par ligne.
    $quantity = $dataItem->stores
        ->filter(fn ($s) => in_array($s->id, $locationIds ?? [], true))
        ->sum('pivot.quantity');
@endphp
@if($quantity > 0)
<div class="col-lg-4 col-sm-4 col-6 d-flex product-item"
     data-numeroFacture="{{ $numeroFacture }}"
     data-sku="{{ $dataItem->sku }}"
     data-id="{{ $dataItem->id }}"
     data-price="{{ $dataItem->price }}"
     {{-- Prix de revient : sert au blocage de la vente à perte côté POS.
          Le contrôle faisant foi reste celui de SaleController::store(). --}}
     data-cost="{{ $dataItem->price_sale ?? 0 }}"
     {{-- Prix de vente minimum fixé par le gérant (facultatif). --}}
     data-min-price="{{ $dataItem->price_sale_minimum ?? 0 }}">
    <div class="productset flex-fill">
        <div class="productsetimg">
            <img src="{{ asset('products/' . $dataItem->image) }}" alt="img" style="height: 170px" loading="lazy" onerror="this.closest('.productsetimg').classList.add('img-fallback')">
            <div class="check-product">
                <i class="fa fa-check"></i>
            </div>
        </div>
        <div class="productsetcontent">
            <span class="cat-chip">
                @foreach ($dataItem->categories as $cat)
                {{ $cat->slug }}
                @endforeach
            </span>
            <h5>{{ $dataItem->libelle }}</h5>
            <h4>{{ $dataItem->sku }}</h4>
            <h4 class="price-vente">{{ number_format($dataItem->price ?? 0, 0, ',', ' ') }} FG <small style="font-weight:400;font-size:.75em;">/ {{ \App\Models\Product::motUnite($dataItem->unite) }}</small></h4>
            <span class="stock-badge {{ $quantity <= 5 ? 'stock-low' : 'stock-ok' }}">
                @if($quantity <= 5)
                    <i class="fa fa-exclamation-triangle"></i> Stock faible : {{ $dataItem->quantiteAvecUnite($quantity) }}
                @else
                    <i class="fa fa-check-circle"></i> En stock : {{ $dataItem->quantiteAvecUnite($quantity) }}
                @endif
            </span>
            @if(!$userStoreId)
                <div class="store-stock-breakdown">
                    @foreach ($dataItem->stores->where('pivot.quantity', '>', 0)->whereIn('id', $locationIds ?? []) as $store)
                        <span class="store-pill">
                            <i class="fa fa-shop"></i> {{ $store->store_name }} : {{ $dataItem->quantiteAvecUnite($store->pivot->quantity) }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endif
