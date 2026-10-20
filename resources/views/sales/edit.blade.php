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
                            <h4>Gestion de Vente</h4>
                            <h6>Modification Vente</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('sales.update', $sale->id) }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="text" name="id" value="{{ $sale->id }}" hidden>
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="prix">Prix par {{ $sale->mot_unite }}</label>
                                            <input type="text" id="prix" value="{{ $sale->prix }}" name="prix" class="form-control">
                                            <span class="error-danger"><strong id="prix-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="quantity">Quantité (en {{ \App\Models\Product::motUnite($sale->unite ?: $sale->product?->unite, 2) }})</label>
                                            <input type="text" id="quantity" value="{{ $sale->quantity }}" name="quantity" class="form-control">
                                            <span class="error-danger"><strong id="quantity-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">Mis à jour</button>
                                        <a href="{{ route('sales.index') }}" class="btn btn-cancel">Annuler</a>
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
