{{-- Rapport des ventes tierces (PDF, A4 paysage). Mise en page en tableaux :
     Dompdf ne gère ni flex ni grid. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ventes tierces — {{ $label }}</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1C230D; }
        h1 { font-size: 17px; margin: 0; color: #3E4C21; letter-spacing: 1px; }
        h2 { font-size: 11.5px; margin: 16px 0 6px; color: #3E4C21; border-bottom: 1.5px solid #C7A150; padding-bottom: 3px; }
        .entete td { vertical-align: middle; }
        .entete img { height: 52px; }
        .sous-titre { color: #66695A; font-size: 10px; margin-top: 2px; }
        .meta { text-align: right; color: #66695A; font-size: 9px; line-height: 1.5; }
        table.donnees { width: 100%; border-collapse: collapse; }
        table.donnees th { background: #3E4C21; color: #fff; font-weight: bold; padding: 5px 4px; text-align: left; font-size: 8.5px; }
        table.donnees td { padding: 4px; border-bottom: 1px solid #E6E9D5; }
        table.donnees tr:nth-child(even) td { background: #F9F9F3; }
        table.donnees tfoot td { font-weight: bold; background: #F4F5EC; border-top: 1.5px solid #3E4C21; }
        .n { text-align: right; white-space: nowrap; }
        .rouge { color: #B3261E; font-weight: bold; }
        .vert { color: #3E4C21; font-weight: bold; }
        .cartes { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 10px -6px 4px; }
        .cartes td { border: 1px solid #CED5AE; background: #F9F9F3; padding: 7px 9px; width: 25%; }
        .cartes .t { color: #66695A; font-size: 8.5px; }
        .cartes .v { font-size: 13px; font-weight: bold; color: #3E4C21; margin-top: 2px; }
        .filtres { font-size: 9px; color: #66695A; margin-top: 6px; }
        .pied { margin-top: 14px; font-size: 8px; color: #8A8D7A; text-align: center; }
    </style>
</head>
<body>
    @php
        $fg = fn ($n) => number_format((float) $n, 0, ',', ' ');
        // Logo en base64 : Dompdf résout mal les chemins Windows non ASCII.
        $logo = ($company && $company->logo && file_exists(public_path('companies/'.$company->logo)))
            ? 'data:'.mime_content_type(public_path('companies/'.$company->logo)).';base64,'.base64_encode(file_get_contents(public_path('companies/'.$company->logo)))
            : null;
        $partenaireFiltre = request('partner_id') ? $tousPartenaires->firstWhere('id', (int) request('partner_id'))?->name : null;
        $emplacementFiltre = request('store_id') ? $stores->firstWhere('id', (int) request('store_id'))?->store_name : null;
    @endphp

    <table class="entete" style="width:100%;">
        <tr>
            <td style="width:60px;">@if ($logo)<img src="{{ $logo }}" alt="">@endif</td>
            <td>
                <h1>RAPPORT DES VENTES TIERCES</h1>
                <div class="sous-titre">{{ $company?->name ?? 'TELICO' }} — produits pris chez un partenaire et revendus</div>
            </td>
            <td class="meta">
                Période : {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}<br>
                Édité le {{ now()->format('d/m/Y H:i') }} par {{ auth()->user()?->name }}<br>
                {{ $company?->phone }}{{ $company?->email ? ' · '.$company->email : '' }}
            </td>
        </tr>
    </table>
    @if ($partenaireFiltre || $emplacementFiltre)
        <div class="filtres">
            Filtres : {{ $partenaireFiltre ? 'partenaire '.$partenaireFiltre : '' }}{{ $partenaireFiltre && $emplacementFiltre ? ' · ' : '' }}{{ $emplacementFiltre ? 'emplacement '.$emplacementFiltre : '' }}
        </div>
    @endif

    <table class="cartes">
        <tr>
            <td><div class="t">Ventes tierces (période)</div><div class="v">{{ $fg($totals['ca']) }} FG</div></td>
            <td><div class="t">Dû aux partenaires (période)</div><div class="v">{{ $fg($totals['du']) }} FG</div></td>
            <td><div class="t">Marge (période)</div><div class="v">{{ $fg($totals['marge']) }} FG</div></td>
            @if ($peutRegler)
            <td><div class="t">Reste à payer aux partenaires (tout)</div><div class="v">{{ $fg($totals['solde']) }} FG</div></td>
            @endif
        </tr>
    </table>

    <h2>Par partenaire</h2>
    <table class="donnees">
        <thead>
            <tr>
                <th>Partenaire</th><th>Téléphone</th><th class="n">Lignes</th><th class="n">Quantités</th>
                <th class="n">Ventes</th><th class="n">Dû (période)</th><th class="n">Marge</th>
                @if ($peutRegler)<th class="n">Dû (tout)</th><th class="n">Réglé</th><th class="n">Reste à payer</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($partenaires as $r)
            <tr>
                <td><strong>{{ $r->partner->name }}</strong></td>
                <td>{{ $r->partner->phone }}</td>
                <td class="n">{{ $r->periode->nb ?? 0 }}</td>
                <td class="n">{{ $r->qty }}</td>
                <td class="n">{{ $fg($r->periode->ca ?? 0) }}</td>
                <td class="n">{{ $fg($r->periode->du ?? 0) }}</td>
                <td class="n">{{ $fg($r->periode->marge ?? 0) }}</td>
                @if ($peutRegler)
                <td class="n">{{ $fg($r->du) }}</td>
                <td class="n">{{ $fg($r->regle) }}</td>
                <td class="n {{ $r->solde > 0.01 ? 'rouge' : 'vert' }}">{{ $fg(max(0, $r->solde)) }}</td>
                @endif
            </tr>
            @empty
            <tr><td colspan="{{ $peutRegler ? 10 : 7 }}" style="text-align:center;color:#66695A;">Aucune vente tierce sur la période.</td></tr>
            @endforelse
        </tbody>
        @if ($partenaires->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="n">{{ $fg($totals['ca']) }}</td>
                <td class="n">{{ $fg($totals['du']) }}</td>
                <td class="n">{{ $fg($totals['marge']) }}</td>
                @if ($peutRegler)
                <td class="n">{{ $fg($partenaires->sum('du')) }}</td>
                <td class="n">{{ $fg($partenaires->sum('regle')) }}</td>
                <td class="n">{{ $fg($totals['solde']) }}</td>
                @endif
            </tr>
        </tfoot>
        @endif
    </table>

    <h2>Produits pris chez les partenaires</h2>
    <table class="donnees">
        <thead>
            <tr>
                <th>Date</th><th>Facture</th><th>Vendeur</th><th>Emplacement</th><th>Produit</th><th>Partenaire</th>
                <th class="n">Qté</th><th class="n">Payé / unité</th><th class="n">Vendu / unité</th>
                <th class="n">Dû</th><th class="n">Vente</th><th class="n">Marge</th><th>Client</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($details as $s)
            <tr>
                <td style="white-space:nowrap;">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $s->numeroFacture }}</td>
                <td>{{ $s->user?->name ?? '—' }}</td>
                <td>{{ $s->store?->store_name }}</td>
                <td>{{ $s->product?->libelle ?? 'Produit #'.$s->product_id }}</td>
                <td>{{ $s->partner?->name ?? '—' }}</td>
                <td class="n">{{ $s->quantite_affichee }}</td>
                <td class="n">{{ $fg($s->purchase_price_from_partner) }} / {{ $s->mot_unite }}</td>
                <td class="n">{{ $fg($s->prix) }} / {{ $s->mot_unite }}</td>
                <td class="n">{{ $fg($s->du_partenaire) }}</td>
                <td class="n">{{ $fg($s->prixTotal) }}</td>
                <td class="n">{{ $fg($s->interet) }}</td>
                <td>{{ $customers[$s->facture_customer_id] ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="13" style="text-align:center;color:#66695A;">Aucune ligne.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($peutRegler)
    <h2>Règlements aux partenaires (période)</h2>
    <table class="donnees">
        <thead><tr><th>Date</th><th>Partenaire</th><th class="n">Montant</th><th>Mode</th><th>Note</th><th>Enregistré par</th></tr></thead>
        <tbody>
            @forelse ($reglements as $r)
            <tr>
                <td>{{ $r->paid_at->format('d/m/Y') }}</td>
                <td>{{ $r->partner?->name }}</td>
                <td class="n">{{ $fg($r->amount) }} FG</td>
                <td>{{ $r->mode_label }}</td>
                <td>{{ $r->note }}</td>
                <td>{{ $r->user?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;color:#66695A;">Aucun règlement sur la période.</td></tr>
            @endforelse
        </tbody>
        @if ($reglements->isNotEmpty())
        <tfoot><tr><td colspan="2">Total réglé</td><td class="n">{{ $fg($reglements->sum('amount')) }} FG</td><td colspan="3"></td></tr></tfoot>
        @endif
    </table>
    @endif

    <div class="pied">Document interne — ne pas remettre au client. Montants en francs guinéens (FG).</div>
</body>
</html>
