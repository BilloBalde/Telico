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
            .period-field {
                display: none;
            }
            /* Le thème masque #filter_inputs par défaut partout (voir style.css :
               "#filter_inputs{display:none}"), révélé seulement en cliquant la petite
               icône entonnoir — trop facile à manquer. Le filtre doit être visible
               d'emblée sur cette page. */
            #filter_inputs {
                display: block !important;
            }
        </style>
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
                            <h4>Liste des Ventes</h4>
                            <h6>Gerer vos Ventes</h6>
                        </div>
                        <div class="page-btn">
                            {{-- <a class="btn btn-added" data-bs-toggle="modal" data-bs-target="#addsale"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Vendre</a> --}}
                            <a class="btn btn-added" href="{{ route('pos') }}">
                                Vendre
                            </a>
                            <!-- <a class="btn btn-added ms-2" href="{{ route('orders.index') }}">
                                Commandes Clients
                            </a> -->
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @include('layouts.flash')
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
                                <div class="wordset">
                                    <ul>
                                        <li>
                                            <a href="{{ route('sales.export-current-pdf', request()->query()) }}"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="PDF (filtres actifs ci-dessous, ou mois en cours si aucun filtre)"
                                            class="btn btn-sm btn-danger"
                                            style="padding: 5px 10px;">
                                                <img src="{{ asset('assets/img/icons/pdf.svg') }}" alt="pdf" style="width: 16px; height: 16px;">
                                                <span style="margin-left: 5px;">PDF</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('sales.export-current-excel', request()->query()) }}"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Excel (filtres actifs ci-dessous, ou tout l'historique si aucun filtre)"
                                            class="btn btn-sm btn-success"
                                            style="padding: 5px 10px;">
                                                <img src="{{ asset('assets/img/icons/excel.svg') }}" alt="excel" style="width: 16px; height: 16px;">
                                                <span style="margin-left: 5px;">Excel</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('sales.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="numeroFacture" value="{{ request('numeroFacture') }}" placeholder="numeroFacture" class="form-control">
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
                                                    <select name="user_id" id="user_id" class="form-control">
                                                        <option value="">Selectionner Employé</option>
                                                        @foreach ($employees as $employee)
                                                        <option value="{{ $employee->id }}" {{ request('user_id') == $employee->id ? 'selected' : '' }}>{{ $employee->username ?? $employee->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Filtre période -->
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <label for="period" class="mb-1" style="font-size:12px;color:#6b7280;">Période</label>
                                                    <select id="period" name="period" class="form-control">
                                                        <option value="">Toutes (mois en cours si vide)</option>
                                                        <option value="daily" {{ request('period') == 'daily' ? 'selected' : '' }}>Jour</option>
                                                        <option value="weekly" {{ request('period') == 'weekly' ? 'selected' : '' }}>Semaine</option>
                                                        <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Mois</option>
                                                        <option value="quarterly" {{ request('period') == 'quarterly' ? 'selected' : '' }}>Trimestre</option>
                                                        <option value="semestral" {{ request('period') == 'semestral' ? 'selected' : '' }}>Semestre</option>
                                                        <option value="yearly" {{ request('period') == 'yearly' ? 'selected' : '' }}>Année</option>
                                                        <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Personnalisé</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Champs dynamiques selon la période -->
                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="daily">
                                                <div class="form-group">
                                                    <label for="date">Jour</label>
                                                    <input type="date" id="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="weekly">
                                                <div class="form-group">
                                                    <label for="week_start">Semaine de début</label>
                                                    <input type="date" id="week_start" name="week_start" value="{{ request('week_start', \Carbon\Carbon::now()->startOfWeek()->toDateString()) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="monthly">
                                                <div class="form-group">
                                                    <label for="month">Mois</label>
                                                    <input type="month" id="month" name="month" value="{{ request('month', date('Y-m')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="quarterly">
                                                <div class="form-group">
                                                    <label for="quarter">Trimestre</label>
                                                    <select id="quarter" name="quarter" class="form-control">
                                                        @for ($q = 1; $q <= 4; $q++)
                                                            <option value="{{ $q }}" {{ (int) request('quarter', ceil(now()->month / 3)) === $q ? 'selected' : '' }}>Q{{ $q }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="semestral">
                                                <div class="form-group">
                                                    <label for="semester">Semestre</label>
                                                    <select id="semester" name="semester" class="form-control">
                                                        <option value="1" {{ (int) request('semester', now()->month <= 6 ? 1 : 2) === 1 ? 'selected' : '' }}>S1</option>
                                                        <option value="2" {{ (int) request('semester', now()->month <= 6 ? 1 : 2) === 2 ? 'selected' : '' }}>S2</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="quarterly,semestral,yearly">
                                                <div class="form-group">
                                                    <label for="year">Année</label>
                                                    <input type="number" id="year" name="year" class="form-control" value="{{ request('year', now()->year) }}" min="2020" max="2100">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="custom">
                                                <div class="form-group">
                                                    <label for="start_date">Date de début</label>
                                                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date', date('Y-m-d')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="custom">
                                                <div class="form-group">
                                                    <label for="end_date">Date de fin</label>
                                                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <a href="{{ route('sales.index') }}" class="btn btn-secondary">Remettre à jour</a>
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
                                            <th>No. Facture</th>
                                            <th>Produit</th>
                                            <th>Quantité</th>
                                            <th>Prix</th>
                                            <th>Prix Total</th>
                                            <th>Interet</th>
                                            <th>Vendeur</th>
                                            <th>Created at</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $data)
                                        <tr>
                                            <td>{{ $data->numeroFacture }}</td>
                                            <td class="productimgname">
                                                <a href="javascript:void(0);" class="product-img" onclick="showProductDetails({{ $data->product_id }})">
                                                    <img src="{{ \App\Models\Product::urlImage($data->produitImage) }}" alt="product">
                                                </a>
                                                <a href="javascript:void(0);" onclick="showProductDetails({{ $data->product_id }})">{{ $data->produit }}</a>
                                                @if ($data->is_third_party)<small class="d-block" style="color:#8A6A24;"><i class="fa fa-handshake"></i> Pris chez {{ $data->partner?->name ?? 'un partenaire' }}</small>@endif
                                            </td>
                                            <td>{{ $data->quantite_affichee }}</td>
                                            <td>{{ $data->prix_affiche }}</td>
                                            <td>{{ $data->prixTotal }}</td>
                                            <td>{{ $data->interet }}</td>
                                            <td>{{ $data->user?->username ?? $data->user?->name ?? '-' }}</td>
                                            <td>{{ $data->created_at }}</td>
                                            <td class="text-end">
                                            @if ($data->facture?->statut == 'non payé')
                                                <a class="me-3" href="{{ route('sales.edit', $data->id) }}">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                </a>
                                                @else
                                                No action
                                                @endif
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
        <!-- Modal Détails du Produit -->
        <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productDetailsModalLabel">Détails du Produit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="productDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
        @include('factures.add')

        @include('layouts.scripts')
        <script>
            // Le plugin DataTable (initialisé globalement dans script.js, dans un
            // $(document).ready) trie par défaut sur la 1ère colonne (No. Facture)
            // sans qu'on le lui demande, ce qui écrasait le tri "plus récent
            // d'abord" déjà fait côté serveur. On force ici le tri sur la colonne
            // "Created at" (index 7), descendant. Utiliser $(document).ready ici
            // (et non addEventListener('DOMContentLoaded')) est important : ce
            // script est chargé en bas de page, après que l'évènement natif ait
            // déjà pu se déclencher — jQuery gère ce cas et exécute immédiatement.
            $(document).ready(function () {
                if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('.datanew')) {
                    $('.datanew').DataTable().order([7, 'desc']).draw();
                }
            });
        </script>
        <script>
            function togglePeriodFields() {
                const period = document.getElementById('period').value;
                document.querySelectorAll('.period-field').forEach(function (field) {
                    const periods = field.dataset.period.split(',');
                    field.style.display = periods.includes(period) ? 'block' : 'none';
                });
            }

            document.getElementById('period').addEventListener('change', togglePeriodFields);
            window.addEventListener('load', togglePeriodFields);
        </script>
        <script>
        function showProductDetails(productId) {
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
            modal.show();
            
            // Obtenir le token CSRF s'il existe
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = metaToken ? metaToken.getAttribute('content') : '';
            
            // Préparer les headers
            const headers = {
                'Accept': 'application/json'
            };
            
            // Ajouter le token seulement s'il existe
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }
            
            // Charger les détails du produit via AJAX
            fetch(`/products/${productId}`, {
                method: 'GET',
                headers: headers
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayProductDetails(data.product);
                } else {
                    document.getElementById('productDetailsContent').innerHTML = '<p class="text-danger">Erreur lors du chargement des détails du produit.</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('productDetailsContent').innerHTML = '<p class="text-danger">Une erreur est survenue.</p>';
            });
        }

        function displayProductDetails(product) {
            const content = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="/products/${product.image}" alt="${product.libelle}" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 40%">Nom du produit</th>
                                <td>${product.libelle}</td>
                            </tr>
                            <tr>
                                <th>SKU</th>
                                <td>${product.sku || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Catégorie</th>
                                <td>${product.category_name || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>${product.description || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Prix de Vente</th>
                                <td>${product.price ? new Intl.NumberFormat('fr-FR').format(product.price) + ' F' : 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Prix de revient</th>
                                <td>${product.price_sale ? new Intl.NumberFormat('fr-FR').format(product.price_sale) + ' F' : 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
            
            document.getElementById('productDetailsContent').innerHTML = content;
        }
        </script>
    </body>
</html>
