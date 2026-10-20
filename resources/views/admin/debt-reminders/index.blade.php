<!DOCTYPE html>
<html lang="fr">
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
                            <h4>Rappels de dettes</h4>
                            <h6>Clients avec dettes ou factures impayées — envoyez les rappels via WhatsApp</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Téléphone</th>
                                            <th>Total dû</th>
                                            <th>Éléments impayés</th>
                                            <th>Rappels envoyés</th>
                                            <th>Dernier envoi</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reminders as $r)
                                        @php
                                            $tel = preg_replace('/\D/', '', $r->customer?->tel ?? '');
                                            $telOk = strlen($tel) >= 8;
                                            $waNumber = str_starts_with($tel, '224') ? $tel : '224' . $tel;
                                            $waText = rawurlencode(
                                                'Bonjour ' . ($r->customer?->customerName ?? 'cher client')
                                                . ', vous avez une facture impayée de '
                                                . number_format($r->total, 0, ',', ' ')
                                                . ' GNF chez TELICO. Veuillez régler votre dette avant lundi. Merci de votre confiance.'
                                            );
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $r->customer?->customerName ?? 'Client #'.$r->customer_id }}</strong>
                                                @if ($r->customer?->trashed())<span class="badge bg-secondary ms-1">archivé</span>@endif
                                                <small class="d-block text-muted">{{ $r->customer?->mark ?? '' }}</small>
                                            </td>
                                            <td>{{ $r->customer?->tel ?? 'N/A' }}</td>
                                            <td><strong style="color:#3E4C21;">{{ number_format($r->total, 0, ',', ' ') }} GNF</strong></td>
                                            <td>{{ $r->count }}</td>
                                            <td>{{ $r->max_reminders }}</td>
                                            <td>{{ $r->last_sent_at ? \Carbon\Carbon::parse($r->last_sent_at)->format('d/m/Y H:i') : '—' }}</td>
                                            <td style="white-space:nowrap;">
                                                @if($telOk)
                                                <a href="https://wa.me/{{ $waNumber }}?text={{ $waText }}" target="_blank" rel="noopener"
                                                   class="btn btn-sm" style="background:#25D366;color:#fff;">
                                                    <i class="fab fa-whatsapp me-1"></i>WhatsApp
                                                </a>
                                                {{-- Aucun fournisseur SMS sur ce projet : le bouton ne pourrait
                                                     qu'échouer, il est masqué. Le lien WhatsApp ci-dessus ne dépend
                                                     d'aucun fournisseur et reste disponible. --}}
                                                @if(\App\Services\SmsService::fournisseurConfigure())
                                                <form method="POST" action="{{ route('admin.debt-reminders.send-sms', $r->customer_id) }}" class="d-inline"
                                                      onsubmit="return confirm('Envoyer un SMS de rappel à {{ $r->customer?->customerName }} ?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm" style="background:#3E4C21;color:#fff;">
                                                        <i class="fas fa-sms me-1"></i>SMS
                                                    </button>
                                                </form>
                                                @endif
                                                @else
                                                <span class="badge bg-secondary" title="Numéro invalide">Tél. invalide</span>
                                                @endif
                                                <form method="POST" action="{{ route('admin.debt-reminders.resolve', $r->customer_id) }}" class="d-inline"
                                                      onsubmit="return confirm('Marquer tous les rappels de ce client comme résolus ?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Résolu</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                Aucun rappel de dette en attente. Lancez <code>php artisan debt:send-reminders</code> ou attendez le passage planifié (samedi 18h).
                                            </td>
                                        </tr>
                                        @endforelse
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
