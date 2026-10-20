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
                            <h4>Commandes à valider</h4>
                            <h6>Gérer les commandes en attente</h6>
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
                                    <form action="{{ route('admin.orders.index') }}" method="GET">
                                        <div class="row">
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="N° commande" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Nom client" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Reset</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            {{-- Siège par défaut ; une commande attribuée n'est visible que de sa boutique. --}}
                            @unless (auth()->user()->isStoreScoped())
                            <div class="d-flex flex-wrap gap-2 mb-3" style="font-size:13px;">
                                @foreach (['' => 'Toutes', 'siege' => 'Traitées au siège', 'boutiques' => 'Attribuées à une boutique'] as $valeur => $libelle)
                                    <a href="{{ route('admin.orders.index', array_filter(['attribution' => $valeur])) }}"
                                       class="btn btn-sm {{ (string) request('attribution') === $valeur ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $libelle }}</a>
                                @endforeach
                            </div>
                            @endunless
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>#ID</th>
                                            <th>Client</th>
                                            <th>Articles</th>
                                            <th>Total (GNF)</th>
                                            <th>Paiement</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Traitée par</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr>
                                            <td><strong>#{{ $order->id }}</strong></td>
                                            <td>
                                                {{ $order->user->name ?? 'N/A' }}
                                                <span class="d-block text-muted" style="font-size:11px;">
                                                    {{ $order->user->phone ?? '' }}
                                                </span>
                                            </td>
                                            <td style="font-size:12px;">
                                                @foreach($order->items as $item)
                                                    <span class="d-block">
                                                        {{ $item->product?->libelle ?? '#'.$item->product_id }}
                                                        <em class="text-muted">× {{ $item->quantite_affichee }}</em>
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td>{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</td>
                                            <td>{{ strtoupper($order->payment_method) }}</td>
                                            <td>
                                                @if($order->status == 'pending')
                                                    <span class="badges bg-warning">En attente</span>
                                                @elseif($order->status == 'approved')
                                                    <span class="badges bg-success">Approuvée</span>
                                                @elseif($order->status == 'rejected')
                                                    <span class="badges bg-danger">Rejetée</span>
                                                @else
                                                    <span class="badges bg-secondary">{{ $order->status }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td style="min-width:190px;">
                                                @if ($sections->isNotEmpty() && $order->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.orders.assign', $order) }}" class="m-0">
                                                        @csrf
                                                        <select name="store_id" class="form-control form-control-sm" onchange="this.form.submit()">
                                                            <option value="">Siège (traitement central)</option>
                                                            @foreach ($sections as $section)
                                                                @php $complet = $couverture[$order->id][$section->id] ?? false; @endphp
                                                                <option value="{{ $section->id }}" {{ (int) $order->store_id === $section->id ? 'selected' : '' }}>
                                                                    {{ $section->store_name }} {{ $complet ? '✓ stock complet' : '— stock incomplet' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                    @if ($order->assigned_at)
                                                        <small class="text-muted d-block" style="font-size:11px;">
                                                            le {{ $order->assigned_at->format('d/m H:i') }}{{ $order->assignedBy ? ' par '.$order->assignedBy->name : '' }}
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="badge {{ $order->store ? 'bg-info' : 'bg-secondary' }}">{{ $order->store?->store_name ?? 'Siège' }}</span>
                                                    @if ($order->store_id && $order->status === 'pending' && auth()->user()->isStoreScoped())
                                                        <form method="POST" action="{{ route('admin.orders.release', $order) }}" class="mt-1"
                                                              onsubmit="var r = prompt('Motif (facultatif) : pourquoi rendre la commande au siège ?'); if (r === null) return false; this.reason.value = r;">
                                                            @csrf
                                                            <input type="hidden" name="reason">
                                                            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo me-1"></i>Rendre au siège</button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @if($order->status === 'pending' && auth()->user()->hasPermission('orders.validate'))
                                                        {{-- Bouton Approuver → ouvre modal boutique --}}
                                                        <button type="button"
                                                            class="btn btn-success btn-sm btn-approve"
                                                            data-order-id="{{ $order->id }}"
                                                            data-order-label="#{{ $order->id }} — {{ $order->user?->name ?? 'Client' }}"
                                                            data-approve-url="{{ route('admin.orders.approve', $order) }}"
                                                            data-check-url="{{ route('admin.orders.stock.check', $order) }}">
                                                            <i class="fas fa-check me-1"></i> Approuver
                                                        </button>
                                                        <button 
                                                            type="button" 
                                                            class="btn btn-danger btn-sm btn-reject"
                                                            data-url="{{ route('admin.orders.reject', $order) }}">
                                                            <i class="fas fa-times me-1"></i> Rejeter
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye me-1"></i> Voir
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                Aucune commande en attente.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $orders->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Modal approbation boutique ══ --}}
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

                        {{-- Un emplacement de stock par produit : c'est l'endroit d'où
                             chaque article sera retiré (magasin, stock interne, entrepôt). --}}
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

                        {{-- Magasin qui facture et encaisse : automatique d'après les lignes. --}}
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

         // Gestion du rejet avec SweetAlert
$(document).on('click', '.btn-reject', function() {
    const url = $(this).data('url');

    Swal.fire({
        title: 'Rejeter la commande',
        text: 'Êtes-vous sûr ? Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, rejeter'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                method: 'POST',
                timeout: 20000, // évite un blocage indéfini si le serveur ne répond pas
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    window.location.reload();
                },
                error: function(xhr, status) {
                    console.log(xhr.responseText);
                    var msg = status === 'timeout'
                        ? 'Le serveur met trop de temps à répondre. Réessayez.'
                        : 'Impossible de rejeter la commande';
                    Swal.fire('Erreur', msg, 'error');
                }
            });
        }
    });
});
        $(function () {
            var csrfToken       = $('meta[name="csrf-token"]').attr('content');
            var currentCheckUrl = '';
            var currentApproveUrl = '';
            var stockOk = false;

            // Ouvrir le modal (Bootstrap 4)
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
            // Fermeture manuelle de la modale
            $('#approveModal .close, #approveModal button[data-bs-dismiss="modal"]').on('click', function(e) {
                e.preventDefault();
                $('#approveModal').modal('hide');
            });

            $(document).on('change', '#stockCheckList .item-location', function () {
                stockOk = window.refreshOrderLines();
                $('#confirmApproveBtn').prop('disabled', !stockOk);
            });

            // Confirmer → POST via $.ajax puis redirect
            $('#confirmApproveBtn').on('click', function () {
                if (!stockOk) return;

                $('#confirmApproveBtn').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm mr-1"></span> Traitement…');

                $.ajax({
                    url:    currentApproveUrl,
                    method: 'POST',
                    timeout: 30000, // évite un blocage indéfini de la modale si le serveur traîne
                    data:   { _token: csrfToken, store_id: $('#storeSelect').val(), locations: window.collectOrderLocations() },
                    success: function () {
                        window.location.reload();
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

            // Échappatoire : si la modale reste ouverte plus de 45s (requête bloquée
            // côté serveur sans jamais répondre, même sans timeout jQuery atteint),
            // on force sa fermeture pour ne pas immobiliser toute la page.
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
        $(window).on('load', function () {
            $('#global-loader').fadeOut('slow');
        });
        </script>

    </body>
</html>
