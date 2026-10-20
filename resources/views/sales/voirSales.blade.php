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
                            <h4>Liste des Ventes</h4>
                            <h6>{{ $numero_facture }}</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('factures.index') }}"><img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img"></a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @include('layouts.flash')
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Quantité</th>
                                            <th>Prix</th>
                                            <th>Prix Total</th>
                                            <th>Interet</th>
                                            <th>Created at</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ligneVentes as $data)
                                        <tr>
                                            <td class="productimgname">
                                                <a href="javascript:void(0);" class="product-img">
                                                    <img src="{{ \App\Models\Product::urlImage($data->produitImage) }}" alt="product">
                                                </a>
                                                <a href="javascript:void(0);">{{ $data->produit }}</a>
                                                @if ($data->is_third_party)<small class="d-block" style="color:#8A6A24;"><i class="fa fa-handshake"></i> Pris chez {{ $data->partner?->name ?? 'un partenaire' }} — payé {{ numberDelimiter($data->purchase_price_from_partner) }} FG / {{ $data->mot_unite }}</small>@endif
                                            </td>
                                            <td>{{ $data->quantite_affichee }}</td>
                                            <td>{{ $data->prix_affiche }}</td>
                                            <td>{{ $data->prixTotal }}</td>
                                            <td>{{ $data->interet }}</td>
                                            <td>{{ $data->created_at }}</td>
                                            <td class="text-end">
                                            @if (App\Models\Facture::where('numero_facture', $data->numeroFacture)->first()->statut == 'non payé')
                                                <a class="me-3" href="{{ route('sales.edit', $data->id) }}">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                </a>
                                                @else
                                                No action
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.scripts')
    </body>
</html>
