<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            /* La pagination côté serveur (Laravel) fait déjà foi ici — celle
               générée automatiquement par le plugin DataTable ferait doublon
               et affiche un total incorrect (limité à la page chargée). */
            .dataTables_paginate, .dataTables_info {
                display: none !important;
            }
        </style>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>
        <style>
            /* Styling the product image */
            #productImage {
                max-width: 100%; /* Ensures the image does not overflow */
                max-height: 300px; /* Limits the height of the image */
                display: block; /* Ensures proper alignment */
                margin: 0 auto; /* Centers the image horizontally */
                border-radius: 10px; /* Adds rounded corners */
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); /* Adds a subtle shadow */
            }

            /* Modal body layout */
            .modal-body {
                display: flex;
                flex-wrap: wrap;
                gap: 20px; /* Adds space between the image and text */
            }

            /* Text section styling */
            .modal-body h5 {
                font-size: 1.5rem; /* Larger font size for the title */
                font-weight: bold;
                color: #333; /* Darker color for better readability */
            }

            .modal-body p {
                font-size: 1rem;
                margin-bottom: 8px;
            }
        </style>
        <div class="main-wrapper">
            @include('layouts.header')

            @include('layouts.sidebar')

            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Transferts</h4>
                            <h6>Gerer vos Transferts Stock</h6>
                        </div>
                        <div class="page-btn">
                            @permission('transfers.create')
                            <a class="btn btn-added" data-bs-toggle="modal" data-bs-target="#addTransfer"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Nouveau transfert</a>
                            @endpermission
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-path">
                                        <a class="btn btn-filter" id="filter_search">
                                            <img src="{{ asset('assets/img/icons/filter.svg') }}" alt="img">
                                            <span><img src="{{ asset('assets/img/icons/closes.svg') }}" alt="img"></span>
                                        </a>
                                    </div>
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('transfers.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-2 col-sm-2 col-12">
                                                <div class="form-group">
                                                    <select name="product_id" id="product_id" class="form-control">
                                                        <option value="">Selectionner Produit</option>
                                                        @foreach ($produits as $item)
                                                        <option value="{{ $item->id }}" {{ request('product_id') == $item->id ? 'selected' : '' }}>{{ $item->libelle }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-3 col-12">
                                                <div class="form-group">
                                                    <select name="from_store_id" id="from_store_id" class="form-control">
                                                        <option value="">Selectionner Source</option>
                                                        @foreach ($boutiques as $item)
                                                        <option value="{{ $item->id }}" {{ request('from_store_id') == $item->id ? 'selected' : '' }}>{{ $item->store_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-3 col-12">
                                                <div class="form-group">
                                                    <select name="to_store_id" id="to_store_id" class="form-control">
                                                        <option value="">Selectionner Destination</option>
                                                        @foreach ($boutiques as $item)
                                                        <option value="{{ $item->id }}" {{ request('to_store_id') == $item->id ? 'selected' : '' }}>{{ $item->store_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-2 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="created_at" value="{{ request('created_at') }}" placeholder="date creation" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-2 col-12">
                                                <div class="form-group">
                                                    <select name="status" class="form-control">
                                                        <option value="">Tous les statuts</option>
                                                        @foreach (\App\Models\StockTransfer::STATUSES as $value => $label)
                                                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-3 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <a href="{{ route('transfers.index') }}" class="btn btn-secondary">Annuler Recherche</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Produit</th>
                                            <th>Source</th>
                                            <th>Destination</th>
                                            <th>Quantité</th>
                                            <th>Statut</th>
                                            <th>Suivi</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $data)
                                        <tr>
                                            <td>{{ $data->reference }}</td>
                                            <td>
                                                @php
                                                $categoryList = [];
                                                foreach ($data->product->categories as $category){
                                                    $categoryList[] = $category->slug . ' (' . $category->category_type . ')';
                                                }
                                                $categoryListString = implode(', ', $categoryList);
                                                @endphp
                                                <a
                                                    type="button"
                                                    class="me-3 showProductDetails"
                                                    data-bs-toggle="modal"
                                                    data-libelle="{{ $data->product->libelle }}"
                                                    data-sku="{{ $data->product->sku }}"
                                                    data-categories="{{ $categoryListString }}"
                                                    data-CBM="{{ ($data->product->longueure * $data->product->largeure * $data->product->profondeure) * 0.000001 }}"
                                                    data-image="{{ $data->product->image }}"
                                                    data-bs-target="#showProductDetailModal">
                                                    {{ $data->product->sku }}
                                                </a>
                                            </td>
                                            <div class="modal fade" id="showProductDetailModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="productModalLabel">Détails du Produit</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-lg-6 col-md-6">
                                                                    <img id="productImage" src="" alt="Image Produit" class="img-fluid rounded">
                                                                </div>
                                                                <div class="col-lg-6 col-md-6">
                                                                    <h5 id="productLibelle"></h5>
                                                                    <p><strong>SKU:</strong> <span id="productSku"></span></p>
                                                                    <p><strong>Catégories:</strong> <span id="productCategories"></span></p>
                                                                    <p><strong>CBM:</strong> <span id="productCBM"></span></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <td>{{ $data->fromStore?->full_name }} <small class="text-muted">({{ $data->fromStore?->type_label }})</small></td>
                                            <td>{{ $data->toStore?->full_name }} <small class="text-muted">({{ $data->toStore?->type_label }})</small></td>
                                            <td>
                                                {{ $data->quantite_affichee }}
                                                @if ($data->fund_id)<small class="d-block text-muted">{{ \App\Models\Fund::labelFor($data->fund_id) }}</small>@endif
                                            </td>
                                            <td>
                                                @php
                                                    $badge = [
                                                        'pending' => 'bg-warning', 'approved' => 'bg-info', 'in_transit' => 'bg-primary',
                                                        'completed' => 'bg-success', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary',
                                                    ][$data->status] ?? 'bg-secondary';
                                                @endphp
                                                <span class="badge {{ $badge }}">{{ $data->status_label }}</span>
                                            </td>
                                            <td style="font-size:12px; line-height:1.5;">
                                                Demandé {{ $data->created_at?->format('d/m/Y H:i') }}{{ $data->requester ? ' par '.$data->requester->name : '' }}<br>
                                                @if ($data->approved_at) {{ $data->status === 'rejected' ? 'Refusé' : 'Autorisé' }} {{ $data->approved_at->format('d/m/Y H:i') }}{{ $data->approver ? ' par '.$data->approver->name : '' }}<br>@endif
                                                @if ($data->shipped_at) Expédié {{ $data->shipped_at->format('d/m/Y H:i') }}{{ $data->shipper ? ' par '.$data->shipper->name : '' }}<br>@endif
                                                @if ($data->received_at) Réceptionné {{ $data->received_at->format('d/m/Y H:i') }}{{ $data->receiver ? ' par '.$data->receiver->name : '' }}@endif
                                            </td>
                                            <td class="text-end" style="white-space:nowrap;">
                                                @if ($data->status === 'pending')
                                                    @permission('transfers.approve')
                                                    <form action="{{ route('transfers.approve', $data) }}" method="POST" class="d-inline">@csrf
                                                        <button class="btn btn-sm btn-success" title="Autoriser">Autoriser</button>
                                                    </form>
                                                    <form action="{{ route('transfers.reject', $data) }}" method="POST" class="d-inline" onsubmit="var r = prompt('Motif du refus ?'); if (r === null) return false; this.reason.value = r;">@csrf
                                                        <input type="hidden" name="reason">
                                                        <button class="btn btn-sm btn-outline-danger" title="Refuser">Refuser</button>
                                                    </form>
                                                    @endpermission
                                                @elseif ($data->status === 'approved')
                                                    @permission('transfers.execute')
                                                    <form action="{{ route('transfers.ship', $data) }}" method="POST" class="d-inline">@csrf
                                                        <button class="btn btn-sm btn-primary" title="Expédier">Expédier</button>
                                                    </form>
                                                    @endpermission
                                                @elseif ($data->status === 'in_transit')
                                                    @permission('transfers.execute')
                                                    <form action="{{ route('transfers.receive', $data) }}" method="POST" class="d-inline">@csrf
                                                        <button class="btn btn-sm btn-success" title="Réceptionner">Réceptionner</button>
                                                    </form>
                                                    @endpermission
                                                @endif
                                                @if (in_array($data->status, ['pending', 'approved', 'in_transit'], true)
                                                    && (auth()->user()->hasPermission('transfers.approve') || (int) $data->requested_by === (int) auth()->id()))
                                                    <form action="{{ route('transfers.cancel', $data) }}" method="POST" class="d-inline" onsubmit="return confirm('Annuler ce transfert ?');">@csrf
                                                        <button class="btn btn-sm btn-outline-secondary" title="Annuler">Annuler</button>
                                                    </form>
                                                @endif
                                                @if ($data->status === 'completed')
                                                    @permission('transfers.edit')
                                                    <a class="me-2" href="{{ route('transfers.edit', $data->id) }}" title="Corriger la quantité">
                                                        <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                    </a>
                                                    @endpermission
                                                @endif
                                                <a class="me-2" href="{{ route('receipts.transfers.show', $data->id) }}">
                                                    <img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img">
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mt-3 d-flex justify-content-center">
                                    {{ $dataTable->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('transfers.create')
        @include('layouts.scripts')
        <script>
            document.querySelectorAll('.showProductDetails').forEach(item => {
                item.addEventListener('click', function () {
                    // Get product details from data attributes
                    const libelle = this.getAttribute('data-libelle');
                    const sku = this.getAttribute('data-sku');
                    const categories = this.getAttribute('data-categories');
                    const cbm = this.getAttribute('data-CBM');
                    const image = this.getAttribute('data-image');

                    // Set modal content
                    document.getElementById('productLibelle').innerText = libelle;
                    document.getElementById('productSku').innerText = sku;
                    document.getElementById('productCategories').innerText = categories;
                    document.getElementById('productCBM').innerText = cbm;

                    // Update image source
                    document.getElementById('productImage').src = image ? `{{ asset('products/') }}/${image}` : "{{ asset('assets/img/produit-sans-photo.svg') }}";
                });
            });
        </script>
    </body>
</html>
