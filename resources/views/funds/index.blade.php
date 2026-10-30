@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Associés</h4>
            <h6>Argent, marchandises et résultat de chaque associé, séparés des fonds propres</h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('associates.create') }}" class="btn btn-added"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Nouvel associé</a>
        </div>
    </div>
    @include('layouts.flash')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Associé</th>
                            <th class="text-end">Capital restant dû</th>
                            <th class="text-end">Stock (valeur au coût)</th>
                            <th class="text-end">Bénéfice encaissé</th>
                            <th class="text-end">Part associé à verser</th>
                            <th class="text-end">Dettes (frais avancés)</th>
                            <th class="text-end">Net à remettre</th>
                            <th class="text-end">Part patron restante</th>
                            <th class="text-end">Compte associé</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($funds as $row)
                        @php $s = $row->s; @endphp
                        <tr>
                            <td>
                                <strong>{{ $row->fund->name }}</strong>
                                <small class="d-block text-muted">{{ $row->fund->associate_name }} · partage {{ rtrim(rtrim(number_format($row->fund->owner_share, 2, ',', ''), '0'), ',') }} % patron / {{ rtrim(rtrim(number_format($row->fund->associate_share, 2, ',', ''), '0'), ',') }} % associé</small>
                                @unless ($row->fund->active)<span class="badge bg-secondary">Inactif</span>@endunless
                            </td>
                            <td class="text-end">{{ numberDelimiter($s['capital_restant']) }}</td>
                            <td class="text-end">{{ numberDelimiter($s['stock_valeur']) }}</td>
                            <td class="text-end {{ $s['resultat'] < 0 ? 'text-danger' : 'text-success' }}">{{ numberDelimiter($s['resultat']) }}</td>
                            <td class="text-end">{{ numberDelimiter($s['benefice_a_verser']) }}</td>
                            <td class="text-end">{{ numberDelimiter($s['dettes']) }}</td>
                            <td class="text-end fw-semibold">{{ numberDelimiter($s['net_a_verser']) }}</td>
                            <td class="text-end">{{ numberDelimiter($s['part_patron_restante']) }}</td>
                            <td class="text-end fw-semibold">{{ numberDelimiter($s['tresorerie']) }}</td>
                            <td class="text-end"><a href="{{ route('associates.show', $row->fund) }}" class="btn btn-sm btn-primary">Relevé</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Aucun associé. Créez-en un pour suivre son argent séparément.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-muted mb-0" style="font-size:12px;">Montants en FG. Le bénéfice est compté à l'encaissement : une vente à crédit n'entre dans le résultat qu'au fur et à mesure des paiements du client. Seul le prix des marchandises est prélevé sur le compte d'un associé ; les frais de ses achats (transport, douane, frais) sont avancés par le patron et forment sa dette. Net à remettre = capital restant + bénéfice à verser − dettes.</p>
        </div>
    </div>
</div>
@endsection
