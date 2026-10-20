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
                        <h4>Gestion Transaction</h4>
                        <h5>{{ $customer->mark }}</h5>
                    </div>
                    <div class="page-btn">
                        <a href="/" class="hover:text-[#D4AF37]"><strong>Accueil</strong></a></li> / <a href="{{ route('customers.index') }}"><strong>Clients</strong></a> / <a href="{{ route('paiementsClient.add', $customer->mark) }}"><strong>{{ $customer->mark }}</strong></a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flash')
                        <form action="{{ route('paiementsClient.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="balance" style="text-align: left; font-weight:800px;">Montant A Payer</label>
                                        <input class="form-control" type="text" name="balance" id="balance" value="{{ numberDelimiter($balanceDue ?? 0) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="versement" style="text-align: left; font-weight:800px;">Montant Reçu</label>
                                        <input class="form-control" type="number" name="versement" id="versement" min="0" step="0.01">
                                        @error('versement')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="paid_by" style="text-align: left; font-weight:800px;">Type Paiement</label>
                                        <select class="form-control" name="paid_by" id="paid_by">
                                            <option value="">Selectionner le paiement</option>
                                            <option Value="cash">Cash</option>
                                            <option value="check">Check</option>
                                            <option value="orange money">Orange Money</option>
                                        </select>
                                        @error('paid_by')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group mb-0">
                                        <label for="note" style="text-align: left; font-weight:800px;">Note</label>
                                        <textarea class="form-control" name="note" id="note"></textarea>
                                    </div>
                                    @error('amount_rmb')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <br>
                            <br>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit me-2">Valider</button>
                                    <a href="{{ route('paiementsClient.add', $customer->mark) }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-top">
                            <div class="search-set">
                                <div class="search-input">
                                    <input type="text" id="search-input" class="form-control" placeholder="Search...">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table dataitems">
                                <thead>
                                    <tr>
                                        <th>Date Transaction</th>
                                        <th>Montant</th>
                                        <th>Reste à payer après versement</th>
                                        <th>Description</th>
                                        <th>Reçu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataTable as $item)
                                    <tr>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->versement }}</td>
                                        <td>{{ number_format($item->solde_apres, 0, ',', ' ') }} FG</td>
                                        <td style="width: 100px;
                                                min-width: 100px;
                                                max-width: 100px;
                                                word-break: break-word;
                                                overflow-wrap: anywhere;
                                                white-space: normal;
                                                line-height: 1.3;">
                                            {{ $item->note }}
                                        </td>
                                        <td>
                                            @if ($item->receipt_number)
                                                <a href="{{ route('receipts.transactions.show', $item->id) }}" class="btn btn-sm btn-secondary">
                                                    <img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img">
                                                </a>
                                            @else
                                                N/A
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
