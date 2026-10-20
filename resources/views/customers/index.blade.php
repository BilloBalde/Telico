<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.flash')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste Clients</h4>
                            <h6>Gerer vos Clients</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('customers.create') }}" class="btn btn-added"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Add Customer</a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                        <a class="btn btn-searchset">
                                            <img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Mark</th>
                                            <th>Nom</th>
                                            <th>Phone</th>
                                            <th>email</th>
                                            <th>Address</th>
                                            <th>Balance</th>
                                            <th>Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $item)
                                        <tr>
                                            <td>
                                                <a class="btn btn-success" style="color: white" href="{{ route('paiementsClient.add', $item->mark) }}" title="View Transactions">
                                                    {{ $item->mark }}
                                                </a>
                                            </td>
                                            <td>{{ $item->customerName }}</td>
                                            <td>{{ $item->tel }}</td>
                                            <td>{{ $item->email }}</td>
                                            <td>{{ $item->address }}</td>
                                            <td>{{ auth()->user()->isStoreScoped() ? (float) $item->balance_perimetre : $item->balance }} FG</td>
                                            <td>
                                                <a href="{{ route('customers.edit', $item->id) }}" class="btn btn-warning btn-sm me-2"><i class="fas fa-edit"></i> Modifier</a >
                                                <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteCustomerModal"
                                                    data-customer-id="{{ $item->id }}"
                                                    data-customer-name="{{ $item->customerName }}">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
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
        <div class="modal fade" id="deleteCustomerModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer ce client? Cette action supprimera également:</p>
                        <ul>
                            <li>Toutes les factures associées</li>
                            <li>Tous les paiements associés</li>
                        </ul>
                        <p class="text-danger"><strong>Cette action est irréversible!</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <form id="deleteCustomerForm" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
        <!-- Delete Confirmation Modal -->

<!-- JavaScript to set up the modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var deleteModal = document.getElementById('deleteCustomerModal');
    var deleteForm = document.getElementById('deleteCustomerForm');
    
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var customerId = button.getAttribute('data-customer-id');
        var customerName = button.getAttribute('data-customer-name');
        
        // Update modal content
        var modalBody = deleteModal.querySelector('.modal-body');
        modalBody.querySelector('p:first-child').innerHTML = 
            'Êtes-vous sûr de vouloir supprimer le client <strong>"' + customerName + '"</strong>?';
        
        // Update form action
        deleteForm.action = '/customers/' + customerId;
    });
});
</script>
    </body>
</html>

