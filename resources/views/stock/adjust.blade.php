@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Inventaire / ajustement de stock</h4>
            <h6>Saisissez la quantité réellement comptée : l'écart est enregistré dans le journal avec votre nom et le motif</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form method="POST" action="{{ route('stock.adjust') }}" class="row">
                @csrf
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Emplacement</label>
                        <select name="store_id" id="ajustEmplacement" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach ($stores as $s)
                            <option value="{{ $s->id }}" {{ (string) old('store_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->full_name }} ({{ $s->type_label }})</option>
                            @endforeach
                        </select>
                        @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Produit</label>
                        <select name="product_id" id="ajustProduit" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach ($products as $p)
                            <option value="{{ $p->id }}" data-singulier="{{ \App\Models\Product::motUnite($p->unite) }}" data-pluriel="{{ \App\Models\Product::motUnite($p->unite, 2) }}" {{ (string) old('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->libelle }} ({{ $p->sku }}) — {{ $p->unite_libelle }}</option>
                            @endforeach
                        </select>
                        @error('product_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-2 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Quantité comptée <span class="text-muted" id="ajustUnite"></span></label>
                        <input type="number" min="0" name="counted" id="ajustCompte" value="{{ old('counted') }}" class="form-control" required>
                        @error('counted') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-2 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Nature</label>
                        <select name="type" class="form-control">
                            <option value="INVENTORY">Inventaire</option>
                            <option value="ADJUSTMENT" {{ old('type') === 'ADJUSTMENT' ? 'selected' : '' }}>Ajustement (casse, perte…)</option>
                        </select>
                    </div>
                </div>
                @if ($funds->isNotEmpty())
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Propriétaire du stock compté</label>
                        <select name="fund_id" id="ajustFonds" class="form-control">
                            <option value="">{{ \App\Models\Fund::OWNER_LABEL }}</option>
                            @foreach ($funds as $f)
                            <option value="{{ $f->id }}" {{ (string) old('fund_id') === (string) $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                {{-- Stock enregistré et écart, calculés pendant la saisie, dans l'unité du produit. --}}
                <div class="col-12" id="ajustApercu" style="display:none;">
                    <div class="alert alert-light border mb-3" style="font-size:14px;">
                        Stock enregistré : <strong id="ajustAvant"></strong>
                        <span id="ajustEcartBloc"> · Écart : <strong id="ajustEcart"></strong></span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label>Motif (obligatoire)</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" required placeholder="Ex. : inventaire mensuel, 3 sacs déchirés…">
                        @error('reason') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        const stock = @json($stockEnregistre);
        const emplacement = document.getElementById('ajustEmplacement');
        const produit = document.getElementById('ajustProduit');
        const fonds = document.getElementById('ajustFonds');
        const compte = document.getElementById('ajustCompte');
        const fmt = n => Number(n).toLocaleString('fr-FR');
        function enUnite(n, opt) {
            return fmt(n) + ' ' + (Math.abs(n) > 1 ? opt.dataset.pluriel : opt.dataset.singulier);
        }
        function rafraichir() {
            const opt = produit.selectedOptions[0];
            document.getElementById('ajustUnite').textContent = opt && opt.value ? '(en ' + opt.dataset.pluriel + ')' : '';
            const apercu = document.getElementById('ajustApercu');
            if (!opt || !opt.value || !emplacement.value) { apercu.style.display = 'none'; return; }
            const avant = stock[emplacement.value + '|' + opt.value + '|' + (fonds ? fonds.value : '')] || 0;
            document.getElementById('ajustAvant').textContent = enUnite(avant, opt);
            const saisi = compte.value === '' ? null : parseInt(compte.value, 10);
            document.getElementById('ajustEcartBloc').style.display = saisi === null ? 'none' : '';
            if (saisi !== null) {
                const ecart = saisi - avant;
                const el = document.getElementById('ajustEcart');
                el.textContent = ecart === 0 ? 'aucun' : (ecart > 0 ? '+' : '−') + enUnite(Math.abs(ecart), opt);
                el.className = ecart < 0 ? 'text-danger' : (ecart > 0 ? 'text-success' : '');
            }
            apercu.style.display = '';
        }
        [emplacement, produit, fonds, compte].forEach(el => el && el.addEventListener('input', rafraichir));
        [emplacement, produit, fonds].forEach(el => el && el.addEventListener('change', rafraichir));
        rafraichir();
    })();
</script>
@endsection
