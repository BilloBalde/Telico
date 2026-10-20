<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .form-section {
                border: 1px solid #CED5AE;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 24px;
                background: #fffdfb;
            }

            .form-section-title {
                font-size: 15px;
                font-weight: 700;
                color: #3E4C21;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .form-section-title i {
                font-size: 14px;
            }

            .promo-toggle-row {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 16px;
            }

            .promo-toggle-row label {
                margin: 0;
                font-weight: 600;
                cursor: pointer;
            }

            .promo-fields {
                display: none;
            }

            .promo-fields.active {
                display: flex;
                flex-wrap: wrap;
                margin: 0 -12px;
            }

            .promo-preview {
                display: none;
                align-items: center;
                gap: 14px;
                background: #fbf3ea;
                border: 1px solid #CED5AE;
                border-radius: 8px;
                padding: 12px 16px;
                margin-top: 8px;
            }

            .promo-preview.active {
                display: flex;
            }

            .promo-preview .old-price {
                text-decoration: line-through;
                color: #a99b8c;
                font-size: 14px;
            }

            .promo-preview .new-price {
                color: #3E4C21;
                font-weight: 700;
                font-size: 18px;
            }

            .promo-preview .badge-percent {
                background: #3E4C21;
                color: #fff;
                font-size: 12px;
                font-weight: 700;
                padding: 2px 9px;
                border-radius: 999px;
            }
        </style>
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
                            <h4>Modification Produit</h4>
                            <h6>Modifier le Produit</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('produits.update', $product->id) }}" method="POST" id="Register" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="id" value="{{ $product->id }}">

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-box"></i> Informations générales</div>
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="libelle">Libelle</label>
                                                <input type="text" id="libelle" name="libelle" class="form-control" value="{{ old('libelle', $product->libelle) }}" required>
                                                @error('libelle')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="sku">Identifiant Stock</label>
                                                <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                                                @error('sku')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="categories">Categories:</label>
                                                <select name="categories[]" id="categories" multiple class="form-control" required>
                                                    @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ in_array($category->id, $product->categories->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                        {{ $category->category_type }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                @error('categories')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            @if ($userStoreId)
                                                {{-- Locked to a single store --}}
                                                <div class="mb-2">
                                                    <strong>Magasin :</strong>
                                                    {{ \App\Models\Store::find($userStoreId)?->store_name ?? 'N/A' }}
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Stock dans ce magasin :</strong>
                                                    <span id="stockForStore">{{ $quantityForUser }}</span> <span data-unite-pluriel>{{ \App\Models\Product::motUnite($product->unite, 2) }}</span>
                                                </div>
                                                <div class="text-muted">
                                                    <strong>Stock total (tous magasins) :</strong>
                                                    <span>{{ $totalQty }}</span> <span data-unite-pluriel>{{ \App\Models\Product::motUnite($product->unite, 2) }}</span>
                                                </div>
                                                <input type="hidden" id="store_id" value="{{ $userStoreId }}">
                                            @else
                                                {{-- Multi-store: let user pick a store and show its stock --}}
                                                <div class="row g-3 align-items-end">
                                                    <div class="col-md-6">
                                                        <label for="store_id" class="form-label">Magasin</label>
                                                        <select id="store_id" name="store_id" class="form-control">
                                                            <option value="">-- Sélectionner magasin --</option>
                                                            @foreach ($stores as $s)
                                                                <option value="{{ $s->id }}">{{ $s->store_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('store_id')
                                                        <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div>
                                                            <strong>Stock dans ce magasin :</strong>
                                                            <span id="stockForStore">0</span> <span data-unite-pluriel>{{ \App\Models\Product::motUnite($product->unite, 2) }}</span>
                                                        </div>
                                                        <div class="text-muted">
                                                            <strong>Stock total (tous magasins) :</strong>
                                                            <span>{{ $totalQty }}</span> <span data-unite-pluriel>{{ \App\Models\Product::motUnite($product->unite, 2) }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-6">
                                            <div class="form-group">
                                                <label for="stock_restant">Stock global (en <span data-unite-pluriel>pièces</span>)</label>
                                                <input type="number" id="stock_restant" name="stock_restant" class="form-control" value="{{ old('stock_restant', $totalQty) }}">
                                                @error('stock_restant')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea id="description" name="description" class="form-control" required>{{ old('description', $product->description) }}</textarea>
                                                @error('description')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @include('products.partials.unites')

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-tag"></i> Tarification</div>
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="price">Prix de Vente par <span data-unite-singulier>pièce</span> (FG)</label>
                                                <input type="text" id="price" name="price" class="form-control" value="{{ old('price', $product->price) }}">
                                                @error('price')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="price_sale">Prix de Revient par <span data-unite-singulier>pièce</span> (FG)</label>
                                                <input type="text" id="price_sale" name="price_sale" class="form-control" value="{{ old('price_sale', $product->price_sale ?? 0) }}">
                                                @error('price_sale')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        @include('products.partials.prix-minimum')
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-percent"></i> Promotion</div>
                                    <div class="promo-toggle-row">
                                        <input type="checkbox" id="is_promo" name="is_promo" value="1" style="width:18px;height:18px;" {{ old('is_promo', $product->is_promo) ? 'checked' : '' }}>
                                        <label for="is_promo">Activer une promotion sur ce produit</label>
                                    </div>
                                    <div class="promo-fields {{ old('is_promo', $product->is_promo) ? 'active' : '' }}" id="promoFields">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="promo_percent">Pourcentage de réduction (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" id="promo_percent" name="promo_percent" class="form-control" value="{{ old('promo_percent', $product->promo_percent) }}">
                                                @error('promo_percent')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="promo_start_date">Début de la promo</label>
                                                <input type="date" id="promo_start_date" name="promo_start_date" class="form-control" value="{{ old('promo_start_date', $product->promo_start_date?->format('Y-m-d')) }}">
                                                @error('promo_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="promo_end_date">Fin de la promo</label>
                                                <input type="date" id="promo_end_date" name="promo_end_date" class="form-control" value="{{ old('promo_end_date', $product->promo_end_date?->format('Y-m-d')) }}">
                                                @error('promo_end_date')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="promo-preview" id="promoPreview">
                                                <span class="old-price" id="promoOldPrice">0 FG</span>
                                                <i class="fa fa-arrow-right" style="color:#3E4C21;"></i>
                                                <span class="new-price" id="promoNewPrice">0 FG</span>
                                                <span class="badge-percent" id="promoBadge">-0%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-image"></i> Image</div>
                                    <div class="form-group">
                                        @if ($product->image)
                                            <img src="{{ asset('products/' . $product->image) }}" alt="product image" style="width: 150px; height: auto; border: 1px solid #ccc; margin-bottom: 10px;">
                                        @else
                                            <p>Aucune Image associated</p>
                                        @endif

                                        <input type="file" name="image" id="image" class="form-control">
                                        <small class="form-text text-muted">Mettre a jour l'image of product.</small>
                                        @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <x-form-actions mode="edit" cancel-route="{{ route('produits.index') }}"/>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $('#categories').select2({
                    width: '100%',
                    placeholder: 'Rechercher une catégorie…',
                });

                // Preloaded per-store quantities from controller
                const perStore = @json($perStoreQuantities);
                const storeSelect = document.getElementById('store_id');
                const out = document.getElementById('stockForStore');

                function updateQty() {
                    const id = storeSelect?.value || storeSelect?.getAttribute('value');
                    const qty = (id && perStore[id]) ? perStore[id] : 0;
                    if (out) out.textContent = qty;
                }

                if (storeSelect) {
                    // initialize if a value is already set
                    updateQty();
                    storeSelect.addEventListener('change', updateQty);
                }

                const isPromoCheckbox = document.getElementById('is_promo');
                const promoFields = document.getElementById('promoFields');
                const promoPreview = document.getElementById('promoPreview');
                const priceInput = document.getElementById('price');
                const percentInput = document.getElementById('promo_percent');

                function formatGNF(value) {
                    return new Intl.NumberFormat('fr-FR').format(Math.round(value)) + ' FG';
                }

                function updatePromoUI() {
                    const enabled = isPromoCheckbox.checked;
                    promoFields.classList.toggle('active', enabled);

                    const price = parseFloat(priceInput.value) || 0;
                    const percent = parseFloat(percentInput.value) || 0;

                    if (enabled && price > 0 && percent > 0) {
                        const newPrice = price * (1 - percent / 100);
                        document.getElementById('promoOldPrice').textContent = formatGNF(price);
                        document.getElementById('promoNewPrice').textContent = formatGNF(newPrice);
                        document.getElementById('promoBadge').textContent = '-' + percent + '%';
                        promoPreview.classList.add('active');
                    } else {
                        promoPreview.classList.remove('active');
                    }
                }

                isPromoCheckbox.addEventListener('change', updatePromoUI);
                priceInput.addEventListener('input', updatePromoUI);
                percentInput.addEventListener('input', updatePromoUI);
                updatePromoUI();
            });
        </script>
    </body>
</html>
