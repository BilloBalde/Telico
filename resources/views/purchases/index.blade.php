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
                            <h4>Liste d'Achat</h4>
                            <h6>Gerer vos Achats</h6>
                        </div>
                        <div class="page-btn">
                            <a class="btn btn-added" data-bs-toggle="modal" data-bs-target="#addpurchase"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Ajouter Achat</a>
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
                                    <form action="{{ route('purchases.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="numeroPurchase" value="{{ request('numeroPurchase') }}" placeholder="numeroPurchase" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="product_id" id="product_id" class="form-control">
                                                        <option value="">Selectionner Produit</option>
                                                        @foreach ($produits as $item)
                                                        <option value="{{ $item->id }}" {{ request('product_id') == $item->id ? 'selected' : '' }}>{{ $item->libelle }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="created_at" value="{{ request('created_at') }}" placeholder="date creation" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Annuler Recherche</a>
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
                                            <th>Reference</th>
                                            <th>Produit</th>
                                            <th>Prix</th>
                                            <th>Quantity</th>
                                            <th>Description</th>
                                            <th>Created at</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $data)
                                        <tr>
                                            <td>{{ $data->numeroPurchase }}</td>
                                            <td>
                                                @php
                                                $categoryList = [];
                                                foreach ($data->product->categories as $category){
                                                    $categoryList[] = $category->slug . ' (' . $category->category_type . ')';
                                                }
                                                $categoryListString = implode(', ', $categoryList);
                                                @endphp
                                                {{ $data->product->sku }}
                                                
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
                                            <td>{{ $data->price }}</td>
                                            <td>{{ $data->quantity }}</td>
                                            <td>{{ $data->description }}</td>
                                            <td>{{ $data->created_at }}</td>
                                            <td class="text-end">
                                                <a class="me-3" href="{{ route('purchases.edit', $data->id) }}">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
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
        @include('logistics.create')
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
