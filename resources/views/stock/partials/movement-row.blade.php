<tr>
    <td style="white-space:nowrap;">{{ $m->created_at->format('d/m/Y H:i') }}</td>
    <td><span class="badge {{ $m->quantity < 0 ? 'bg-danger' : 'bg-success' }}">{{ $m->type_label }}</span></td>
    @if ($showProduct ?? true)
    <td>{{ $m->product?->libelle ?? 'Produit #'.$m->product_id }}</td>
    @endif
    <td>
        {{ $m->store?->store_name ?? 'Emplacement #'.$m->store_id }}
        @if ($m->fund_id)<small class="d-block text-muted">{{ \App\Models\Fund::labelFor($m->fund_id) }}</small>@endif
    </td>
    <td class="text-end {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity > 0 ? '+' : '−' }}{{ \App\Models\Product::quantiteEnUnite(abs($m->quantity), $m->unite ?: $m->product?->unite) }}</td>
    <td class="text-end text-muted">{{ $m->stock_before }} → {{ $m->stock_after }}</td>
    <td>
        @if ($m->counterpartStore){{ $m->quantity < 0 ? 'Vers' : 'Depuis' }} {{ $m->counterpartStore->store_name }}<br>@endif
        @if ($m->supplier)Fournisseur : {{ $m->supplier->name }}<br>@endif
        @if ($m->customer)Client : {{ $m->customer->customerName }}<br>@endif
        @if ($m->unit_price !== null)Prix : {{ numberDelimiter($m->unit_price) }} FG<br>@endif
        @if ($m->reason)<span class="text-muted">{{ $m->reason }}</span>@endif
    </td>
    <td>{{ $m->document_ref }}</td>
    <td>{{ $m->user?->name ?? '—' }}</td>
</tr>
