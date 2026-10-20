{{-- resources/views/devis/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<head>
    <style>
        .devis-status-badge {
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 30px;
            display: inline-block;
        }
        .status-draft { background: #6c757d; color: white; }
        .status-sent { background: #17a2b8; color: white; }
        .status-accepted { background: #28a745; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .status-expired { background: #ffc107; color: #212529; }
        .status-validated { background: #28a745; color: white; }
        
        .info-group {
            margin-bottom: 15px;
        }
        .info-group .label {
            font-weight: 600;
            color: #666;
            font-size: 13px;
            margin-bottom: 3px;
        }
        .info-group .value {
            font-size: 15px;
            font-weight: 500;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 5px;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        .products-table th {
            background: #f8f9fa;
            padding: 12px 8px;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .products-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e9ecef;
        }
        .products-table tfoot td {
            font-weight: 600;
            border-top: 2px solid #dee2e6;
            background: #f8f9fa;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
</head>
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
                        <h4>Détails du Devis</h4>
                        <h6>{{ $devis->numero_devis }}</h6>
                    </div>
                    <div class="page-btn" style="display: flex; gap: 10px; align-items: center;">
                        <a href="{{ route('devis.pdf', $devis->id) }}" class="btn btn-added" target="_blank">
                            <i class="fa fa-file-pdf me-2"></i>PDF
                        </a>
                        <a href="{{ route('devis.index') }}" class="btn btn-cancel">
                            <i class="fa fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>

                @include('layouts.flash')

                <!-- Statut et actions rapides -->
                <div class="card mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="devis-status-badge status-{{ $devis->status }}">
                                @php
                                    $statusLabels = [
                                        'draft' => 'Brouillon',
                                        'sent' => 'Envoyé',
                                        'accepted' => 'Accepté',
                                        'rejected' => 'Rejeté',
                                        'expired' => 'Expiré',
                                        'validated' => 'Validé'
                                    ];
                                @endphp
                                {{ $statusLabels[$devis->status] ?? $devis->status }}
                            </span>
                            @if($devis->validated_at)
                                <span class="ms-3 text-muted">
                                    Validé le {{ $devis->validated_at->format('d/m/Y H:i') }} par {{ $devis->validatedBy?->name ?? 'N/A' }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="action-buttons">
                            @if($devis->status == 'draft')
                                <!-- <button class="btn btn-info" onclick="changeStatus({{ $devis->id }}, 'sent')">
                                    <i class="fa fa-paper-plane me-2"></i>Marquer envoyé
                                </button> -->
                                <a href="{{ route('devis.edit', $devis->id) }}" class="btn btn-primary">
                                    <i class="fa fa-edit me-2"></i>Modifier
                                </a>
                            @endif
                            
                            @if($devis->status == 'sent')
                                <button class="btn btn-success" onclick="changeStatus({{ $devis->id }}, 'accepted')">
                                    <i class="fa fa-check-circle me-2"></i>Accepter
                                </button>
                                <button class="btn btn-danger" onclick="changeStatus({{ $devis->id }}, 'rejected')">
                                    <i class="fa fa-times-circle me-2"></i>Rejeter
                                </button>
                            @endif
                            
                            @if($devis->status == 'accepted' && !$devis->validated_at)
                                <button class="btn btn-success" onclick="validateDevis({{ $devis->id }})">
                                    <i class="fa fa-check-double me-2"></i>Valider et créer facture
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Informations générales -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Informations générales</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-group">
                                    <div class="label">N° Devis</div>
                                    <div class="value">{{ $devis->numero_devis }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-group">
                                    <div class="label">Date de création</div>
                                    <div class="value">{{ $devis->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-group">
                                    <div class="label">Valable jusqu'au</div>
                                    <div class="value">{{ $devis->valid_until ? $devis->valid_until->format('d/m/Y') : 'Non spécifié' }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-group">
                                    <div class="label">Créé par</div>
                                    <div class="value">{{ $devis->createdBy?->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client et boutique -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5>Client</h5>
                            </div>
                            <div class="card-body">
                                @if($devis->customer)
                                    <div class="info-group">
                                        <div class="label">Nom</div>
                                        <div class="value">{{ $devis->customer->customerName }}</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Marque</div>
                                        <div class="value">{{ $devis->customer->mark }}</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Téléphone</div>
                                        <div class="value">{{ $devis->customer->tel ?? 'Non renseigné' }}</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Adresse</div>
                                        <div class="value">{{ $devis->customer->address ?? 'Non renseignée' }}</div>
                                    </div>
                                @else
                                    <p class="text-muted">Client non trouvé</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5>Boutique</h5>
                            </div>
                            <div class="card-body">
                                @if($devis->store)
                                    <div class="info-group">
                                        <div class="label">Nom</div>
                                        <div class="value">{{ $devis->store->store_name }}</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Description</div>
                                        <div class="value">{{ $devis->store->description ?? 'Non renseignée' }}</div>
                                    </div>
                                    <div class="info-group">
                                        <div class="label">Lieu</div>
                                        <div class="value">{{ $devis->store->place?->placeName ?? 'Non renseigné' }}</div>
                                    </div>
                                @else
                                    <p class="text-muted">Boutique non trouvée</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Produits -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5>Produits</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">N°</th>
                                        <th style="width: 50%">Produit</th>
                                        <th style="width: 10%">Quantité</th>
                                        <th style="width: 15%">Prix unitaire</th>
                                        <th style="width: 20%">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($devis->lines as $index => $line)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $line->product->libelle ?? 'Produit supprimé' }}</strong>
                                            @if($line->product && $line->product->sku)
                                                <br><small class="text-muted">SKU: {{ $line->product->sku }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $line->quantite_affichee }}</td>
                                        <td class="text-right">{{ number_format($line->unit_price, 0, ',', ' ') }} FG</td>
                                        <td class="text-right"><strong>{{ number_format($line->total_price, 0, ',', ' ') }} FG</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                                        <td class="text-right"><strong>{{ number_format($devis->total_amount, 0, ',', ' ') }} FG</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                @if($devis->notes)
                <div class="card">
                    <div class="card-header">
                        <h5>Notes</h5>
                    </div>
                    <div class="card-body">
                        <p>{{ $devis->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Formulaires cachés -->
    <form id="statusForm" method="POST" style="display: none;">
        @csrf
    </form>
    
    <form id="validateForm" method="POST" style="display: none;">
        @csrf
    </form>

    @include('layouts.scripts')
    
    <script>
    function changeStatus(devisId, status) {
        if (confirm('Êtes-vous sûr de vouloir changer le statut de ce devis ?')) {
            const form = document.getElementById('statusForm');
            form.action = '{{ url("devis") }}/' + devisId + '/status';
            
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'status';
            input.value = status;
            form.appendChild(input);
            
            form.submit();
        }
    }

    function validateDevis(devisId) {
        if (confirm('La validation créera une facture et affectera le stock. Continuer ?')) {
            const form = document.getElementById('validateForm');
            form.action = '{{ url("devis") }}/' + devisId + '/validate';
            form.submit();
        }
    }
    </script>
</body>
</html>