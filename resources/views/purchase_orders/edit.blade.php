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
                            <h4>Gestion des Achats</h4>
                            <h6>Modifier l'achat {{ $order->reference }}</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <form action="{{ route('purchase-orders.update', $order->id) }}" method="POST" id="poForm">
                        @csrf
                        @method('PUT')

                        <div class="card">
                            <div class="card-body">
                            <h5>Informations générales</h5>
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Référence</label>
                                        <input type="text" class="form-control" value="{{ $order->reference }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Destination du stock</label>
                                        <div class="row g-1">
                                            <div class="col-9">
                                            <select name="store_id" id="storeSelect" class="form-control" required>
                                                @foreach($stores as $store)
                                                    <option value="{{ $store->id }}" {{ old('store_id', $order->store_id) == $store->id ? 'selected' : '' }}>{{ $store->full_name }} ({{ $store->type_label }})</option>
                                                @endforeach
                                            </select>
                                            </div>
                                            <div class="col-3">
                                            <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#newStoreModal" title="Ajouter une boutique">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                            </div>
                                        </div>
                                        @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                @include('purchase_orders.partials.supplier', ['selectedSupplierId' => $order->partner_id])
                                @include('purchase_orders.partials.fund', ['selectedFundId' => $order->fund_id])
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Date d'émission</label>
                                        <input type="date" name="date_emis" class="form-control" value="{{ old('date_emis', \Illuminate\Support\Carbon::parse($order->date_emis)->toDateString()) }}" required>
                                        @error('date_emis') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Date de réception (optionnel)</label>
                                        <input type="date" name="date_recu" class="form-control" value="{{ old('date_recu', $order->date_recu ? \Illuminate\Support\Carbon::parse($order->date_recu)->toDateString() : '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Origine de l'achat</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="origin" value="guinee" id="originGuinee" {{ old('origin', $order->origin) == 'guinee' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="originGuinee">Achat local (Guinée)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="origin" value="chine" id="originChine" {{ old('origin', $order->origin) == 'chine' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="originChine">Import (Chine)</label>
                                    </div>
                                </div>
                                @error('origin') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            </div>
                        </div>

                        <div class="card po-import-only">
                            <div class="card-body">
                            <h5>Devise &amp; taux de change</h5>
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Devise de paiement</label>
                                        <select name="currency_code" id="currencyCode" class="form-control">
                                            <option value="">Sélectionner</option>
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency->currencyCode }}" data-rate="{{ $currency->rate_to_gnf }}" data-updated="{{ $currency->updated_at?->format('d/m/Y') }}" {{ old('currency_code', $order->currency_code) == $currency->currencyCode ? 'selected' : '' }}>
                                                    {{ $currency->currencyName }} ({{ $currency->currencyCode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('currency_code') <span class="text-danger" data-error-for="currencyCode">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Taux du jour (1 unité = X GNF)</label>
                                        <input type="text" inputmode="decimal" name="exchange_rate_used" id="exchangeRate" class="form-control decimal-input" value="{{ old('exchange_rate_used', $order->exchange_rate_used) }}">
                                        @error('exchange_rate_used') <span class="text-danger" data-error-for="exchangeRate">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted" id="rateReference"></p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                            <h5>Frais de l'arrivage</h5>
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Transport (GNF)</label>
                                        <input type="text" inputmode="decimal" name="transport_cost_gnf" id="transportCost" class="form-control decimal-input" value="{{ old('transport_cost_gnf', $order->transport_cost_gnf) }}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12 po-import-only">
                                    <div class="form-group">
                                        <label>Douane / Dédouanement (GNF)</label>
                                        <input type="text" inputmode="decimal" name="customs_cost_gnf" id="customsCost" class="form-control decimal-input" value="{{ old('customs_cost_gnf', $order->customs_cost_gnf) }}">
                                    </div>
                                </div>
                            </div>
                            {{-- Champ retiré du formulaire, mais conservé en caché : sans lui,
                                 modifier un achat ancien remettrait ses frais divers à 0 et
                                 fausserait son coût de revient. L'id reste, le JS de calcul
                                 continue donc de fonctionner sans changement. --}}
                            <input type="hidden" name="other_fees_gnf" id="otherFees" value="{{ old('other_fees_gnf', $order->other_fees_gnf) }}">
                            <p class="text-muted">
                                Le transport et la douane sont répartis automatiquement entre les produits, au prorata du volume (CBM) de chaque ligne.
                            </p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5>Produits achetés</h5>
                                <div class="table-responsive mb-3">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Produit</th>
                                                <th>Cartons</th>
                                                <th>Unités / carton</th>
                                                <th>Quantité</th>
                                                <th class="po-price-label">Prix par unité</th>
                                                <th class="po-import-only">CBM / carton</th>
                                                <th class="po-import-only">CBM / unité</th>
                                                <th>Description</th>
                                                <th>Coût de revient</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        {{-- id sur le tbody : addLine() y insère les <tr> --}}
                                        <tbody id="poLines"></tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Totaux</th>
                                                <th><span id="grandTotalCbm">0.000</span> CBM</th>
                                                <th></th>
                                                <th class="po-import-only"></th>
                                                <th class="po-import-only"></th>
                                                <th></th>
                                                <th><span id="grandTotalCost">0</span> GNF</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-primary w-100" id="addLineBtn">
                                    <i class="fa fa-plus"></i> Ajouter une ligne
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control">{{ old('notes', $order->notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-submit">Mettre à jour l'achat</button>
                        <a href="{{ route('purchase-orders.show', $order->id) }}" class="btn btn-cancel">Annuler</a>
                    </form>
                </div>
            </div>
        </div>

        <template id="poLineTemplate">
            <tr data-line>
                <td>
                    <div class="row g-1">
                        <div class="col-9">
                            <select name="lines[__INDEX__][product_id]" class="form-control" data-field="product_id" required>
                                <option value="">Sélectionner le produit</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-unite="{{ $product->unite }}" data-unite-pluriel="{{ \App\Models\Product::motUnite($product->unite, 2) }}">{{ $product->libelle }} ({{ $product->sku }}) — {{ $product->unite_libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#newProductModal" title="Ajouter un produit">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </td>
                <td><input type="number" min="0" name="lines[__INDEX__][cartons]" class="form-control" data-field="cartons" value="0"></td>
                <td><input type="number" min="0" name="lines[__INDEX__][pieces_per_carton]" class="form-control" data-field="pieces_per_carton" value="0"></td>
                <td><input type="number" min="1" name="lines[__INDEX__][quantity]" class="form-control" data-field="quantity" readonly required><small class="text-muted" data-unite-hint></small></td>
                <td><input type="text" inputmode="decimal" name="lines[__INDEX__][unit_price_foreign]" class="form-control decimal-input" data-field="unit_price_foreign" required><small class="text-muted" data-unite-prix></small></td>
                <td class="po-import-only"><input type="text" inputmode="decimal" name="lines[__INDEX__][cbm_per_carton]" class="form-control decimal-input" data-field="cbm_per_carton" placeholder="Ex : 0.085"></td>
                <td class="po-import-only"><input type="text" class="form-control" data-field="cbm_per_unit_display" readonly></td>
                <td><input type="text" name="lines[__INDEX__][description]" class="form-control" data-field="description"></td>
                {{-- Remplace l'ancien encadré .po-line-cost-preview : même donnée, en colonne --}}
                <td data-preview><strong data-preview-cost>0</strong> GNF <small class="text-muted" data-unite-prix></small></td>
                <td><button type="button" class="btn btn-secondary" data-remove-line title="Retirer"><i class="fa fa-trash"></i></button></td>
            </tr>
        </template>

        <!-- Modale : nouvelle boutique -->
        <div class="modal fade" id="newStoreModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvel emplacement (magasin, entrepôt ou stock)</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="newStoreError" class="alert alert-danger" style="display:none;"></div>
                        <div class="form-group">
                            <label>Nom de l'emplacement</label>
                            <input type="text" id="newStoreName" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ville</label>
                            <select id="newStorePlace" class="form-control">
                                <option value="">Sélectionner</option>
                                @foreach($places as $place)
                                    <option value="{{ $place->id }}">{{ $place->placeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Type d'emplacement</label>
                            <select id="newStoreType" class="form-control">
                                @foreach(\App\Models\Store::TYPES as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}" {{ $typeValue === 'entrepot' ? 'selected' : '' }}>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rattaché à (optionnel)</label>
                            <select id="newStoreParent" class="form-control">
                                <option value="">— Aucun —</option>
                                @foreach(\App\Models\Store::optionsFor(null)->where('type', '!=', 'stock') as $parentStore)
                                    <option value="{{ $parentStore->id }}">{{ $parentStore->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Responsable</label>
                            <select id="newStoreManager" class="form-control">
                                <option value="">Sélectionner</option>
                                @foreach($storeManagers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-submit" id="newStoreSubmit">Créer l'emplacement</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale : nouveau produit -->
        <div class="modal fade" id="newProductModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau produit</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="newProductError" class="alert alert-danger" style="display:none;"></div>
                        <div class="form-group">
                            <label>Nom du produit</label>
                            <input type="text" id="newProductLibelle" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" id="newProductSku" class="form-control">
                        </div>
                        @include('purchase_orders.partials.new-product-unit')
                        <p class="text-muted" style="font-size:13px;">
                            Le prix de revient n'est pas demandé ici : il sera calculé automatiquement
                            (avec le prix de vente, +30%) une fois cet achat enregistré.
                        </p>
                        <div class="form-group">
                            <label>Catégorie</label>
                            <select id="newProductCategory" class="form-control">
                                <option value="">Sélectionner</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-submit" id="newProductSubmit">Créer le produit</button>
                    </div>
                </div>
            </div>
        </div>

        @php
            $existingLinesData = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'cartons' => $item->cartons,
                    'pieces_per_carton' => $item->pieces_per_carton,
                    'unit_price_foreign' => $item->unit_price_foreign,
                    'unit_price_gnf' => $item->unit_price_gnf,
                    'cbm_per_carton' => $item->cbm_per_carton,
                    'description' => $item->description,
                ];
            })->all();
        @endphp
        @include('layouts.scripts')
        @stack('purchase_modals')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const linesContainer = document.getElementById('poLines');
            const template = document.getElementById('poLineTemplate');
            const addLineBtn = document.getElementById('addLineBtn');
            let lineIndex = 0;

            const existingLines = @json($existingLinesData);

            $('#newProductCategory').select2({
                width: '100%',
                placeholder: 'Rechercher une catégorie…',
                dropdownParent: $('#newProductModal'),
            });

            // ============================================================
            // Modales "Nouvelle boutique" / "Nouveau produit"
            // ============================================================
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function postJson(url, payload) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                }).then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                });
            }

            function showModalError(elId, message) {
                const el = document.getElementById(elId);
                el.textContent = message;
                el.style.display = 'block';
            }

            function hideModalError(elId) {
                document.getElementById(elId).style.display = 'none';
            }

            const newStoreModalEl = document.getElementById('newStoreModal');
            const newStoreModal = bootstrap.Modal.getOrCreateInstance(newStoreModalEl);
            const newProductModalEl = document.getElementById('newProductModal');
            const newProductModal = bootstrap.Modal.getOrCreateInstance(newProductModalEl);

            // --- Nouvelle boutique ---
            document.getElementById('newStoreSubmit').addEventListener('click', function () {
                hideModalError('newStoreError');
                postJson('{{ route('boutiques.quickCreate') }}', {
                    store_name: document.getElementById('newStoreName').value,
                    place_id: document.getElementById('newStorePlace').value,
                    user_id: document.getElementById('newStoreManager').value,
                    type: document.getElementById('newStoreType').value,
                    parent_id: document.getElementById('newStoreParent').value || null,
                }).then(function (result) {
                    if (!result.ok || !result.data.success) {
                        const errors = result.data.errors
                            ? Object.values(result.data.errors).flat().join(' ')
                            : (result.data.message || 'Erreur lors de la création.');
                        showModalError('newStoreError', errors);
                        return;
                    }

                    const store = result.data.store;
                    const storeSelect = document.getElementById('storeSelect');
                    const option = new Option(store.store_name, store.id, true, true);
                    storeSelect.add(option);
                    storeSelect.dispatchEvent(new Event('change', { bubbles: true }));

                    document.getElementById('newStoreName').value = '';
                    document.getElementById('newStorePlace').value = '';
                    document.getElementById('newStoreManager').value = '';
                    newStoreModal.hide();
                }).catch(function () {
                    showModalError('newStoreError', 'Erreur réseau, réessayez.');
                });
            });

            // --- Nouveau produit ---
            let activeProductSelect = null;

            newProductModalEl.addEventListener('show.bs.modal', function (event) {
                const opener = event.relatedTarget;
                const line = opener ? opener.closest('[data-line]') : null;
                activeProductSelect = line ? line.querySelector('[data-field="product_id"]') : null;
            });

            document.getElementById('newProductSubmit').addEventListener('click', function () {
                hideModalError('newProductError');
                postJson('{{ route('produits.quickCreate') }}', {
                    libelle: document.getElementById('newProductLibelle').value,
                    sku: document.getElementById('newProductSku').value,
                    category_id: document.getElementById('newProductCategory').value,
                    unite: document.getElementById('newProductUnite').value,
                    pcs_par_carton: document.getElementById('newProductPcsCarton').value,
                    pcs_par_paquet: document.getElementById('newProductPcsPaquet').value,
                    dimension_rouleau: document.getElementById('newProductDimension').value,
                }).then(function (result) {
                    if (!result.ok || !result.data.success) {
                        const errors = result.data.errors
                            ? Object.values(result.data.errors).flat().join(' ')
                            : (result.data.message || 'Erreur lors de la création.');
                        showModalError('newProductError', errors);
                        return;
                    }

                    const product = result.data.product;
                    const label = product.libelle + ' (' + product.sku + ') — ' + product.unite_libelle;
                        // Option avec l'unité du produit, comme les options existantes.
                        const nouvelleOption = function () {
                            const opt = new Option(label, product.id);
                            opt.dataset.unite = product.unite;
                            opt.dataset.unitePluriel = product.unite_pluriel;
                            return opt;
                        };

                    document.querySelectorAll('[data-field="product_id"]').forEach(function (select) {
                        select.add(nouvelleOption());
                    });
                    template.content.querySelectorAll('[data-field="product_id"]').forEach(function (select) {
                        select.add(nouvelleOption());
                    });

                    if (activeProductSelect) {
                        activeProductSelect.value = product.id;
                        activeProductSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    document.getElementById('newProductLibelle').value = '';
                    document.getElementById('newProductSku').value = '';
                    document.getElementById('newProductCategory').value = '';
                    document.getElementById('newProductUnite').value = 'pcs';
                    document.getElementById('newProductUnite').dispatchEvent(new Event('change'));
                    ['newProductPcsCarton', 'newProductPcsPaquet', 'newProductDimension'].forEach(id => document.getElementById(id).value = '');
                    newProductModal.hide();
                }).catch(function () {
                    showModalError('newProductError', 'Erreur réseau, réessayez.');
                });
            });

            // Champs prix/CBM/taux : on tape librement (virgule ou point), on ne garde
            // que les chiffres et un seul séparateur décimal (converti en point tout de
            // suite) — évite les soucis des inputs type=number avec le clavier/locale française.
            function sanitizeDecimal(el) {
                let value = el.value.replace(/[^0-9.,]/g, '').replace(',', '.');
                const firstDot = value.indexOf('.');
                if (firstDot !== -1) {
                    value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
                }
                el.value = value;
            }

            function wireDecimalInput(el) {
                el.addEventListener('input', function () {
                    sanitizeDecimal(el);
                    recompute();
                });
            }

            [document.getElementById('exchangeRate'), document.getElementById('transportCost'), document.getElementById('customsCost')].forEach(wireDecimalInput);

            // Les messages d'erreur du serveur (ex: "Le taux de change est obligatoire")
            // restent affichés tant qu'on n'a pas resoumis le formulaire — on les cache
            // dès que l'utilisateur corrige le champ concerné, pour éviter la confusion.
            function clearErrorFor(id) {
                const span = document.querySelector('[data-error-for="' + id + '"]');
                if (span) {
                    span.style.display = 'none';
                }
            }
            ['currencyCode', 'exchangeRate'].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                const eventName = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(eventName, function () {
                    if (el.value) clearErrorFor(id);
                });
            });

            function setOriginClass() {
                const isChine = document.getElementById('originChine').checked;
                document.body.classList.toggle('origin-chine', isChine);
                    // L'affichage des champs d'import était piloté par du CSS ; le design
                    // minimal n'en ayant plus, c'est le JS qui s'en charge.
                    document.querySelectorAll('.po-import-only').forEach(function (el) {
                        el.style.display = isChine ? '' : 'none';
                    });
                document.querySelectorAll('.po-price-label').forEach(function (label) {
                    label.textContent = isChine ? 'Prix par unité (devise)' : 'Prix par unité (GNF)';
                });
                recompute();
            }

            document.getElementById('originGuinee').addEventListener('change', setOriginClass);
            document.getElementById('originChine').addEventListener('change', setOriginClass);

            // Rappel du taux de référence enregistré (CurrencySettingController) : reste
            // affiché même si l'utilisateur saisit un taux du jour différent au-dessus.
            function updateRateReference() {
                const select = document.getElementById('currencyCode');
                const ref = document.getElementById('rateReference');
                const selected = select.options[select.selectedIndex];
                const rate = selected ? selected.getAttribute('data-rate') : null;
                const updated = selected ? selected.getAttribute('data-updated') : null;

                if (!select.value || !rate) {
                    ref.textContent = '';
                    return;
                }

                const formattedRate = new Intl.NumberFormat('fr-FR').format(parseFloat(rate));
                ref.textContent = '💡 Taux de référence enregistré : 1 ' + select.value + ' = ' + formattedRate + ' GNF'
                    + (updated ? ' (mis à jour le ' + updated + ')' : '')
                    + '. Vous pouvez saisir un taux du jour différent ci-dessus si besoin.';
            }

            // On ne pré-remplit plus le taux avec la valeur de référence enregistrée —
            // l'utilisateur doit saisir lui-même le taux du jour (le rappel affiché en
            // dessous lui sert juste de repère).
            document.getElementById('currencyCode').addEventListener('change', function () {
                const rateInput = document.getElementById('exchangeRate');

                if (this.value === "") {
                    rateInput.value = "1";
                    rateInput.required = false;
                } else {
                    rateInput.value = "";
                    rateInput.required = true;
                }
                updateRateReference();
                recompute();
            });

            function addLine(data) {
                data = data || {};
                const html = template.innerHTML.replaceAll('__INDEX__', lineIndex);
                // <tbody> et non <div> : le parseur HTML supprime un <tr> injecté
                    // dans un conteneur qui n'est pas une table.
                    const wrapper = document.createElement('tbody');
                wrapper.innerHTML = html;
                const lineEl = wrapper.firstElementChild;
                linesContainer.appendChild(lineEl);
                lineIndex++;

                if (data.product_id) {
                    lineEl.querySelector('[data-field="product_id"]').value = data.product_id;
                }
                if (data.cartons) {
                    lineEl.querySelector('[data-field="cartons"]').value = data.cartons;
                }
                if (data.pieces_per_carton) {
                    lineEl.querySelector('[data-field="pieces_per_carton"]').value = data.pieces_per_carton;
                }
                if (data.quantity) {
                    lineEl.querySelector('[data-field="quantity"]').value = data.quantity;
                }
                const isChineOrder = document.getElementById('originChine').checked;
                const priceField = lineEl.querySelector('[data-field="unit_price_foreign"]');
                if (isChineOrder && data.unit_price_foreign) {
                    priceField.value = data.unit_price_foreign;
                } else if (data.unit_price_gnf) {
                    priceField.value = data.unit_price_gnf;
                }
                if (data.cbm_per_carton) {
                    lineEl.querySelector('[data-field="cbm_per_carton"]').value = data.cbm_per_carton;
                }
                if (data.description) {
                    lineEl.querySelector('[data-field="description"]').value = data.description;
                }

                lineEl.querySelector('[data-remove-line]').addEventListener('click', function () {
                    if (linesContainer.querySelectorAll('[data-line]').length <= 1) {
                        return;
                    }
                    lineEl.remove();
                    recompute();
                });

                // Calcul automatique cartons × pièces = quantité (comme à la création).
                const cartonsField = lineEl.querySelector('[data-field="cartons"]');
                const piecesField = lineEl.querySelector('[data-field="pieces_per_carton"]');
                const quantityField = lineEl.querySelector('[data-field="quantity"]');

                function recalcQuantity() {
                    const cartons = parseInt(cartonsField.value) || 0;
                    const pieces = parseInt(piecesField.value) || 0;
                    if (cartons > 0 && pieces > 0) {
                        quantityField.value = cartons * pieces;
                        recompute();
                    }
                }
                cartonsField.addEventListener('input', recalcQuantity);
                piecesField.addEventListener('input', recalcQuantity);

                lineEl.querySelectorAll('input, select').forEach(function (el) {
                    if (el.classList.contains('decimal-input')) {
                        wireDecimalInput(el);
                    } else {
                        el.addEventListener('input', recompute);
                    }
                    el.addEventListener('change', recompute);
                });

                // Sélecteur de produit avec recherche — initialisé après avoir positionné
                // la valeur ci-dessus, pour que le produit déjà sélectionné s'affiche bien.
                $(lineEl).find('[data-field="product_id"]').select2({
                    width: '100%',
                    placeholder: 'Sélectionner le produit'
                });
                $(lineEl).find('[data-field="product_id"]').on('change', function () { appliquerUnite(lineEl); });
                appliquerUnite(lineEl);
            }


                // Unité unique du produit : la quantité achetée (et entrée en stock)
                // est dans cette unité. Un produit vendu au carton s'achète au
                // carton : 1 « unité par carton », quantité = nombre de cartons.
                function appliquerUnite(lineEl) {
                    const opt = lineEl.querySelector('[data-field="product_id"]')?.selectedOptions[0];
                    const unite = opt?.dataset.unite || 'pcs';
                    const piecesField = lineEl.querySelector('[data-field="pieces_per_carton"]');
                    const hint = lineEl.querySelector('[data-unite-hint]');
                    if (hint) hint.textContent = opt?.value ? 'en ' + (opt.dataset.unitePluriel || 'pièces') : '';
                    // Prix et coût de revient de la ligne : par unité du produit (« / carton »).
                    const singulier = { pcs: 'pièce', douzaine: 'douzaine', carton: 'carton', paquet: 'paquet', rouleau: 'rouleau' }[unite];
                    lineEl.querySelectorAll('[data-unite-prix]').forEach(el => el.textContent = opt?.value ? '/ ' + singulier : '');
                    if (unite === 'carton') {
                        piecesField.value = 1;
                        piecesField.readOnly = true;
                        piecesField.dispatchEvent(new Event('input'));
                    } else {
                        piecesField.readOnly = false;
                    }
                }
            function recompute() {
                const isChine = document.getElementById('originChine').checked;
                const currencySelect = document.getElementById('currencyCode');
                let rate = 1;
                // Aligné sur PurchaseOrderCostingService : pour un achat local le taux
                // vaut toujours 1, même si une devise reste sélectionnée d'un passage
                // précédent en import.
                if (isChine && currencySelect && currencySelect.value !== '') {
                    rate = parseFloat(document.getElementById('exchangeRate').value) || 1;
                }

                const transport = parseFloat(document.getElementById('transportCost').value) || 0;
                const customs = isChine ? (parseFloat(document.getElementById('customsCost').value) || 0) : 0;
                const otherFees = parseFloat(document.getElementById('otherFees').value) || 0;

                const lines = Array.from(linesContainer.querySelectorAll('[data-line]'));
                let totalCbm = 0;
                let grandTotalPrice = 0;

                const computed = lines.map(function (lineEl) {
                    const quantity = parseFloat(lineEl.querySelector('[data-field="quantity"]').value) || 0;
                    const unitPriceForeign = parseFloat(lineEl.querySelector('[data-field="unit_price_foreign"]').value) || 0;

                    // CBM saisi par carton, divisé par le nombre de pièces du carton pour
                    // obtenir le CBM exact d'une seule unité.
                    const piecesPerCarton = parseInt(lineEl.querySelector('[data-field="pieces_per_carton"]')?.value) || 0;
                    const cbmPerCarton = isChine ? (parseFloat(lineEl.querySelector('[data-field="cbm_per_carton"]')?.value) || 0) : 0;
                    const cbmPerUnit = (isChine && piecesPerCarton > 0) ? (cbmPerCarton / piecesPerCarton) : 0;

                    const cbmDisplay = lineEl.querySelector('[data-field="cbm_per_unit_display"]');
                    if (cbmDisplay) {
                        cbmDisplay.value = cbmPerUnit > 0 ? cbmPerUnit.toFixed(6) : '';
                    }

                    const lineTotalCbm = cbmPerUnit * quantity;
                    totalCbm += lineTotalCbm;

                    const unitPriceGnf = isChine ? unitPriceForeign * rate : unitPriceForeign;
                    const lineTotalPrice = unitPriceGnf * quantity;
                    grandTotalPrice += lineTotalPrice;

                    return { lineEl, quantity, unitPriceGnf, lineTotalCbm, lineTotalPrice };
                });

                let grandTotalCost = 0;
                computed.forEach(function (item) {
                    const share = totalCbm > 0 ? (item.lineTotalCbm / totalCbm) : (1 / computed.length);
                    const freightShare = transport * share;
                    const customsShare = customs * share;
                    const otherFeesShare = otherFees * share;
                    const landedTotal = item.lineTotalPrice + freightShare + customsShare + otherFeesShare;
                    const landedUnit = item.quantity > 0 ? landedTotal / item.quantity : 0;

                    grandTotalCost += landedTotal;

                    const preview = item.lineEl.querySelector('[data-preview-cost]');
                    if (preview) {
                        preview.textContent = new Intl.NumberFormat('fr-FR').format(Math.round(landedUnit));
                    }
                });

                document.getElementById('grandTotalCbm').textContent = totalCbm.toFixed(3);
                document.getElementById('grandTotalCost').textContent = new Intl.NumberFormat('fr-FR').format(Math.round(grandTotalCost));
            }

            addLineBtn.addEventListener('click', function () { addLine(); });

            setOriginClass();
            updateRateReference();
            document.getElementById('exchangeRate').required = document.getElementById('currencyCode').value !== "";
            if (existingLines.length > 0) {
                existingLines.forEach(function (line) { addLine(line); });
            } else {
                addLine();
            }
            recompute();
        });
        </script>
    </body>
</html>
