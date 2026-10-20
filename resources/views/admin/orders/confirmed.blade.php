<!DOCTYPE html>
<html lang="fr">
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
                            <h4>Ventes confirmées</h4>
                            <h6>Toutes les commandes approuvées et leurs ventes</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                                <i class="me-1 fas fa-clock"></i> Commandes en attente
                            </a>
                        </div>
                    </div>

                    @include('layouts.flash')

                    {{-- Filtres --}}
                    <div class="card mb-3">
                        <div class="card-body pb-0">
                            <form action="{{ route('admin.orders.confirmed') }}" method="GET">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <input type="text" name="order_id" value="{{ request('order_id') }}"
                                                placeholder="N° commande" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <input type="text" name="client" value="{{ request('client') }}"
                                                placeholder="Nom client" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <select name="payment" class="form-control">
                                                <option value="">Tous les statuts paiement</option>
                                                <option value="non payé"  {{ request('payment') == 'non payé'  ? 'selected' : '' }}>Non payé</option>
                                                <option value="partiel"   {{ request('payment') == 'partiel'   ? 'selected' : '' }}>Partiel</option>
                                                <option value="payé"      {{ request('payment') == 'payé'      ? 'selected' : '' }}>Payé</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group d-flex gap-2">
                                            <button type="submit" class="btn btn-filters">
                                                <img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img">
                                            </button>
                                            <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Tableau --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>#Cmd</th>
                                            <th>Client</th>
                                            <th>N° Facture</th>
                                            <th>Articles</th>
                                            <th>Total (GNF)</th>
                                            <th>Intérêt (GNF)</th>
                                            <th>Paiement</th>
                                            <th>Payé (GNF)</th>
                                            <th>Reste (GNF)</th>
                                            <th>Livraison</th>
                                            <th>Date confirmation</th>
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
                                            <td>
                                                @if($order->facture)
                                                    <span class="badge bg-light text-dark font-monospace">
                                                        {{ $order->facture->numero_facture }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @foreach($order->items as $item)
                                                    <span class="d-block" style="font-size:12px;">
                                                        {{ $item->product->libelle ?? '#'.$item->product_id }}
                                                        <em class="text-muted">× {{ $item->quantite_affichee }}</em>
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td>{{ number_format($order->total_amount, 0, ',', ' ') }}</td>
                                            <td class="text-info fw-semibold">
                                                {{ number_format($order->sales->sum('interet'), 0, ',', ' ') }}
                                            </td>
                                            <td>
                                                @if($order->facture)
                                                    @php
                                                        $ps = match($order->facture->statut) {
                                                            'payé'    => ['label' => 'Payé',     'class' => 'bg-success'],
                                                            'partiel' => ['label' => 'Partiel',  'class' => 'bg-warning'],
                                                            default   => ['label' => 'Non payé', 'class' => 'bg-danger'],
                                                        };
                                                    @endphp
                                                    <span class="badges {{ $ps['class'] }}">{{ $ps['label'] }}</span>
                                                @else
                                                    <span class="badges bg-secondary">—</span>
                                                @endif
                                            </td>
                                            <td class="text-success fw-semibold">
                                                {{ $order->facture ? number_format($order->facture->avance, 0, ',', ' ') : '—' }}
                                            </td>
                                            <td class="{{ ($order->facture && $order->facture->reste > 0) ? 'text-danger' : 'text-muted' }} fw-semibold">
                                                {{ $order->facture ? number_format($order->facture->reste, 0, ',', ' ') : '—' }}
                                            </td>
                                            <td>
                                                @if($order->facture)
                                                    @if($order->facture->livraison === 'livré')
                                                        <span class="badges bg-success">Livrée</span>
                                                    @else
                                                        <span class="badges bg-warning">En attente</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @if($order->facture)
                                                        {{-- Voir facture --}}
                                                        <a href="{{ route('factures.show', $order->facture->numero_facture) }}"
                                                           class="btn btn-info btn-sm" title="Voir facture">
                                                            <i class="fas fa-file-invoice"></i> Facture
                                                        </a>

                                                        {{-- Enregistrer paiement --}}
                                                        @if($order->facture->statut !== 'payé')
                                                        <a href="{{ route('payments.creation', $order->facture->id) }}"
                                                           class="btn btn-success btn-sm" title="Enregistrer paiement">
                                                            <i class="fas fa-money-bill-wave"></i> Paiement
                                                        </a>
                                                        @endif

                                                        {{-- Marquer comme livré --}}
                                                        @if($order->facture->livraison !== 'livré')
                                                        <button type="button" class="btn btn-warning btn-sm" 
                                                                onclick="openDeliveryModal('{{ $order->facture->id }}', '{{ $order->facture->numero_facture }}')"
                                                                title="Marquer comme livré">
                                                            <i class="fas fa-truck"></i> Livrer
                                                        </button>
                                                        @else
                                                        <span class="btn btn-sm btn-outline-success disabled" title="Déjà livrée">
                                                            <i class="fas fa-check"></i> Livré
                                                        </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                Aucune vente confirmée pour le moment.
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
        @include('layouts.scripts')
        @include('layouts.delete')

        <script>
            // Filet de sécurité : s'assure que le loader plein écran disparaît
            // même si le script global (script.js) échoue à s'exécuter.
            document.addEventListener('DOMContentLoaded', function () {
                var loader = document.getElementById('global-loader');
                if (loader) loader.style.display = 'none';
            });

            /**
             * fetch() natif n'a pas de timeout par défaut : si le serveur ne répond
             * jamais, la promesse reste en attente indéfiniment. On force une limite
             * via AbortController pour ne jamais bloquer l'interface silencieusement.
             */
            function fetchWithTimeout(url, options = {}, timeoutMs = 15000) {
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), timeoutMs);
                return fetch(url, { ...options, signal: controller.signal })
                    .finally(() => clearTimeout(timer));
            }

            function openDeliveryModal(factureId, invoiceNumber) {
                // Retour visuel immédiat : sans ça, un clic sur "Livrer" ne montre
                // strictement rien tant que la requête n'a pas répondu.
                Swal.fire({
                    title: 'Chargement…',
                    html: 'Récupération des articles de la commande.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                });

                // Récupérer les articles de la commande (données depuis le serveur)
                fetchWithTimeout(`/admin/orders/facture/${factureId}/items`, {}, 15000)
                    .then(response => response.json())
                    .then(items => {
                        let itemsHTML = `
                            <div class="delivery-form">
                                <p style="margin-bottom: 16px; color: #6b7280;">Indiquez la quantité livrée pour chaque article :</p>
                                <form id="partial-delivery-form">
                        `;
                        
                        items.forEach((item, index) => {
                            const remaining = item.quantity_remaining;
                            const alreadyDone = item.quantity_delivered || 0;
                            itemsHTML += `
                                <div class="delivery-item" style="margin-bottom: 14px; padding: 12px; background: #f9fafb; border-radius: 8px; border-left: 3px solid #3E4C21;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                        <strong style="font-size:13px;">${item.product_name}</strong>
                                        <span style="color: #9ca3af; font-size:12px;">Total commandé : ${item.quantity} ${item.unite || ''}</span>
                                    </div>
                                    <div style="font-size:12px; color:#6b7280; margin-bottom:8px;">
                                        Déjà livré : <strong style="color:#10b981;">${alreadyDone} ${item.unite || ''}</strong>
                                        &nbsp;|&nbsp; Reste à livrer : <strong style="color:#3E4C21;">${remaining} ${item.unite || ''}</strong>
                                    </div>
                                    ${remaining > 0 ? `
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <label style="font-size: 13px; color: #6b7280; min-width:110px;">Livrer maintenant :</label>
                                        <input type="number"
                                               name="quantities[${item.id}]"
                                               value="${remaining}"
                                               min="0"
                                               max="${remaining}"
                                               class="form-control"
                                               style="width: 80px; padding: 6px; border: 1px solid #e5e7eb; border-radius: 6px;">
                                        <span style="color: #9ca3af;">/ ${remaining}</span>
                                    </div>` : `
                                    <div style="font-size:12px; color:#10b981; font-weight:600;">
                                        <i class="fas fa-check-circle"></i> Entièrement livré
                                    </div>
                                    <input type="hidden" name="quantities[${item.id}]" value="0">`}
                                </div>
                            `;
                        });

                        itemsHTML += `
                                </form>
                            </div>
                        `;

                        Swal.fire({
                            title: 'Livraison (partielle ou complète)',
                            html: itemsHTML,
                            icon: 'info',
                            iconColor: '#3b82f6',
                            showCancelButton: true,
                            confirmButtonColor: '#3E4C21',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: '<i class="fas fa-check"></i> Confirmer la livraison',
                            cancelButtonText: 'Annuler',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-warning ms-2',
                                cancelButton: 'btn btn-secondary'
                            },
                            width: '500px',
                            didOpen: (modal) => {
                                modal.querySelector('.swal2-confirm').innerHTML = '<i class="fas fa-check me-2"></i>Confirmer la livraison';
                                modal.querySelector('.swal2-cancel').innerHTML = 'Annuler';
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Envoyer les données de livraison
                                const formData = new FormData(document.getElementById('partial-delivery-form'));

                                Swal.fire({
                                    title: 'Enregistrement…',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => Swal.showLoading(),
                                });

                                fetchWithTimeout(`/admin/orders/facture/${factureId}/deliver`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: formData
                                }, 30000)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Succès',
                                            text: 'Livraison enregistrée avec succès',
                                            icon: 'success',
                                            confirmButtonColor: '#10b981',
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire('Erreur', data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    var msg = error.name === 'AbortError'
                                        ? 'Le serveur met trop de temps à répondre. Vérifiez si la livraison a été enregistrée avant de réessayer.'
                                        : 'Une erreur est survenue.';
                                    Swal.fire('Erreur', msg, 'error');
                                    console.error(error);
                                });
                            }
                        });
                    })
                    .catch(error => {
                        var msg = error.name === 'AbortError'
                            ? 'Le serveur met trop de temps à répondre. Réessayez.'
                            : 'Impossible de charger les articles';
                        Swal.fire('Erreur', msg, 'error');
                        console.error(error);
                    });
            }
        </script>
    </body>
</html>
