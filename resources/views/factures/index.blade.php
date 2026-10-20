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

        <div class="main-wrapper">
            @include('layouts.header')

            @include('layouts.sidebar')

            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Factures</h4>
                            <h6>Gerer vos Factures</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('sales.index') }}" class="btn btn-added"><img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2"></a>
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
                                    <form action="{{ route('factures.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="numero_facture" value="{{ request('numero_facture') }}" placeholder="numero facture" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="customer_id" id="customer_id" class="form-control">
                                                        <option value="">Selectionner Client</option>
                                                        @foreach ($customers as $item)
                                                        <option value="{{ $item->id }}" {{ request('customer_id') == $item->id ? 'selected' : '' }}>{{ $item->customerName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="statut" id="statut" class="form-control">
                                                        <option value="">Sélectionner un statut</option>
                                                        <option value="non payé" {{ request('statut') == 'non payé' ? 'selected' : '' }}>Non payé</option>
                                                        <option value="partiel" {{ request('statut') == 'partiel' ? 'selected' : '' }}>Partiel</option>
                                                        <option value="payé" {{ request('statut') == 'payé' ? 'selected' : '' }}>Payé</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="livraison" id="livraison" class="form-control">
                                                        <option value="">Selectionner Livraison</option>
                                                        <option value="livré" {{ request('livraison') == 'livré' ? 'selected' : '' }}>livré</option>
                                                        <option value="non livré" {{ request('livraison') == 'non livré' ? 'selected' : '' }}>Non livré</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="created_at" value="{{ request('created_at') }}" placeholder="date creation" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <a href="{{ route('factures.index') }}" class="btn btn-secondary">Annuler</a>
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
                                            <th>Information du Client</th>
                                            <th>Information Stock</th>
                                            <th>Quantité</th>
                                            <th>Montant</th>
                                            <th>Interet</th>
                                            <th>Paid</th>
                                            <th>Reste</th>
                                            <th>Status</th>
                                            <th>Livraison</th>
                                            <th>Note</th>
                                            <th>Date</th>
                                            <th>Action d'administration</th>
                                            <th>Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $data)
                                        <tr>
                                            <td><a href="{{ route('voirSales', $data->numero_facture) }}">{{ $data->numero_facture }}</a></td>
                                            <td>{{ $data->customer?->customerName ?? 'N/A' }}</td>
                                            <td>{{ $data->store?->store_name ?? 'N/A' }}</td>
                                            <td>{{ $data->quantite_resumee }}</td>
                                            <td>{{ $data->montant_total }}</td>
                                            <td>{{ $data->interet_total ?? 0 }}</td>
                                            <td>{{ $data->paiements->first()?->total_paye }}</td>
                                            <td>{{ $data->reste }}</td>
                                            @php
                                                // `paiements->sum('total_paye')` comptait plusieurs fois le même
                                                // argent : `total_paye` est un cumul, pas un montant unitaire.
                                                // Le modèle porte désormais le calcul, partagé avec la garde qui
                                                // bloque la suppression — l'état affiché et l'état qui bloque
                                                // sont ainsi forcément les mêmes.
                                                $totalPaye = $data->montantEncaisse();
                                                $encaissee = $data->aRecuUnPaiement();
                                            @endphp
                                            <td>
                                                <span class="badge
                                                    @if ($data->isPaid()) bg-success
                                                    @elseif ($data->isPartial()) bg-warning text-dark
                                                    @else bg-danger
                                                    @endif">
                                                    @if ($data->isPaid())
                                                        Payé
                                                    @elseif ($data->isPartial())
                                                        Partiel
                                                    @else
                                                        Non payé
                                                    @endif
                                                </span>
                                            </td>
                                            <td>{{ $data->livraison }}</td>
                                            <td style="max-width: 200px; white-space: normal; word-break: break-word;">
                                                {{ $data->notes }}
                                            </td>
                                            <td>{{ $data->created_at }}</td>
                                            <td>
                                                <a data-id="{{ $data->numero_facture }}" class="openModalShow btn" onclick="openViewModal({{ $data->id }}, '{{ $data->numero_facture }}')">
                                                    <img src="{{ asset('assets/img/icons/eye1.svg') }}" class="me-2" alt="img">
                                                </a>
                                                <!-- <a href="{{ route('factures.show', $data->numero_facture) }}"><img src="{{ asset('assets/img/icons/eye1.svg') }}" class="me-2" alt="img"></a> -->
                                                @if ($data->livraison !== "livré")
                                                <a href="javascript:void(0);" class="me-2" data-bs-toggle="modal" data-bs-target="#editfacture" data-id="{{ $data->id }}" data-reference="{{ $data->numero_facture }}"><img src="{{ asset('assets/img/icons/calendars.svg') }}" class="me-2" alt="img"></a>
                                                @endif
                                                @if ($encaissee)
                                                    {{-- Bouton inactif plutôt que masqué : le vendeur voit qu'une
                                                         suppression existe et lit pourquoi elle est refusée, au lieu
                                                         de chercher un bouton disparu. Le serveur refuse de toute
                                                         façon, cet affichage n'est qu'une courtoisie. --}}
                                                    <button type="button" class="btn-danger" disabled style="opacity:.4;cursor:not-allowed;"
                                                            title="{{ number_format($data->montantEncaisse(), 0, ',', ' ') }} GNF déjà encaissés — une facture payée ou partiellement payée ne peut pas être supprimée.">
                                                        <img src="{{ asset('assets/img/icons/delete.svg') }}" class="me-2" alt="Suppression impossible">
                                                    </button>
                                                @else
                                                <button type="button" class="btn-danger" data-bs-toggle="modal" data-bs-target="#deleteFactureModal-{{ $data->id }}">
                                                    <img src="{{ asset('assets/img/icons/delete.svg') }}" class="me-2" alt="Supprimer">
                                                </button>
                                                <div class="modal fade" id="deleteFactureModal-{{ $data->id }}" tabindex="-1" aria-labelledby="deleteFactureLabel-{{ $data->id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title" id="deleteFactureLabel-{{ $data->id }}">Supprimer la facture n° {{ $data->numero_facture }}</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="mb-1">Cette action va :</p>
                                                                <ul class="mb-3">
                                                                    <li>supprimer toutes les ventes rattachées ;</li>
                                                                    <li>supprimer tous les paiements rattachés ;</li>
                                                                    <li>remettre les quantités dans le <strong>stock de la boutique</strong>.</li>
                                                                </ul>
                                                                <p class="mb-0 text-danger fw-semibold">Cette action est irréversible.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form method="POST" action="{{ route('factures.destroy', $data) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <button type="submit" class="btn btn-danger">Oui, supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a class="action-set" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="true">
                                                    <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a href="{{ route('payments.voir', $data->id) }}" class="dropdown-item"><img src="{{ asset('assets/img/icons/dollar-square.svg') }}" class="me-2" alt="img">Voir Paiements</a>
                                                    </li>
                                                    @if ($data->statut !== "payé")
                                                    <li>
                                                        <a href="{{ route('payments.creation', $data->id) }}" class="dropdown-item"><img src="{{ asset('assets/img/icons/plus-circle.svg') }}" class="me-2" alt="img">Ajouter Paiement</a>
                                                    </li>
                                                    @endif
                                                </ul>
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
        <div id="viewModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:#000000aa; z-index:9999;">
            <div style="background:white; margin:10% auto; padding:20px; width:90%; max-width:600px; text-align:center; border-radius:8px;">
                <h3>Selectionner un Modele</h3>

                <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:16px;">
                    <button class="btn btn-primary" onclick="goBonDeCommande()">Bon de Commande</button>
                    <button class="btn btn-primary" onclick="goBonDeSortie()">Bon de Sortie</button>
                    <button class="btn btn-primary" onclick="goFacture()">Facture</button>
                </div>

                <br>
                <button class="btn btn-cancel" onclick="closeModal()">Fermer</button>
            </div>
        </div>

        <script>
            let selectedIdentifier = null;
            let selectedInvoiceId = null;

            function openViewModal(invoiceId, identifier) {
                selectedInvoiceId = invoiceId;
                selectedIdentifier = identifier;
                document.getElementById('viewModal').style.display = 'block';
            }

            function closeModal(){
                document.getElementById('viewModal').style.display = 'none';
            }

            function goBonDeCommande() {
                if (!selectedIdentifier) return;
                // Redirect to bon de commande view
                window.location.href = `/factures/bon-de-commande/${selectedIdentifier}`;
            }

            function goFacture() {
                if (!selectedIdentifier) return;
                // Redirect to existing facture view
                window.location.href = `/factures/${selectedIdentifier}`;
            }
            function goBonDeSortie() {
                if (!selectedIdentifier) return;
                window.location.href = `/factures/bon-de-sortie/${selectedIdentifier}`;
            }
        </script>


    @include('layouts.scripts')
    <script>
        // Le plugin DataTable (initialisé globalement dans script.js) trie par
        // défaut sur la 1ère colonne, écrasant le tri "plus récent d'abord"
        // déjà fait côté serveur. On force le tri sur "Date" (colonne 11), desc.
        $(document).ready(function () {
            if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable().order([11, 'desc']).draw();
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var deleteModal = document.getElementById('editfacture');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var deleteId = button.getAttribute('data-id');
                var deleteRef = button.getAttribute('data-reference');
                var form = deleteModal.querySelector('#deleteForm');
                form.action = '/factures/' + deleteId;

                var expenseRefSpan = deleteModal.querySelector('#expense-reference');
                expenseRefSpan.textContent = deleteRef;
            });
        });
    </script>
    @include('factures.edit')
    </body>
</html>
