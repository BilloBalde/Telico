{{-- Unité du produit créé depuis un achat : il est acheté, stocké, vendu et
     facturé uniquement dans cette unité. Mêmes règles que la fiche produit. --}}
<div class="form-group">
    <label for="newProductUnite">Unité du produit</label>
    <select id="newProductUnite" class="form-control">
        @foreach (\App\Models\Product::UNITES as $code => $unite)
            <option value="{{ $code }}">{{ $unite['libelle'] }}{{ $code === 'douzaine' ? ' (12 pcs)' : '' }}</option>
        @endforeach
    </select>
</div>
<div class="form-group" data-new-unite="carton" style="display:none;">
    <label for="newProductPcsCarton">Nombre de pièces par carton</label>
    <input type="number" min="2" step="1" id="newProductPcsCarton" class="form-control" placeholder="Ex. 24">
</div>
<div class="form-group" data-new-unite="paquet" style="display:none;">
    <label for="newProductPcsPaquet">Nombre de pièces par paquet</label>
    <input type="number" min="2" step="1" id="newProductPcsPaquet" class="form-control" placeholder="Ex. 10">
</div>
<div class="form-group" data-new-unite="rouleau" style="display:none;">
    <label for="newProductDimension">Dimension du rouleau</label>
    <input type="text" maxlength="60" id="newProductDimension" class="form-control" placeholder="Ex. 100 m × 1,5 mm²">
</div>
<script>
    (function () {
        const select = document.getElementById('newProductUnite');
        function rafraichir() {
            document.querySelectorAll('[data-new-unite]').forEach(function (champ) {
                champ.style.display = champ.dataset.newUnite === select.value ? '' : 'none';
            });
        }
        select.addEventListener('change', rafraichir);
        rafraichir();
    })();
</script>
