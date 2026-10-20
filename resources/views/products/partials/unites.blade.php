{{-- Unité unique du produit. Le stock, les prix, les achats, les ventes et
     les factures du produit sont tous comptés dans cette unité. --}}
@php
    $uniteChoisie = old('unite', isset($product) ? $product->unite : 'pcs');
@endphp
<div class="form-section" id="uniteProduit">
    <div class="form-section-title"><i class="fa fa-boxes"></i> Unité du produit</div>
    <p class="text-muted" style="font-size:13px;margin-bottom:10px;">
        Le produit est acheté, stocké, vendu et facturé <strong>uniquement</strong> dans cette unité.
        @isset($product)
            Changer l'unité ne convertit pas le stock existant : corrigez le stock ci-dessous si besoin.
        @endisset
    </p>
    <div class="d-flex flex-wrap gap-3 mb-3">
        @foreach (\App\Models\Product::UNITES as $code => $unite)
            <label class="form-check-label" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="radio" class="form-check-input unite-radio" name="unite" value="{{ $code }}"
                       data-singulier="{{ mb_strtolower($unite['libelle']) }}" data-pluriel="{{ $unite['pluriel'] }}"
                       style="margin:0;" {{ $uniteChoisie === $code ? 'checked' : '' }} required>
                {{ $unite['libelle'] }}{{ $code === 'douzaine' ? ' (12 pcs)' : '' }}
            </label>
        @endforeach
    </div>
    @error('unite') <div class="text-danger mb-2">{{ $message }}</div> @enderror
    <div class="row">
        <div class="col-lg-4 col-sm-6 col-12" data-unite-champ="carton">
            <div class="form-group">
                <label for="pcs_par_carton">Nombre de pièces par carton</label>
                <input type="number" min="2" step="1" id="pcs_par_carton" name="pcs_par_carton" class="form-control"
                       value="{{ old('pcs_par_carton', $product->pcs_par_carton ?? '') }}" placeholder="Ex. 24">
                @error('pcs_par_carton') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12" data-unite-champ="paquet">
            <div class="form-group">
                <label for="pcs_par_paquet">Nombre de pièces par paquet</label>
                <input type="number" min="2" step="1" id="pcs_par_paquet" name="pcs_par_paquet" class="form-control"
                       value="{{ old('pcs_par_paquet', $product->pcs_par_paquet ?? '') }}" placeholder="Ex. 10">
                @error('pcs_par_paquet') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12" data-unite-champ="rouleau">
            <div class="form-group">
                <label for="dimension_rouleau">Dimension du rouleau</label>
                <input type="text" maxlength="60" id="dimension_rouleau" name="dimension_rouleau" class="form-control"
                       value="{{ old('dimension_rouleau', $product->dimension_rouleau ?? '') }}" placeholder="Ex. 100 m × 1,5 mm²">
                @error('dimension_rouleau') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const bloc = document.getElementById('uniteProduit');
        const radios = bloc.querySelectorAll('.unite-radio');
        function rafraichir() {
            const choisi = Array.from(radios).find(r => r.checked) || radios[0];
            bloc.querySelectorAll('[data-unite-champ]').forEach(function (champ) {
                const actif = champ.dataset.uniteChamp === choisi.value;
                champ.style.display = actif ? '' : 'none';
                champ.querySelector('input').required = actif;
            });
            // Libellés du formulaire qui suivent l'unité : « Stock initial (cartons) »,
            // « Prix de revient par carton ».
            document.querySelectorAll('[data-unite-singulier]').forEach(el => el.textContent = choisi.dataset.singulier);
            document.querySelectorAll('[data-unite-pluriel]').forEach(el => el.textContent = choisi.dataset.pluriel);
        }
        radios.forEach(r => r.addEventListener("change", rafraichir));
        // Les libellés de stock et de prix sont plus bas dans la page.
        document.addEventListener("DOMContentLoaded", rafraichir);
        rafraichir();
    })();
</script>
