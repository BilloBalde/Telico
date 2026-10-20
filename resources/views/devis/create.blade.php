{{-- resources/views/devis/create.blade.php --}}
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
                        <h4>Création Devis</h4>
                        <h6>Ajouter un nouveau devis</h6>
                    </div>
                </div>

                @include('layouts.flash')

                <form action="{{ route('devis.store') }}" method="POST" id="devisForm">
                    @csrf

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
                                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }} {{ isset($userStoreId) && $userStoreId == $store->id ? 'selected' : '' }}>
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
                                        <div class="input-group">
                                            <select name="customer_id" id="customerSelect" class="form-control @error('customer_id') is-invalid @enderror" required>
                                                <option value="">Sélectionner un client</option>
                                                @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->customerName }} - {{ $customer->mark }}
                                                </option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-primary" id="addCustomerBtn">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        @error('customer_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Valable jusqu'au</label>
                                        <input type="date" name="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                                               value="{{ old('valid_until', now()->addDays(30)->format('Y-m-d')) }}">
                                        @error('valid_until')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                                  rows="2">{{ old('notes') }}</textarea>
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
                                        <!-- Les lignes de produits seront ajoutées ici dynamiquement -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                                            <td><strong id="totalAmount">0 FG</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="alert alert-info mt-3" id="noProductsAlert" style="display: none;">
                                <i class="fa fa-info-circle me-2"></i> Cliquez sur "Ajouter un produit" pour commencer.
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="text-end mb-4">
                        <a href="{{ route('devis.index') }}" class="btn btn-cancel me-2">
                            <i class="fa fa-times me-2"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fa fa-save me-2"></i>Enregistrer le devis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Client -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Ajouter un nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="newCustomerForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label>Nom complet <span class="text-danger">*</span></label>
                            <input type="text" name="customerName" id="customerName" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Marque / Enseigne</label>
                            <input type="text" name="mark" id="mark" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label>Téléphone</label>
                            <input type="text" name="tel" id="tel" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label>Adresse</label>
                            <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="saveCustomerBtn">Enregistrer</button>
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
        let productIndex = 0;
        const products = @json($products);
        // Unité unique du produit : la quantité et le prix du devis sont dans
        // cette unité (« en cartons »).
        $(document).on('change', '.product-select', function () {
            const unite = $(this).find(':selected').data('unite');
            $(this).closest('tr').find('.unite-hint').text(unite ? 'en ' + unite : '');
        });

        // Initialiser Select2 pour les sélections de produits
        $('.select2').select2({
            width: '100%'
        });

        // =============== GESTION DU CLIENT ===============
        // Ouvrir le modal d'ajout de client
        $('#addCustomerBtn').click(function() {
            $('#newCustomerForm')[0].reset();
            $('#addCustomerModal').modal('show');
        });

        // Soumettre le formulaire de nouveau client
        $('#newCustomerForm').submit(function(e) {
            e.preventDefault();

            // Récupérer les données du formulaire
            let formData = {
                customerName: $('#customerName').val(),
                mark: $('#mark').val(),
                tel: $('#tel').val(),
                email: $('#email').val(),
                address: $('#address').val(),
                _token: '{{ csrf_token() }}'
            };

            // Validation basique
            if (!formData.customerName) {
                alert('Le nom du client est requis');
                return;
            }

            // Désactiver le bouton pendant l'envoi
            $('#saveCustomerBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enregistrement...');

            // Envoyer la requête AJAX
            $.ajax({
                url: '{{ route("customers.quick-add") }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Ajouter le nouveau client au select
                        const newOption = new Option(
                            response.customer.customerName + ' - ' + (response.customer.mark || ''),
                            response.customer.id,
                            true,
                            true
                        );
                        $('#customerSelect').append(newOption).trigger('change');

                        // Fermer le modal
                        $('#addCustomerModal').modal('hide');

                        // Afficher un message de succès
                        showModal('Client ajouté avec succès');
                    } else {
                        alert('Erreur: ' + response.message);
                    }
                },
                error: function(xhr) {
                    let message = 'Erreur lors de l\'ajout du client';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);
                },
                complete: function() {
                    $('#saveCustomerBtn').prop('disabled', false).html('Enregistrer');
                }
            });
        });

        // =============== GESTION DES PRODUITS ===============
        // Ajouter un produit
        $('#addProductBtn').click(function() {
            addProductRow();
            $('#noProductsAlert').hide();
        });

        // Fonction pour ajouter une ligne de produit
        function addProductRow(productData = null) {
            const index = productIndex++;
            const rowId = 'product_row_' + index;

            let html = `
                <tr id="${rowId}" class="product-row">
                    <td>
                        <select name="products[${index}][product_id]" class="form-control product-select" required onchange="updateProductDetails(this, ${index})">
                            <option value="">Sélectionner un produit</option>
            `;

            // Ajouter les options de produits
            products.forEach(product => {
                const selected = (productData && productData.product_id == product.id) ? 'selected' : '';
                html += `<option value="${product.id}" data-price="${product.price ?? product.price_sale}" data-unite="${product.unite_pluriel}" data-plancher="${product.plancher}" data-plancher-origine="${product.plancher_origine}" ${selected}>${product.libelle} — ${product.unite_libelle}</option>`;
            });

            html += `
                        </select>
                    ${fundSelect(index, productData ? productData.fund_id : null)}
                    </td>
                    <td>
                        <input type="number" name="products[${index}][quantity]" class="form-control quantity-input"
                               min="1" value="${productData ? productData.quantity : 1}" required onchange="updateLineTotal(this, ${index})">
                        <small class="text-muted unite-hint">${productData ? 'en ' + (products.find(p => p.id == productData.product_id)?.unite_pluriel || '') : ''}</small>
                    </td>
                    <td>
                        <input type="number" name="products[${index}][unit_price]" class="form-control price-input"
                               step="0.01" min="0" value="${productData ? productData.unit_price : ''}" required onchange="updateLineTotal(this, ${index})">
                    </td>
                    <td class="line-total">${productData ? (productData.quantity * productData.unit_price).toLocaleString() + ' FG' : '0 FG'}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeProductRow('${rowId}')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#productsContainer').append(html);

            // Initialiser Select2 pour la nouvelle ligne
            $(`#${rowId} .product-select`).select2({
                width: '100%'
            });

            // Si on a des données, mettre à jour les champs
            if (productData) {
                $(`#${rowId} .price-input`).val(productData.unit_price);
                updateLineTotal($(`#${rowId} .quantity-input`)[0], index);
            }

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

        // Supprimer une ligne de produit
        window.removeProductRow = function(rowId) {
            $('#' + rowId).remove();
            updateGrandTotal();

            if ($('#productsContainer tr').length === 0) {
                $('#noProductsAlert').show();
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

            // Ajouter un champ caché pour le total
            if ($('#totalAmountInput').length) {
                $('#totalAmountInput').val(total);
            } else {
                $('#devisForm').append(`<input type="hidden" name="total_amount" id="totalAmountInput" value="${total}">`);
            }
        }

        // Validation du formulaire avant soumission
        $('#devisForm').submit(function(e) {
            const productCount = $('.product-row').length;

            if (productCount === 0) {
                e.preventDefault();
                alert('Veuillez ajouter au moins un produit au devis.');
                return false;
            }

            // Vérifier que tous les champs requis sont remplis
            let valid = true;
            $('.product-row').each(function() {
                const productId = $(this).find('.product-select').val();
                const quantity = $(this).find('.quantity-input').val();
                const price = $(this).find('.price-input').val();

                if (!productId || !quantity || quantity <= 0 || !price || price <= 0) {
                    valid = false;
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Veuillez remplir correctement toutes les lignes de produits.');
                return false;
            }

            return true;
        });

        // Ajouter une première ligne par défaut
        addProductRow();

        // Fonction pour afficher un modal de message
        function showModal(message) {
            // Si vous avez un modal d'alerte existant
            if ($('#alertModalImage').length) {
                $('#modalMessage').text(message);
                $('#alertModalImage').modal('show');
            } else {
                alert(message);
            }
        }
    });
    </script>

    <style>
    .product-row td {
        vertical-align: middle;
    }
    .line-total {
        font-weight: 600;
        text-align: right;
    }
    #productsTable tfoot td {
        font-size: 16px;
    }
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .input-group {
        display: flex;
        width: 100%;
    }
    .input-group select {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-group button {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    </style>
</body>
</html>