<!DOCTYPE html>
<html lang="fr">
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
                            <h4>Factures E-commerce</h4>
                            <h6>Factures générées par les commandes validées</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-added">
                                <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2">
                            </a>
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
                                    <form action="{{ route('admin.orders.factures') }}" method="GET">
                                        <div class="row">
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="numero_facture" value="{{ request('numero_facture') }}" placeholder="Numéro facture" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="client" value="{{ request('client') }}" placeholder="Nom client" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="statut" class="form-control">
                                                        <option value="">Selectionner Statut</option>
                                                        <option value="payé"    {{ request('statut') == 'payé'    ? 'selected' : '' }}>Payé</option>
                                                        <option value="partiel" {{ request('statut') == 'partiel' ? 'selected' : '' }}>Partiel</option>
                                                        <option value="non payé"{{ request('statut') == 'non payé'? 'selected' : '' }}>Non Payé</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="livraison" class="form-control">
                                                        <option value="">Selectionner Livraison</option>
                                                        <option value="livré"               {{ request('livraison') == 'livré'               ? 'selected' : '' }}>Livré</option>
                                                        <option value="partiellement livré"  {{ request('livraison') == 'partiellement livré'  ? 'selected' : '' }}>Partiellement livré</option>
                                                        <option value="non livré"            {{ request('livraison') == 'non livré'            ? 'selected' : '' }}>Non livré</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2">
                                                        <img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img">
                                                    </button>
                                                    <a href="{{ route('admin.orders.factures') }}" class="btn btn-secondary">Annuler</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>No. Facture</th>
                                            <th>#Cmd</th>
                                            <th>Information du Client</th>
                                            <th>Boutique</th>
                                            <th>Quantité</th>
                                            <th>Montant</th>
                                            <th>Avance</th>
                                            <th>Reste</th>
                                            <th>Statut</th>
                                            <th>Livraison</th>
                                            <th>Note</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($factures as $facture)
                                        @php
                                            $order  = $facture->order;
                                            $client = $order?->user;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('factures.show', $facture->numero_facture) }}">
                                                    {{ $facture->numero_facture }}
                                                </a>
                                            </td>
                                            <td>
                                                @if($order)
                                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-bold">
                                                        #{{ $order->id }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $client?->name ?? ($facture->customer?->customerName ?? 'N/A') }}
                                                <span class="d-block text-muted" style="font-size:11px;">
                                                    {{ $client?->phone ?? $facture->customer?->tel ?? '' }}
                                                </span>
                                            </td>
                                            <td>{{ $facture->store?->store_name ?? '—' }}</td>
                                            <td>{{ $facture->quantite_resumee }}</td>
                                            <td>{{ number_format($facture->montant_total, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format($facture->avance, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format($facture->reste, 0, ',', ' ') }} GNF</td>
                                            <td>
                                                <span class="badge
                                                    @if($facture->statut === 'payé') bg-success
                                                    @elseif($facture->statut === 'partiel') bg-warning
                                                    @elseif($facture->statut === 'non payé') bg-danger
                                                    @endif">
                                                    {{ ucfirst($facture->statut) }}
                                                </span>
                                            </td>
                                            <td>{{ $facture->livraison }}</td>
                                            <td>{{ $facture->notes }}</td>
                                            <td>{{ $facture->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <a href="{{ route('factures.show', $facture->numero_facture) }}" class="me-2">
                                                    <img src="{{ asset('assets/img/icons/eye1.svg') }}" class="me-2" alt="img">
                                                </a>
                                                @if($facture->livraison !== 'livré')
                                                <a href="javascript:void(0);" class="me-2"
                                                   onclick="openDeliveryModal('{{ $facture->id }}', '{{ $facture->numero_facture }}')">
                                                    <img src="{{ asset('assets/img/icons/calendars.svg') }}" class="me-2" alt="img">
                                                </a>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a class="action-set" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="true">
                                                    <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a href="{{ route('payments.voir', $facture->id) }}" class="dropdown-item">
                                                            <img src="{{ asset('assets/img/icons/dollar-square.svg') }}" class="me-2" alt="img">Voir Paiements
                                                        </a>
                                                    </li>
                                                    @if($facture->statut !== 'payé')
                                                    <li>
                                                        <a href="{{ route('payments.creation', $facture->id) }}" class="dropdown-item">
                                                            <img src="{{ asset('assets/img/icons/plus-circle.svg') }}" class="me-2" alt="img">Ajouter Paiement
                                                        </a>
                                                    </li>
                                                    @endif
                                                </ul>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="14" class="text-center text-muted py-4">
                                                Aucune facture e-commerce trouvée.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $factures->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

        @include('layouts.scripts')

        <script>
            function openDeliveryModal(factureId, invoiceNumber) {
                fetch(`/admin/orders/facture/${factureId}/items`)
                    .then(r => r.json())
                    .then(items => {
                        let html = `<div><p style="margin-bottom:16px;color:#6b7280;">Indiquez la quantité livrée pour chaque article :</p><form id="delivery-form">`;
                        items.forEach(item => {
                            const remaining = item.quantity_remaining;
                            const alreadyDone = item.quantity_delivered || 0;
                            html += `
                                <div style="margin-bottom:12px;padding:12px;background:#f9fafb;border-radius:8px;border-left:3px solid #3E4C21;">
                                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                        <strong style="font-size:13px;">${item.product_name}</strong>
                                        <span style="color:#9ca3af;font-size:12px;">Total commandé : ${item.quantity} ${item.unite || ''}</span>
                                    </div>
                                    <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">
                                        Déjà livré : <strong style="color:#10b981;">${alreadyDone} ${item.unite || ''}</strong>
                                        &nbsp;|&nbsp; Reste à livrer : <strong style="color:#3E4C21;">${remaining} ${item.unite || ''}</strong>
                                    </div>
                                    ${remaining > 0 ? `
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <label style="font-size:13px;color:#6b7280;min-width:110px;">Livrer maintenant :</label>
                                        <input type="number" name="quantities[${item.id}]"
                                               value="${remaining}" min="0" max="${remaining}"
                                               class="form-control" style="width:80px;">
                                        <span style="color:#9ca3af;">/ ${remaining}</span>
                                    </div>` : `
                                    <div style="font-size:12px;color:#10b981;font-weight:600;">
                                        <i class="fas fa-check-circle"></i> Entièrement livré
                                    </div>
                                    <input type="hidden" name="quantities[${item.id}]" value="0">`}
                                </div>`;
                        });
                        html += `</form></div>`;

                        Swal.fire({
                            title: 'Livraison — ' + invoiceNumber,
                            html: html,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonColor: '#3E4C21',
                            cancelButtonColor: '#6b7280',
                            buttonsStyling: false,
                            customClass: { confirmButton: 'btn btn-warning ms-2', cancelButton: 'btn btn-secondary' },
                            width: '500px',
                        }).then(result => {
                            if (!result.isConfirmed) return;
                            const formData = new FormData(document.getElementById('delivery-form'));
                            fetch(`/admin/orders/facture/${factureId}/deliver`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: formData
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({ title: 'Succès', text: 'Livraison enregistrée.', icon: 'success', confirmButtonColor: '#10b981' })
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire('Erreur', data.message || 'Erreur inconnue.', 'error');
                                }
                            })
                            .catch(() => Swal.fire('Erreur', 'Une erreur est survenue.', 'error'));
                        });
                    })
                    .catch(() => Swal.fire('Erreur', 'Impossible de charger les articles.', 'error'));
            }
        </script>
    </body>
</html>
