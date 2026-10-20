<!DOCTYPE html>
<html lang="fr">
@include('layouts.head')
<body>
    @php
        // Statut réel du transfert : le bon peut être imprimé à chaque étape
        // (il affichait toujours « Transfert confirmé », même en attente ou refusé).
        $couleurs = [
            \App\Models\StockTransfer::PENDING    => ['#FBF6EA', '#8A6A24'],
            \App\Models\StockTransfer::APPROVED   => ['#E6E9D5', '#3E4C21'],
            \App\Models\StockTransfer::IN_TRANSIT => ['#E6E9D5', '#3E4C21'],
            \App\Models\StockTransfer::COMPLETED  => ['#3E4C21', '#FFFFFF'],
            \App\Models\StockTransfer::REJECTED   => ['#FDECEA', '#B3261E'],
            \App\Models\StockTransfer::CANCELLED  => ['#F1F1EC', '#66695A'],
        ];
        [$fondStatut, $texteStatut] = $couleurs[$transfer->status] ?? ['#F1F1EC', '#66695A'];
        $termine = $transfer->status === \App\Models\StockTransfer::COMPLETED;
        $etapes = [
            ['Demandé',      $transfer->requester, $transfer->created_at],
            ['Autorisé',     $transfer->approver,  $transfer->approved_at],
            ['Expédié',      $transfer->shipper,   $transfer->shipped_at],
            ['Réceptionné',  $transfer->receiver,  $transfer->received_at],
        ];
        $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y H:i') : null;
    @endphp
    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <style>
                    .bon-transfert { background: #fff; border: 1px solid #E6E9D5; border-radius: 12px; box-shadow: 0 6px 24px rgba(0,0,0,.08); max-width: 900px; margin: 0 auto; padding: 24px; color: #1C230D; }
                    .bt-entete { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; border-bottom: 2px solid #3E4C21; padding-bottom: 14px; margin-bottom: 16px; }
                    .bt-entete img { height: 64px; }
                    .bt-titre { font-family: 'Cinzel', Georgia, serif; font-size: 20px; font-weight: 700; color: #3E4C21; letter-spacing: 1px; margin: 0; }
                    .bt-ref { font-size: 13px; color: #66695A; text-align: right; line-height: 1.6; }
                    .bt-statut { display: inline-block; padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 13px; }
                    .bt-section { font-weight: 700; color: #3E4C21; font-size: 13px; text-transform: uppercase; letter-spacing: .06em; margin: 18px 0 8px; }
                    .bt-produit { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; background: #F9F9F3; border: 1px solid #E6E9D5; border-radius: 10px; padding: 14px 16px; }
                    .bt-produit .nom { font-size: 16px; font-weight: 700; }
                    .bt-produit .detail { font-size: 13px; color: #66695A; }
                    .bt-quantite { font-size: 22px; font-weight: 800; color: #3E4C21; white-space: nowrap; }
                    .bt-trajet { width: 100%; border-collapse: collapse; }
                    .bt-trajet td { width: 50%; border: 1px solid #E6E9D5; padding: 12px; vertical-align: top; }
                    .bt-trajet .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #8A6A24; font-weight: 700; }
                    .bt-trajet .lieu { font-size: 15px; font-weight: 700; margin-top: 2px; }
                    .bt-etapes { width: 100%; border-collapse: collapse; font-size: 13px; }
                    .bt-etapes th { background: #F4F5EC; text-align: left; padding: 7px 10px; font-weight: 700; }
                    .bt-etapes td { border-bottom: 1px solid #E6E9D5; padding: 7px 10px; }
                    .bt-etapes .attente { color: #9A9C8A; font-style: italic; }
                    .bt-notes { white-space: pre-line; font-size: 13px; background: #F9F9F3; border-left: 3px solid #C7A150; padding: 8px 12px; }
                    .bt-signatures { display: flex; gap: 24px; margin-top: 28px; }
                    .bt-signatures > div { flex: 1; border-top: 1px solid #1C230D; padding-top: 6px; font-size: 12px; text-align: center; min-height: 70px; }
                    .bt-pied { margin-top: 18px; font-size: 11px; color: #9A9C8A; text-align: center; }
                </style>
                <div class="page-header">
                    <div class="page-title">
                        <h4>Bon de transfert de stock</h4>
                        <h6>{{ $transfer->reference ?? $receiptNumber }}</h6>
                    </div>
                    <div class="page-btn">
                        <button id="downloadPdf" class="btn btn-added" title="Télécharger en PDF">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger PDF
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="bon-transfert" id="bonTransfert">
                            <div class="bt-entete">
                                <div style="display:flex;gap:14px;align-items:center;">
                                    @if ($company?->logo)
                                        <img src="{{ asset('companies/' . $company->logo) }}" alt="{{ $company->name }}">
                                    @endif
                                    <div>
                                        <p class="bt-titre">BON DE TRANSFERT</p>
                                        <div style="font-size:13px;color:#66695A;">{{ $company?->name ?? 'TELICO' }}{{ $company?->phone ? ' · ' . $company->phone : '' }}</div>
                                    </div>
                                </div>
                                <div class="bt-ref">
                                    <div>Référence : <strong>{{ $transfer->reference ?? '—' }}</strong></div>
                                    <div>Reçu n° {{ $receiptNumber }}</div>
                                    <div>Créé le {{ $date($transfer->created_at) }}</div>
                                    <div style="margin-top:4px;"><span class="bt-statut" style="background:{{ $fondStatut }};color:{{ $texteStatut }};">{{ $transfer->status_label }}</span></div>
                                </div>
                            </div>

                            <div class="bt-section">Marchandise</div>
                            <div class="bt-produit">
                                <div>
                                    <div class="nom">{{ $transfer->product?->libelle ?? 'Produit #' . $transfer->product_id }}</div>
                                    <div class="detail">
                                        SKU : {{ $transfer->product?->sku ?? '—' }} · Conditionnement : {{ $transfer->unite_detail }}
                                        @if ($transfer->fund_id)
                                            · Propriétaire : {{ \App\Models\Fund::labelFor($transfer->fund_id) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="bt-quantite">{{ $transfer->quantite_affichee }}</div>
                            </div>

                            <div class="bt-section">Trajet</div>
                            <table class="bt-trajet">
                                <tr>
                                    <td>
                                        <div class="lbl">Depuis</div>
                                        <div class="lieu">{{ $transfer->fromStore?->full_name ?? 'N/A' }}</div>
                                        <div class="detail" style="font-size:12px;color:#66695A;">{{ $transfer->fromStore?->type_label }}</div>
                                    </td>
                                    <td>
                                        <div class="lbl">Vers</div>
                                        <div class="lieu">{{ $transfer->toStore?->full_name ?? 'N/A' }}</div>
                                        <div class="detail" style="font-size:12px;color:#66695A;">{{ $transfer->toStore?->type_label }}</div>
                                    </td>
                                </tr>
                            </table>

                            <div class="bt-section">Circuit</div>
                            <table class="bt-etapes">
                                <thead><tr><th>Étape</th><th>Par</th><th>Le</th></tr></thead>
                                <tbody>
                                    @foreach ($etapes as [$etape, $qui, $quand])
                                    <tr>
                                        <td>{{ $etape }}</td>
                                        @if ($quand)
                                            <td>{{ $qui?->name ?? '—' }}</td>
                                            <td>{{ $date($quand) }}</td>
                                        @else
                                            {{-- Étape sans date : à venir si le transfert est en cours ; sinon
                                                 (transfert terminé, refusé, annulé, ou antérieur au circuit) « — ». --}}
                                            <td colspan="2" class="attente">{{ in_array($transfer->status, [\App\Models\StockTransfer::PENDING, \App\Models\StockTransfer::APPROVED, \App\Models\StockTransfer::IN_TRANSIT], true) ? 'En attente' : '—' }}</td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if ($transfer->notes)
                                <div class="bt-section">Notes</div>
                                <div class="bt-notes">{{ $transfer->notes }}</div>
                            @endif

                            @if (! $termine)
                                <p style="margin-top:14px;font-size:13px;color:#8A6A24;">
                                    <strong>Ce transfert n'est pas terminé :</strong> le stock n'est définitivement déplacé qu'à la réception.
                                </p>
                            @endif

                            {{-- Remise et réception physique de la marchandise. --}}
                            <div class="bt-signatures">
                                <div>Remis par (départ)<br>Nom et signature</div>
                                <div>Transporté par<br>Nom et signature</div>
                                <div>Reçu par (arrivée)<br>Nom et signature</div>
                            </div>

                            <div class="bt-pied">Édité le {{ now()->format('d/m/Y H:i') }} par {{ $user?->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    <script>
        document.getElementById('downloadPdf').addEventListener('click', function () {
            // La bibliothèque est chargée en différé par layouts.scripts.
            if (typeof html2pdf === 'undefined') {
                alert('La bibliothèque PDF se charge encore. Réessayez dans quelques secondes.');
                return;
            }
            const element = document.getElementById('bonTransfert');
            html2pdf().set({
                margin: [8, 8, 8, 8],
                filename: @json('Transfert_' . ($transfer->reference ?? $receiptNumber) . '.pdf'),
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.bt-produit', '.bt-signatures'] }
            }).from(element).save();
        });
    </script>
</body>
</html>
