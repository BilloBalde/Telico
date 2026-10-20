<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<body>
    <div id="global-loader">
        <div class="whirly-loader"> </div>
    </div>

    <style>
.suggestions-list li:hover {
    background-color: #f5f5f5;
}

.suggestions-list li:last-child {
    border-bottom: none;
}
</style>

    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header">
                    <div class="page-title">
                        <h4>Facturation</h4>
                    </div>
                    <div class="page-btn">
                        <a href="{{ url()->previous() }}" class="btn btn-added" id="leave-page-btn">
                            <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2">
                        </a>
                    </div>

                    <!-- Confirmation Modal -->
                    <div class="modal fade" id="leaveConfirmModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Navigation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Voulez-vous quitter cette page?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="button" class="btn btn-danger" id="confirm-leave">Quitter</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                @include('layouts.flash')
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('facturations.store') }}" id="commandForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <input type="hidden" name="numeroFacture" value="{{ $numeroFacture }}"/>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="customer_id">Client</label>
                                        <div class="row">
                                            <div class="col-lg-10 col-sm-10 col-10">
                                                <select name="customer_id" id="customer_id" class="form-control">
                                                    <option value="#">Selectionner le client</option>
                                                    @foreach ($customers as $item)
                                                        <option value="{{ old('customer_id', $item->id) }}" {{ old('customer_id') == $item->id ? 'selected' : '' }}>{{ $item->customerName.' '.$item->mark }}</option>
                                                    @endforeach
                                                </select>
                                                @error('customer_id')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-lg-2 col-sm-2 col-2 ps-0">
                                                <div class="add-icon">
                                                    <a href="javascript:void(0);" id="addCustomer"><img src="{{ asset('assets/img/icons/plus1.svg') }}" alt="img"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="date_facturation">Date Facturation</label>
                                        <input type="date" class="form-control" id="date_facturation" name="date_facturation" placeholder="Date date_facturation" value="{{ old('date_facturation') }}">
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="store_id">Magasin</label>
                                        <select name="store_id" id="store_id" class="form-control">
                                            <option value="">Sélectionner Magasin</option>
                                            @if ($userStoreId)
                                                <option value="{{ $userStoreId }}" selected>{{ App\Models\Store::find($userStoreId)->store_name }}</option>
                                            @else
                                            @foreach ($boutiques as $item)
                                                <option value="{{ $item->id }}">{{ $item->store_name }}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="avance">Avance</label>
                                        <input type="text" class="form-control" id="avance" name="avance" placeholder="Frais Divers" value="{{ old('avance', 0) }}">
                                        @error('avance')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Table for adding products dynamically -->
                            <div class="row">
                                <div class="mb-3 table-responsive">
                                    <table class="table" id="orderTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Produit</th>
                                                <th>Image</th>
                                                <th>Quantité</th>
                                                <th>Unité</th>
                                                <th>Prix par unité</th>
                                                <th>Sous-total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dynamic Rows Will Be Added Here -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-right"><strong>Total</strong></th>
                                                <th id="totalPcs" style="font-weight:bold;">0</th>
                                                <input type="hidden" name="total_pcs" id="total_pcs" />
                                                <th></th>
                                                <th></th>
                                                <th id="totalSubtotal" class="text-right" style="font-weight:bold;">0.00</th>
                                                <input type="hidden" name="total_subtotal" id="total_subtotal" />
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Add Row Button -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <a href="javascript:void(0);" class="btn btn-primary" id="addRowButton">+Ligne</a>
                                </div>
                            </div>

                            <!-- Submit / Cancel -->
                            <div class="mt-3 col-lg-12">
                                <button type="submit" id="submitFormbutton" class="btn btn-submit me-2">Soumettre</button>
                                {{-- <a href="javascript:void(0);" class="btn btn-cancel">Cancel</a> --}}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Ajouter un nouveau Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCustomerForm">
                        <meta name="csrf-token" content="{{ csrf_token() }}">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Nom du Client</label>
                            <input type="text" class="form-control" id="customerName" name="customerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="mark" class="form-label">Marque</label>
                            <input type="text" class="form-control" id="mark" name="mark" required>
                        </div>
                        <div class="mb-3">
                            <label for="tel" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="tel" name="tel" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="address" class="form-control" id="address" name="address" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter Client</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">Avertissement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Veuillez sélectionner un client ou entrer la date, ou frais avant de soumettre.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="alertModalImage" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">ALERTE!!!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <div id="global-loader" style="display:none;">
      <div class="whirly-loader"></div>
    </div>

    @include('layouts.scripts')
    <!-- JavaScript for Modal and Table Management -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let allowLeave = false;
            const leaveBtn = document.getElementById('leave-page-btn');
            const confirmBtn = document.getElementById('confirm-leave');
            let targetUrl = '';

            // Block all navigation attempts unless allowLeave is true
            window.addEventListener('beforeunload', function (e) {
                if (!allowLeave) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Back button click -> show confirmation modal
            leaveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                targetUrl = leaveBtn.getAttribute('href');
                const modal = new bootstrap.Modal(document.getElementById('leaveConfirmModal'));
                modal.show();
            });

            // Confirm leave -> allow and navigate
            confirmBtn.addEventListener('click', function () {
                allowLeave = true;
                window.location.href = targetUrl;
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addCustomerButton = document.getElementById('addCustomer');
            //console.log('Button click');
            const addCustomerModal = new bootstrap.Modal(document.getElementById('addCustomerModal'));

            addCustomerButton.addEventListener('click', function () {
                addCustomerModal.show();
            });

            // Gestion de la soumission du formulaire d'ajout de client
            document.getElementById('addCustomerForm').addEventListener('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);

                const storeCustomerUrl = "{{ route('facturations.storeCustomer') }}";
                fetch(storeCustomerUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const customerSelect = document.getElementById('customer_id');

                        let newOption = document.createElement('option');
                        newOption.value = data.customer.id;
                        newOption.textContent = `${data.customer.customerName} ${data.customer.mark}`;
                        customerSelect.appendChild(newOption);

                        customerSelect.value = data.customer.id;

                        // Ferme le modal et reset le formulaire
                        addCustomerModal.hide();
                        document.getElementById('addCustomerForm').reset();
                    } else {
                        console.log('Erreur lors de l’ajout du client.');
                    }
                })
                .catch(error => console.error('Erreur:', error));
            });
            // Validation function for numeric fields
            function validateNumericInput(input) {
                const value = parseFloat(input.value);
                if (isNaN(value) || value <= 0) {
                    input.classList.add('is-invalid');
                    input.classList.remove('is-valid');
                    return false;
                }else {
                    input.classList.remove('is-invalid');
                    return true;
                }
            }

            // Function to check if required fields are filled
            function validateRequiredField(input) {
                if (input.value.trim() === '') {
                    input.classList.add('is-invalid');
                    input.classList.remove('is-valid');
                    return false;
                }else {
                    input.classList.remove('is-invalid');
                    return true;
                }
            }
            function showModal(message) {
                document.getElementById('modalMessage').textContent = message;
                const alertModal = new bootstrap.Modal(document.getElementById('alertModalImage'));
                alertModal.show();
            }
            let typingTimer;
            const orderTable = document.querySelector('#orderTable tbody');

            document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
                if (event.target.classList.contains('productName')) {
                        const input = event.target;
                        clearTimeout(typingTimer);
                        if (input.value.length >= 3) {
                            typingTimer = setTimeout(() => {
                                fetchProductSuggestions(input.value, input);
                            }, 300);
                        } else {
                            closeSuggestions(input);
                        }
                    }
                });

            // Nom saisi sans passer par une suggestion : on affiche quand même l'unité.
            document.querySelector('#orderTable tbody').addEventListener('change', function (event) {
                if (event.target.classList.contains('productName') && event.target.value.trim()) {
                    fetchProductDetails(event.target.value.trim(), event.target.closest('tr'));
                }
            });

            function fetchProductSuggestions(query, input) {
                fetch(`/fetch-product-details-suggestion?productName=${encodeURIComponent(query)}&suggestions=true`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.suggestions) {
                            showSuggestions(input, data.suggestions);
                        } else {
                            closeSuggestions(input);
                        }
                    })
                    .catch(error => console.error('Error fetching product suggestions:', error));
                }
                function showSuggestions(input, suggestions) {
                    closeSuggestions(input);

                    const list = document.createElement('ul');
                    list.className = 'suggestions-list';
                    list.style.position = 'absolute';
                    list.style.background = '#fff';
                    list.style.border = '1px solid #ccc';
                    list.style.zIndex = 1000;
                    list.style.width = `${input.offsetWidth}px`;
                    list.style.maxHeight = '200px';
                    list.style.overflowY = 'auto';
                      list.style.listStyle = 'none';
                      list.style.padding = '0';
                      list.style.margin = '0';
                      list.style.boxShadow = '0 2px 15px rgba(0,0,0,0.2)';
                    suggestions.forEach(product => {
                        const li = document.createElement('li');
                        // Unité du produit à côté du nom : « Ciment — Sac… », « Câble — Rouleau (100 m) ».
                        li.textContent = product.libelle + (product.unite ? ' — ' + product.unite : '');
                        li.style.padding = '8px 12px';
                        li.style.cursor = 'pointer';
                                        li.style.borderBottom = '1px solid #eee';

                                                li.addEventListener('mouseover', () => {
                                                    li.style.backgroundColor = '#f5f5f5';
                                                });

                                                li.addEventListener('mouseout', () => {
                                                    li.style.backgroundColor = '';
                                                });
                        li.addEventListener('click', () => {
                            input.value = product.libelle;
                            closeSuggestions(input);
                            fetchProductDetails(product.libelle, input.closest('tr')); // now fetch full details
                        });
                        list.appendChild(li);
                    });

                    input.parentNode.style.position = 'relative';
                    input.parentNode.appendChild(list);
                }
                function closeSuggestions(input) {
                    const existing = input.parentNode.querySelector('.suggestions-list');
                    if (existing) existing.remove();
                }
                function fetchProductDetails(productName, row) {
                    fetch(`/fetch-product-details?exact=1&productName=${encodeURIComponent(productName)}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.product) {
                            const product = data.product;
                              row.querySelector('input[name*="[productName]"]').value = product.libelle || 'null';
                              // Unité unique du produit : la quantité et le prix saisis sont dans cette unité.
                              const uniteCell = row.querySelector('.unite-produit');
                              if (uniteCell) uniteCell.textContent = product.unite || 'Pièce';
                              // Prix de vente par unité proposé, modifiable.
                              const prixInput = row.querySelector('.price');
                              if (prixInput) {
                                  prixInput.dataset.plancher = product.plancher || 0;
                                  prixInput.dataset.origine = product.plancher_origine || '';
                              }
                              if (prixInput && !prixInput.value && product.price) {
                                  prixInput.value = product.price;
                              }
                              updateSubtotal(row);

                              const imageInput = row.querySelector('input[name*="[image]"]');
                              const imagePreview = row.querySelector('.image-preview');

                              if (product.image) {
                                  // Assuming the product image URL is provided in the API response
                                  const imageUrl = `/products/${product.image}`;  // Replace this with the actual image URL in your API response

                                  // Set the image source to the product image URL
                                  imagePreview.src = imageUrl;
                                  imagePreview.style.display = 'block';  // Make the image visible
                              } else {
                                  // Hide the image preview if no image is available
                                  imagePreview.style.display = 'none';
                              }

                              // Optionally, set the input file to the default value (if required)
                              imageInput.value = ''; // Clear the input file value if needed
                            } else {
                              // Nom inconnu : la facture créera un nouveau produit, vendu à la pièce.
                              // (Seulement si le nom n'a pas changé entre-temps, par un clic sur une suggestion.)
                              const uniteCell = row.querySelector('.unite-produit');
                              if (uniteCell && row.querySelector('.productName').value.trim() === productName) {
                                  uniteCell.textContent = 'Pièce (nouveau produit)';
                              }
                            }
                        })
                        .catch(error => console.error('Error fetching product details:', error));
                    }

            // Event listener for real-time validation of table fields
            document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
                const input = event.target;

                // Validate numeric fields (cartons, qty_per_ctn, price, etc.)
                if (input.classList.contains('cartons') || input.classList.contains('qty_per_ctn') || input.classList.contains('price')) {
                    validateNumericInput(input);
                    updateSubtotal(input.closest('tr'));  // Recalculate subtotal after input validation
                }

                // Trigger update of totals
                updateTotal();
            });

            updateTotal();
            let rowIndex = document.querySelectorAll('#orderTable tbody tr').length;

            let formData = {}; // Object to store form data

            const orderTableBody = document.querySelector('#orderTable tbody');
            const addButton = document.getElementById('addRowButton');

            // Optional: Preview image before submission (display thumbnail)
            document.querySelector('#orderTable tbody').addEventListener('change', async function (event) {
                if (event.target.type === 'file') {
                    const fileInput = event.target;
                    const file = fileInput.files[0];

                    if (!file) return;

                    // Resize image before previewing/sending
                    const resizedBlob = await resizeImage(file, 500); // resize to 500px width
                    const previewElement = fileInput.closest('tr').querySelector('.image-preview') || createPreviewImage(fileInput);

                    // Preview resized image
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    };
                    reader.readAsDataURL(resizedBlob);

                    // Replace file in input with resized blob
                    const resizedFile = new File([resizedBlob], file.name, { type: resizedBlob.type });
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(resizedFile);
                    fileInput.files = dataTransfer.files;
                }
            });

            // Helper to create preview <img> tag
            function createPreviewImage(fileInput) {
                const img = document.createElement('img');
                img.classList.add('image-preview');
                img.style.maxWidth = '80px';
                img.style.maxHeight = '80px';
                img.style.display = 'block';
                img.style.marginTop = '5px';
                fileInput.parentNode.appendChild(img);
                return img;
            }

            // Resize function using canvas
            function resizeImage(file, maxWidth) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const img = new Image();
                        img.onload = function () {
                            const scale = maxWidth / img.width;
                            const canvas = document.createElement('canvas');
                            canvas.width = maxWidth;
                            canvas.height = img.height * scale;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                            const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                            canvas.toBlob(blob => resolve(blob), mimeType, 0.75); // 75% quality
                        };
                        img.onerror = reject;
                        img.src = event.target.result;
                    };
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
            }


            // Propriétaire de la marchandise de chaque ligne : fonds propres ou fonds d'un associé.
            const fundsList = @json($funds ?? []);
            function fundSelect(index) {
                if (!fundsList.length) return '';
                let opts = '<option value="">Fonds propres</option>';
                fundsList.forEach(f => { opts += '<option value="' + f.id + '">' + f.name + '</option>'; });
                return '<select name="products[' + index + '][fund_id]" class="form-control form-control-sm mt-1" title="Propriétaire de la marchandise">' + opts + '</select>';
            }

            document.getElementById('addRowButton').addEventListener('click', function () {
                rowIndex++;
                const tableBody = document.querySelector('#orderTable tbody');

                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>${rowIndex}</td>
                    <td><input type="text" name="products[${rowIndex}][productName]" class="form-control productName">${fundSelect(rowIndex)}</td>
                    <td>
                        <input type="file" name="products[${rowIndex}][image]" class="form-control image-input" accept="image/*">
                        <img class="image-preview" style="max-width: 80px; max-height: 80px; display: none; margin-top: 5px;">
                    </td>
                    <td><input type="text" name="products[${rowIndex}][cartons]" class="form-control cartons" placeholder="Qté"></td>
                    <td><input type="hidden" name="products[${rowIndex}][qty_per_ctn]" class="qty_per_ctn" value="1"><span class="unite-produit text-muted">—</span></td>
                    <td><input type="text" name="products[${rowIndex}][price]" class="form-control price" required></td>
                    <td><input type="text" name="products[${rowIndex}][subtotal]" class="form-control subtotal" readonly></td>
                    <td><a href="javascript:void(0);" class="btn btn-danger removeRowButton"><i class="fas fa-trash-alt"></i></a></td>
                `;
                tableBody.appendChild(newRow);

                updateTotal();
            });

              // Event Delegation for row removal & subtotal calculation
              document.querySelector('#orderTable tbody').addEventListener('click', function (event) {
                  if (event.target.closest('.removeRowButton')) {
                      event.target.closest('tr').remove();
                      updateTotal();
                      updateAddButtonVisibility();
                  }
              });

            document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
                if (
                    event.target.classList.contains('cartons') ||
                    event.target.classList.contains('qty_per_ctn') ||
                    event.target.classList.contains('price')
                ) {
                    updateSubtotal(event.target.closest('tr'));
                }
            });

            // Function to calculate subtotal for a row
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

            function updateSubtotal(row) {
                const prixChamp = row.querySelector('.price');
                signalerPlancher(prixChamp, parseFloat(prixChamp?.dataset.plancher) || 0, prixChamp?.dataset.origine || '');
                const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                const qtyPerCtn = parseFloat(row.querySelector('.qty_per_ctn').value) || 0;
                const price = parseFloat(row.querySelector('.price').value) || 0;

                const subtotal = cartons * qtyPerCtn * price;
                row.querySelector('.subtotal').value = subtotal.toFixed(2);

                updateTotal();
            }

            // Function to update total values in the footer
            function updateTotal() {
                let totalPcs = 0;
                let totalSubtotal = 0;

                document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                    const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                    const qtyPerCtn = parseFloat(row.querySelector('.qty_per_ctn').value) || 0;
                    const subtotal = parseFloat(row.querySelector('.subtotal').value) || 0;

                    totalPcs += cartons * qtyPerCtn;
                    totalSubtotal += subtotal;
                });

                document.getElementById('totalPcs').textContent = totalPcs;
                document.getElementById('total_pcs').value = totalPcs;
                document.getElementById('totalSubtotal').textContent = totalSubtotal.toFixed(2);
                document.getElementById('total_subtotal').value = totalSubtotal.toFixed(2);
            }


            $(document).ready(function () {
                $('#commandForm').on('submit', function (e) {
                    e.preventDefault(); // Prevent page reload

                    let formData = new FormData(this);

                    const storeCommandeUrl = "{{ route('facturations.store') }}";
                                     // cache the button
                     const $btn = $('#submitFormbutton');
                     const originalBtnHtml = $btn.html();
                    $.ajax({
                        url: storeCommandeUrl,
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function () {
                           // show overlay preloader
                                   $('#global-loader').stop(true, true).fadeIn(100);

                                   // disable button and show inline spinner
                                   $btn.prop('disabled', true).html(
                                     '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Envoi...'
                                   );
                        },
                        success: function (response) {
                            // Show success message in a Bootstrap modal
                            $('#modalMessage').html('Facturation effectuée avec succès.');
                            $('#alertModalImage').modal('show');

                            // Reset the form
                            $('#commandForm')[0].reset();

                            // Remove all rows from the table (adjust selector as needed)
                            $('#orderTable tbody').empty(); // Replace with your actual table ID
                        },
                        error: function (xhr) {
                            let message = 'Une erreur est survenue.';

                            if (xhr.status === 422) {
                                // Validation errors
                                let errors = xhr.responseJSON.errors;
                                message = '';
                                $.each(errors, function (key, value) {
                                    message += value + '<br>';
                                });
                            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                                message = xhr.responseJSON.error;
                            } else {
                                message = xhr.statusText || message;
                            }

                            // Set message and show modal
                            $('#modalMessage').html(message);
                            $('#alertModalImage').modal('show');
                        },
                           complete: function () {
                                   $('#global-loader').stop(true, true).fadeOut(100);
                                   $btn.prop('disabled', false).html(originalBtnHtml);
                                 }
                    });
                });
            });
        });
    </script>

</body>
</html>

