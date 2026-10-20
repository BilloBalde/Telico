<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<body>
    <div id="global-loader">
        <div class="whirly-loader"></div>
    </div>

    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')
        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Commande #{{ $order->id }}</h4>
                        <h6>Détails et suivi de la commande</h6>
                    </div>
                    <div class="page-btn">
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                @include('layouts.flash')

                <div class="row">
                    {{-- Colonne gauche --}}
                    <div class="col-lg-8">
                        {{-- Carte infos commande --}}
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Informations générales</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">N° Commande</label>
                                            <input type="text" class="form-control" value="#{{ $order->id }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Statut</label>
                                            <div>
                                                @if($order->status == 'pending')
                                                    <span class="badges bg-warning">En attente</span>
                                                @elseif($order->status == 'approved')
                                                    <span class="badges bg-success">Approuvée</span>
                                                @elseif($order->status == 'rejected')
                                                    <span class="badges bg-danger">Rejetée</span>
                                                @else
                                                    <span class="badges bg-secondary">{{ $order->status }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Client</label>
                                            <input type="text" class="form-control" value="{{ $order->user?->name ?? 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Email</label>
                                            <input type="text" class="form-control" value="{{ $order->user?->email ?? 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" class="form-control" value="{{ $order->user?->phone ?? 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Date de commande</label>
                                            <input type="text" class="form-control" value="{{ $order->created_at->format('d/m/Y H:i') }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Carte articles commandés --}}
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Articles commandés</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table datanew">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Quantité</th>
                                                <th>Prix unitaire</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product?->libelle ?? 'Produit #'.$item->product_id }}</td>
                                                <td>{{ $item->quantite_affichee }}</td>
                                                <td>{{ number_format($item->price, 0, ',', ' ') }} GNF <small class="text-muted">/ {{ $item->mot_unite }}</small></td>
                                                <td class="font-weight-bold">{{ number_format($item->quantity * $item->price, 0, ',', ' ') }} GNF</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row justify-content-end mt-3">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label class="form-label">Montant total</label>
                                            <input type="text" class="form-control font-weight-bold" value="{{ number_format($order->total_amount, 0, ',', ' ') }} GNF" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Carte adresse de livraison --}}
                        @if($order->address)
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Adresse de livraison</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Quartier / Région</label>
                                            <input type="text" class="form-control" value="{{ $order->address?->place?->name ?? 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label class="form-label">Adresse complète</label>
                                            <input type="text" class="form-control" value="{{ $order->address->full_address ?? 'N/A' }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Colonne droite --}}
                    <div class="col-lg-4">
                        {{-- Carte paiement --}}
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Paiement</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Mode de paiement</label>
                                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Statut de paiement</label>
                                    <div>
                                        @if($order->payment_status == 'paid')
                                            <span class="badges bg-success">Payé</span>
                                        @elseif($order->payment_status == 'pending')
                                            <span class="badges bg-warning">En attente</span>
                                        @else
                                            <span class="badges bg-danger">Non payé</span>
                                        @endif
                                    </div>
                                </div>
                                @if($order->transaction_id)
                                <div class="form-group">
                                    <label class="form-label">ID Transaction</label>
                                    <input type="text" class="form-control" value="{{ $order->transaction_id }}" readonly>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Carte actions --}}
                        @if($order->status == 'pending')
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Actions</h4>
                            </div>
                            <div class="card-body">
                                <p class="mb-2" style="font-size:13px;">
                                    Traitée par : <strong>{{ $order->store?->store_name ?? 'le siège' }}</strong>
                                    @if ($order->assigned_at)
                                        <span class="text-muted">(attribuée le {{ $order->assigned_at->format('d/m/Y H:i') }}{{ $order->assignedBy ? ' par '.$order->assignedBy->name : '' }})</span>
                                    @endif
                                </p>
                                @if (auth()->user()->hasPermission('orders.assign') && ! auth()->user()->isRestrictedToStore())
                                <form method="POST" action="{{ route('admin.orders.assign', $order) }}" class="mb-3">
                                    @csrf
                                    <label style="font-size:12px;" class="text-muted mb-1">Attribuer à une boutique</label>
                                    <select name="store_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value="">Siège (traitement central)</option>
                                        @foreach (\App\Models\Store::sectionsWithIds() as $section)
                                            <option value="{{ $section->id }}" {{ (int) $order->store_id === $section->id ? 'selected' : '' }}>{{ $section->store_name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                @endif
                                @if ($order->store_id && auth()->user()->isStoreScoped())
                                <form method="POST" action="{{ route('admin.orders.release', $order) }}" class="mb-3"
                                      onsubmit="var r = prompt('Motif (facultatif) : pourquoi rendre la commande au siège ?'); if (r === null) return false; this.reason.value = r;">
                                    @csrf
                                    <input type="hidden" name="reason">
                                    <button type="submit" class="btn btn-outline-secondary btn-block w-100">
                                        <i class="fas fa-undo me-1"></i> Rendre au siège
                                    </button>
                                </form>
                                @endif
                                @unless (auth()->user()->hasPermission('orders.validate'))
                                <div class="alert alert-info py-2 mb-0" style="font-size:13px;">
                                    Votre compte n'a pas le droit d'approuver les commandes. Demandez au responsable ou à l'administrateur.
                                </div>
                                @endunless
                                @permission('orders.validate')
                                {{-- Bouton qui ouvre la modale --}}
                                <button type="button"
                                    class="btn btn-success btn-block mb-2 btn-approve"
                                    data-order-id="{{ $order->id }}"
                                    data-order-label="#{{ $order->id }} — {{ $order->user?->name ?? 'Client' }}"
                                    data-approve-url="{{ route('admin.orders.approve', $order) }}"
                                    data-check-url="{{ route('admin.orders.stock.check', $order) }}">
                                    <i class="fas fa-check me-1"></i> Approuver la commande
                                </button>

                                <form action="{{ route('admin.orders.reject', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-block">
                                        <i class="fa fa-times"></i> Rejeter la commande
                                    </button>
                                </form>
                                @endpermission
                            </div>
                        </div>
                        @else
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Statut</h4>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Cette commande a déjà été traitée et ne peut plus être modifiée.</p>
                            </div>
                        </div>
                        @endif

                        {{-- Carte facture (si approuvée) --}}
                        @if($order->facture)
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Facture</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">N° Facture</label>
                                    <input type="text" class="form-control" value="{{ $order->facture->numero_facture }}" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Statut paiement</label>
                                    <div>
                                        @if($order->facture->statut == 'payé')
                                            <span class="badges bg-success">Payé</span>
                                        @elseif($order->facture->statut == 'partiel')
                                            <span class="badges bg-warning">Partiel</span>
                                        @else
                                            <span class="badges bg-danger">Non payé</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal approbation (identique à celle de l'index) --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
            <div class="modal-content" style="border-radius:16px;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#1a0e00,#7c3a00);border:none;padding:20px 24px;">
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-1">
                            <i class="fas fa-store me-2"></i>Approuver la commande
                        </h5>
                        <p class="text-white-50 mb-0" style="font-size:13px;" id="modalOrderLabel"></p>
                    </div>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Fermer">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    {{-- Un emplacement de stock par produit. --}}
                    <p class="fw-semibold mb-2" style="font-size:13px;">
                        <i class="fas fa-boxes me-1 text-info"></i> Emplacement du stock pour chaque produit
                    </p>
                    <div id="stockCheckLoading" style="display:none;" class="text-center py-3 text-muted">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Chargement du stock…
                    </div>
                    <div id="stockCheckZone" style="display:none;">
                        <div id="stockCheckList"></div>
                    </div>
                    {{-- Magasin qui facture : automatique d'après les lignes. --}}
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            <i class="fas fa-store-alt me-1 text-warning"></i>
                            Magasin de vente (facture)
                        </label>
                        <select id="storeSelect" class="form-control" style="border-radius:10px;">
                            <option value="">Automatique (d'après l'emplacement des produits)</option>
                            @foreach(\App\Models\Store::optionsFor(auth()->user())->where('type', \App\Models\Store::TYPE_MAGASIN) as $store)
                                <option value="{{ $store->id }}">{{ $store->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:16px 24px;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="confirmApproveBtn" class="btn btn-success btn-sm" disabled>
                        <i class="fas fa-check mr-1"></i> Confirmer l'approbation
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    @include('admin.orders.partials.location-picker')
    @include('layouts.delete')

    <script>
    $(function () {
        var csrfToken       = $('meta[name="csrf-token"]').attr('content');
        var currentCheckUrl = '';
        var currentApproveUrl = '';
        var stockOk = false;

        $(document).on('click', '.btn-approve', function () {
            currentCheckUrl   = $(this).data('check-url');
            currentApproveUrl = $(this).data('approve-url');
            $('#modalOrderLabel').text('Commande ' + $(this).data('order-label'));
            $('#storeSelect').val('');
            $('#stockCheckList').html('');
            $('#confirmApproveBtn').prop('disabled', true);
            stockOk = false;
            $('#approveModal').modal('show');
            // Une ligne « emplacement » par produit, affichée tout de suite.
            window.loadOrderLines(currentCheckUrl, function (ok) {
                stockOk = ok;
                $('#confirmApproveBtn').prop('disabled', !stockOk);
            });
        });

        $(document).on('change', '#stockCheckList .item-location', function () {
            stockOk = window.refreshOrderLines();
            $('#confirmApproveBtn').prop('disabled', !stockOk);
        });

        $('#confirmApproveBtn').on('click', function () {
            if (!stockOk) return;
            $('#confirmApproveBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm mr-1"></span> Traitement…');
            $.ajax({
                url:    currentApproveUrl,
                method: 'POST',
                timeout: 30000, // évite un blocage indéfini de la modale si le serveur traîne
                data:   { _token: csrfToken, store_id: $('#storeSelect').val(), locations: window.collectOrderLocations() },
                success: function (response) {
                    // Si la réponse est un objet JSON (succès) on recharge la page ou on redirige
                    if (response.success === false) {
                        showToast(response.message || 'Erreur', 'error');
                        $('#confirmApproveBtn').prop('disabled', false)
                            .html('<i class="fas fa-check mr-1"></i> Confirmer l\'approbation');
                        return;
                    }
                    // Succès : redirection vers l'index
                    window.location.href = '{{ route("admin.orders.index") }}';
                },
                error: function (xhr, status) {
                    $('#confirmApproveBtn').prop('disabled', false)
                        .html('<i class="fas fa-check mr-1"></i> Confirmer l\'approbation');
                    var msg = status === 'timeout'
                        ? 'Le serveur met trop de temps à répondre. Vérifiez si la commande a été traitée avant de réessayer.'
                        : (xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Une erreur est survenue. Veuillez réessayer.');
                    showToast(msg, 'error');
                }
            });
        });

        // Échappatoire : si la modale reste ouverte trop longtemps (requête bloquée
        // côté serveur), on force sa fermeture pour ne pas immobiliser toute la page.
        $('#approveModal').on('shown.bs.modal', function () {
            clearTimeout(window.__approveModalSafety);
            window.__approveModalSafety = setTimeout(function () {
                $('#approveModal').modal('hide');
                showToast('La fenêtre a été fermée automatiquement (délai dépassé). Vérifiez la commande avant de réessayer.', 'warning');
            }, 45000);
        });
        $('#approveModal').on('hidden.bs.modal', function () {
            clearTimeout(window.__approveModalSafety);
        });
    });

    function showToast(message, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }
    </script>
</body>
</html>