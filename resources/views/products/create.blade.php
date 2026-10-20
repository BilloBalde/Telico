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
                            <h4>Gestion Produits</h4>
                            <h6>Ajout Produit</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('produits.store') }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-box"></i> Informations générales</div>
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="libelle">Libelle</label>
                                                <input type="text" id="libelle" name="libelle" class="form-control" value="{{ old('libelle') }}">
                                                @error('libelle')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="sku">Identifiant Stock</label>
                                                <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku') }}">
                                                @error('sku')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="categories">Categories:</label>
                                                <select name="categories[]" id="categories" multiple required class="form-control">
                                                    @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->category_type }}</option>
                                                    @endforeach
                                                </select>
                                                @error('categories')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="store_id">Magasin</label>
                                                <select name="store_id" id="store_id" class="form-control">
                                                    <option value="">Sélectionner Magasin</option>
                                                    @foreach($stores as $store)
                                                        <option value="{{ $store->id }}">{{ $store->store_name ?? $store->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('store_id')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="stock_initial">Stock initial (en <span data-unite-pluriel>pièces</span>)</label>
                                                <input type="number" id="stock_initial" name="stock_initial" class="form-control" value="{{ old('stock_initial') }}">
                                                @error('stock_initial')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea id="description" name="description" class="form-control">{{ old('description') }}</textarea>
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
                                                <label for="price_sale">Prix de Revient par <span data-unite-singulier>pièce</span> (FG)</label>
                                                <input type="text" inputmode="decimal" id="price_sale" name="price_sale" class="form-control" value="{{ old('price_sale') }}" required>
                                                @error('price_sale')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="price">Prix de Vente par <span data-unite-singulier>pièce</span> (FG) <small class="text-muted">— calculé automatiquement (+30%)</small></label>
                                                <input type="text" id="price" name="price" class="form-control" value="{{ old('price') }}" readonly style="background:#f5f5f5">
                                                @error('price')
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
                                        <input type="checkbox" id="is_promo" name="is_promo" value="1" style="width:18px;height:18px;" {{ old('is_promo') ? 'checked' : '' }}>
                                        <label for="is_promo">Activer une promotion sur ce produit</label>
                                    </div>
                                    <div class="promo-fields {{ old('is_promo') ? 'active' : '' }}" id="promoFields">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="promo_percent">Pourcentage de réduction (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" id="promo_percent" name="promo_percent" class="form-control" value="{{ old('promo_percent') }}">
                                                @error('promo_percent')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="promo_start_date">Début de la promo</label>
                                                <input type="date" id="promo_start_date" name="promo_start_date" class="form-control" value="{{ old('promo_start_date') }}">
                                                @error('promo_start_date')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="promo_end_date">Fin de la promo</label>
                                                <input type="date" id="promo_end_date" name="promo_end_date" class="form-control" value="{{ old('promo_end_date') }}">
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
                                        <div class="image-upload">
                                            <input type="file" name="image" value="{{ old('image') }}">
                                            <div class="image-uploads">
                                                <img src="{{ asset('assets/img/icons/upload.svg') }}" alt="img">
                                                <h4>Glissez un fichier ici pour l'envoyer</h4>
                                            </div>
                                        </div>
                                        @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <x-form-actions cancel-route="{{ route('produits.index') }}"/>
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

                const isPromoCheckbox = document.getElementById('is_promo');
                const promoFields = document.getElementById('promoFields');
                const promoPreview = document.getElementById('promoPreview');
                const priceInput = document.getElementById('price');
                const priceSaleInput = document.getElementById('price_sale');
                const percentInput = document.getElementById('promo_percent');

                // Prix de vente = prix de revient + 30% de marge (même règle que pour
                // le calcul du coût de revient à l'achat).
                function recomputeSalePrice() {
                    const priceSale = parseFloat(priceSaleInput.value) || 0;
                    priceInput.value = priceSale > 0 ? Math.round(priceSale * 1.3) : '';
                    updatePromoUI();
                }
                priceSaleInput.addEventListener('input', recomputeSalePrice);

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
                percentInput.addEventListener('input', updatePromoUI);
                recomputeSalePrice();
            });
        </script>
    </body>
</html>
