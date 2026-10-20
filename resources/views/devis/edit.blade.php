{{-- resources/views/devis/edit.blade.php --}}
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
                        <h4>Modification Devis</h4>
                        <h6>{{ $devis->numero_devis }}</h6>
                    </div>
                    <div class="page-btn">
                        <a href="{{ route('devis.show', $devis->id) }}" class="btn btn-cancel">
                            <i class="fa fa-arrow-left me-2"></i>Retour
                        </a>
                    </div>
                </div>

                @include('layouts.flash')

                @if($devis->status != 'draft')
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    Ce devis n'est plus en brouillon (statut: {{ $devis->status }}). La modification n'est pas recommandée.
                </div>
                @endif

                <form action="{{ route('devis.update', $devis->id) }}" method="POST" id="devisForm">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-body">
                            <!-- Informations générales -->
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Boutique <span class="text-danger">*</span></label>
                                        <select name="store_id" class="form-control @error('store_id') is-invalid @enderror" required>
                                            <option value="">Sélectionner une boutique</option>
                                            @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ old('store_id', $devis->store_id) == $store->id ? 'selected' : '' }}>
                                                {{ $store->store_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('store_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Client <span class="text-danger">*</span></label>
                                        <select name="customer_id" class="form-control @error('customer_id') is-invalid @enderror" required>
                                            <option value="">Sélectionner un client</option>
                                            @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $devis->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->customerName }} - {{ $customer->mark }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('customer_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Valable jusqu'au</label>
                                        <input type="date" name="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                                               value="{{ old('valid_until', $devis->valid_until ? $devis->valid_until->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}">
                                        @error('valid_until')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                                  rows="2">{{ old('notes', $devis->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Produits -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Produits</h5>
                            <button type="button" class="btn btn-primary" id="addProductBtn">
                                <i class="fa fa-plus me-2"></i>Ajouter un produit
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%">Produit</th>
                                            <th style="width: 15%">Quantité</th>
                                            <th style="width: 20%">Prix unitaire</th>
                                            <th style="width: 20%">Total</th>
                                            <th style="width: 5%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsContainer">
                                        <!-- Les lignes de produits seront chargées ici -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                                            <td><strong id="totalAmount">{{ number_format($devis->total_amount, 0, ',', ' ') }} FG</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="text-end mb-4">
                        <a href="{{ route('devis.show', $devis->id) }}" class="btn btn-cancel me-2">
                            <i class="fa fa-times me-2"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fa fa-save me-2"></i>Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('layouts.scripts')

    <script>
    $(document).ready(function() {
        // Configuration
        // Propriétaire de la marchandise de chaque ligne : fonds propres ou fonds d'un associé.
        const fundsList = @json($funds);
        function fundSelect(index, selected) {
            if (!fundsList.length) return '';
            let opts = '<option value="">Fonds propres</option>';
            fundsList.forEach(f => { opts += '<option value="' + f.id + '"' + (String(f.id) === String(selected) ? ' selected' : '') + '>' + f.name + '</option>'; });
            return '<select name="products[' + index + '][fund_id]" class="form-control form-control-sm mt-1" title="Propriétaire de la marchandise">' + opts + '</select>';
        }
        let productIndex = {{ $devis->lines->count() }};
        const products = @json($products);
        // Unité unique du produit : la quantité et le prix du devis sont dans
        // cette unité (« en cartons »).
        $(document).on('change', '.product-select', function () {
            const unite = $(this).find(':selected').data('unite');
            $(this).closest('tr').find('.unite-hint').text(unite ? 'en ' + unite : '');
        });
        const existingLines = @json($devis->lines);

        // Initialiser Select2
        $('.select2').select2({ width: '100%' });

        // Charger les lignes existantes
        existingLines.forEach(function(line, index) {
            addProductRow(line, index);
        });

        // Ajouter un produit
        $('#addProductBtn').click(function() {
            addProductRow(null, productIndex++);
        });

        // Fonction pour ajouter une ligne de produit
        function addProductRow(lineData, index) {
            const rowId = 'product_row_' + index;

            let html = `
                <tr id="${rowId}" class="product-row">
                    <td>
                        <select name="products[${index}][product_id]" class="form-control product-select" required onchange="updateProductDetails(this, ${index})">
                            <option value="">Sélectionner un produit</option>
            `;

            products.forEach(product => {
                const selected = (lineData && lineData.product_id == product.id) ? 'selected' : '';
                html += `<option value="${product.id}" data-price="${product.price_sale ?? product.price}" data-unite="${product.unite_pluriel}" data-plancher="${product.plancher}" data-plancher-origine="${product.plancher_origine}" ${selected}>${product.libelle} — ${product.unite_libelle}</option>`;
            });

            html += `
                        </select>
                    ${fundSelect(index, lineData ? lineData.fund_id : null)}
                    </td>
                    <td>
                        <input type="number" name="products[${index}][quantity]" class="form-control quantity-input"
                               min="1" value="${lineData ? lineData.quantity : 1}" required onchange="updateLineTotal(this, ${index})">
                        <small class="text-muted unite-hint">${lineData ? 'en ' + (products.find(p => p.id == lineData.product_id)?.unite_pluriel || '') : ''}</small>
                    </td>
                    <td>
                        <input type="number" name="products[${index}][unit_price]" class="form-control price-input"
                               step="0.01" min="0" value="${lineData ? lineData.unit_price : ''}" required onchange="updateLineTotal(this, ${index})">
                    </td>
                    <td class="line-total">${lineData ? (lineData.quantity * lineData.unit_price).toLocaleString() + ' FG' : '0 FG'}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeProductRow('${rowId}')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#productsContainer').append(html);

            // Initialiser Select2
            $(`#${rowId} .product-select`).select2({ width: '100%' });

            updateGrandTotal();
        }

        // Mettre à jour le total de la ligne
        // Plancher de vente (prix minimum de la fiche ou prix de revient) :
            // même règle que le serveur, qui refuse de toute façon l'enregistrement.
            function signalerPlancher(input, plancher, origine) {
                if (!input) return;
                let aide = input.parentElement.querySelector('.plancher-aide');
                if (!aide) {
                    aide = document.createElement('small');
                    aide.className = 'plancher-aide d-block';
                    input.insertAdjacentElement('afterend', aide);
                }
                const prix = parseFloat(input.value) || 0;
                const trop = plancher > 0 && prix > 0 && prix < plancher;
                input.classList.toggle('is-invalid', trop);
                aide.style.color = trop ? '#d92d20' : '#66695A';
                aide.textContent = plancher > 0
                    ? (trop ? 'Trop bas : minimum ' : 'Minimum : ') + Number(plancher).toLocaleString('fr-FR') + ' GNF (' + origine + ')'
                    : '';
            }

        window.updateLineTotal = function(element, index) {
            const row = $(element).closest('tr');
            const choix = row.find('.product-select option:selected');
            signalerPlancher(row.find('.price-input')[0], parseFloat(choix.data('plancher')) || 0, choix.data('plancher-origine') || '');
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const price = parseFloat(row.find('.price-input').val()) || 0;
            const total = quantity * price;

            row.find('.line-total').text(total.toLocaleString() + ' FG');
            updateGrandTotal();
        };

        // Mettre à jour le prix quand un produit est sélectionné
        window.updateProductDetails = function(select, index) {
            const selectedOption = $(select).find(':selected');
            const price = selectedOption.data('price') || 0;
            const row = $(select).closest('tr');

            row.find('.price-input').val(price);
            updateLineTotal(select, index);
        };

        // Supprimer une ligne
        window.removeProductRow = function(rowId) {
            if (confirm('Supprimer cette ligne ?')) {
                $('#' + rowId).remove();
                updateGrandTotal();
            }
        };

        // Mettre à jour le total général
        function updateGrandTotal() {
            let total = 0;
            $('.product-row').each(function() {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                total += quantity * price;
            });

            $('#totalAmount').text(total.toLocaleString() + ' FG');

            if ($('#totalAmountInput').length) {
                $('#totalAmountInput').val(total);
            } else {
                $('#devisForm').append(`<input type="hidden" name="total_amount" id="totalAmountInput" value="${total}">`);
            }
        }

        // Validation
        $('#devisForm').submit(function(e) {
            if ($('.product-row').length === 0) {
                e.preventDefault();
                alert('Veuillez ajouter au moins un produit.');
                return false;
            }
            return true;
        });
    });
    </script>

    <style>
    .product-row td { vertical-align: middle; }
    .line-total { font-weight: 600; text-align: right; }
    #productsTable tfoot td { font-size: 16px; }
    </style>
</body>
</html>