<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Produits</h4>
                            <h6>Gerer vos Produits</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('produits.create') }}" class="btn btn-added"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Ajouter Produit</a>
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
                                <!-- <div class="wordset">
                                    <ul>
                                        <li>
                                            <a href="{{ route('produits.export-pdf') }}" data-bs-toggle="tooltip" data-bs-placement="top" title="pdf"><img src="{{ asset('assets/img/icons/pdf.svg') }}" alt="img"></a>
                                        </li>
                                        <li>
                                            <a href="{{ route('produits.export-csv') }}" data-bs-toggle="tooltip" data-bs-placement="top" title="excel"><img src="{{ asset('assets/img/icons/excel.svg') }}" alt="img"></a>
                                        </li>
                                    </ul>
                                </div> -->
                                <div class="export-buttons" style="margin: 20px 0;">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('new.products.csv') }}" class="btn btn-info" target="_blank">
                                            <i class="fas fa-file-csv"></i> CSV (Ultra léger)
                                        </a>
                                        
                                        <a href="{{ route('new.products.excel') }}" class="btn btn-success" target="_blank">
                                            <i class="fas fa-file-excel"></i> Excel
                                        </a>
                                        
                                        <a href="{{ route('new.products.pdf-simple') }}" class="btn btn-warning" target="_blank">
                                            <i class="fas fa-file-pdf"></i> PDF Simple (Sans images)
                                        </a>
                                        
                                        <a href="{{ route('exports.products.html') }}" class="btn btn-danger" target="_blank">
                                            <i class="fas fa-file-pdf"></i> PDF Complet (Avec images)
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body border-top" id="filter_inputs" style="display: none;">
                                <form action="{{ route('produits.index') }}" method="GET"> <!-- Update to GET method -->
                                    @csrf
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <input type="text" name="libelle" value="{{ request('libelle') }}" placeholder="libelle" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <select name="category_id" id="category_id" class="form-control">
                                                    <option value="">Selectionner Category</option>
                                                    @foreach ($categories as $item)
                                                    <option value="{{ $item->id }}" {{ request('category_id') == $item->id ? 'selected' : '' }}>{{ $item->category_type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                            <div class="form-group d-flex">
                                                <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                <a href="{{ route('produits.index') }}" class="btn btn-secondary">Reset</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Date d'ajout</th>
                                            <th>Nom Produit</th>
                                            <th>Identifiant Stock</th>
                                            <th>Category</th>
                                            <th>Qtité en Stock</th>
                                            <th>Prix Vente</th>
                                            <th>Prix Revient</th>
                                            <th>Prix min.</th>
                                            <th>Image</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($allProducts as $dataItem)
                                            @php
                                                // Calculate the quantity for the connected user's store or total quantity
                                                if ($userStoreId) {
                                                    // For users with role_id 3, we get the quantity from the specific store
                                                    // (collection déjà chargée — pas de requête par ligne)
                                                    $store = $dataItem->stores->firstWhere('id', $userStoreId);
                                                    $quantity = $store ? $store->pivot->quantity : 0; // Access pivot quantity
                                                } else {
                                                    // For other roles, we sum the quantity across all stores
                                                    $quantity = $dataItem->stores->sum('pivot.quantity');
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $dataItem->updated_at }}</td>
                                                <td>{{ $dataItem->libelle }}</td>
                                                <td>{{ $dataItem->sku }}</td>
                                                <td>
                                                    @foreach ($dataItem->categories as $category)
                                                        {{ $category->slug }}
                                                    @endforeach
                                                </td>
                                                <td>{{ $dataItem->quantiteAvecUnite($quantity) }}</td>
                                                <td>{{ $dataItem->price }} FG</td>
                                                <td>{{ $dataItem->price_sale ?? '-' }} FG</td>
                                                <td>{{ $dataItem->price_sale_minimum !== null ? number_format((float) $dataItem->price_sale_minimum, 0, ',', ' ') . ' FG' : '—' }}</td>
                                                <td>
                                                    <img src="{{ $dataItem->image_url }}" alt="product" style="width: 150px; height: 100px;">
                                                </td>
                                                <td>
                                                    <a class="me-3" href="{{ route('produits.edit', $dataItem->id) }}">
                                                        <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                    </a>
                                                    <a
                                                        type="button"
                                                        class="me-3 deleteButtionItem"
                                                        data-bs-toggle="modal"
                                                        data-slug="{{ $dataItem->sku }}"
                                                        data-bs-target="#confirmDeleteModal"
                                                        onclick="setDeleteFormAction('{{ route('produits.destroy', $dataItem->id) }}')">
                                                        <img src="{{ asset('assets/img/icons/delete.svg') }}" class="me-2" alt="img">
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
        @include('layouts.delete')
        @include('products.delete')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $('#category_id').select2({
                    width: '100%',
                    placeholder: 'Rechercher une catégorie…',
                });
            });
        </script>
    </body>
</html>
