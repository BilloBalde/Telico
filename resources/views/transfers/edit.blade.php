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
                            <h4>Gestion d'Achat</h4>
                            <h6>Modifier l'Achat</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('transfers.update', $storeProduct->id) }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="text" name="id" value="{{ $storeProduct->id }}" hidden>
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="product_id">Produit</label>
                                            <select name="product_id" id="product_id" class="form-control">
                                                <option value="">Selectionner le Produit</option>
                                                @foreach ($produits as $item)
                                                @php
                                                    $product = App\Models\Product::with('categories')->find($item->id);
                                                @endphp
                                                <option value="{{ $item->id }}" {{ old('product_id', $storeProduct->product_id) == $item->id ? 'selected' : '' }}>
                                                    {{ $item->libelle }}-
                                                    @foreach ($product->categories as $category)
                                                    {{ $category->slug . ' (' . $category->category_type . ')' }}
                                                    @endforeach
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('product_id')
                                            <span class="text-error"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="from_store_id">Source</label>
                                            <select class="select" name="from_store_id" class="form-control">
                                                <option value="">Choisir Stock</option>
                                                @foreach($boutiques as $boutique)
                                                <option value="{{ $boutique->id }}" {{ old('from_store_id', $storeProduct->from_store_id) == $boutique->id ? 'selected' : '' }}>{{ $boutique->store_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('from_store_id')
                                            <span class="text-danger"><strong></strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="to_store_id">Destination</label>
                                            <select class="select" name="to_store_id" class="form-control">
                                                <option value="">Choisir Stock</option>
                                                @foreach($boutiques as $boutique)
                                                <option value="{{ $boutique->id }}" {{ old('to_store_id', $storeProduct->to_store_id) == $boutique->id ? 'selected' : '' }}>{{ $boutique->store_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('to_store_id')
                                            <span class="text-danger"><strong></strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="quantity">Quantité (en {{ \App\Models\Product::motUnite($storeProduct->unite ?: $storeProduct->product?->unite, 2) }})</label>
                                            <input type="text" name="quantity" id="quantity" value="{{ old('quantity', $storeProduct->quantity) }}" class="form-control">
                                            @error('quantity')
                                            <span class="text-danger"><strong></strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="reason">Motif de la correction</label>
                                            <input type="text" name="reason" id="reason" value="{{ old('reason') }}" class="form-control" placeholder="Ex. : erreur de comptage au départ">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted" style="font-size:12px;">
                                            Seule la quantité d'un transfert réceptionné peut être corrigée ; l'écart est enregistré
                                            dans le journal des mouvements comme une correction. Pour changer de produit, de source ou de
                                            destination, annulez ou faites un transfert inverse.
                                        </p>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">Modifier</button>
                                        <a href="{{ route('transfers.index') }}" class="btn btn-cancel">Annuler</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>
