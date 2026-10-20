{{-- Prix de vente minimum (facultatif), fixé par le gérant, dans l'unité du
     produit. Aucune vente (caisse, facturation, devis) ne peut se faire sous
     le plus haut de ce minimum et du prix de revient : le serveur le refuse. --}}
<div class="col-lg-4 col-sm-6 col-12">
    <div class="form-group">
        <label for="price_sale_minimum">Prix de vente minimum par <span data-unite-singulier>pièce</span> (FG) <small class="text-muted">— facultatif</small></label>
        <input type="text" inputmode="decimal" id="price_sale_minimum" name="price_sale_minimum" class="form-control"
               value="{{ old('price_sale_minimum', isset($product) && $product->price_sale_minimum !== null ? (float) $product->price_sale_minimum : '') }}"
               placeholder="Ex. 55000">
        <small class="text-muted d-block" id="prixMinimumAide">
            Les vendeurs ne pourront pas vendre en dessous. Il doit être supérieur au prix de revient et au plus égal au prix de vente.
        </small>
        <span class="text-danger" id="prixMinimumErreur" style="display:none;"></span>
        @error('price_sale_minimum') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const minimum = document.getElementById('price_sale_minimum');
        const revient = document.getElementById('price_sale');
        const vente = document.getElementById('price');
        const erreur = document.getElementById('prixMinimumErreur');
        if (!minimum || !revient || !vente) return;
        const fg = n => new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FG';

        // Même règle que le serveur : revient < minimum ≤ prix de vente.
        function controler() {
            const m = parseFloat(minimum.value) || 0;
            const r = parseFloat(revient.value) || 0;
            const v = parseFloat(vente.value) || 0;
            let message = '';
            if (m > 0 && r > 0 && m <= r) {
                message = 'Doit être strictement supérieur au prix de revient (' + fg(r) + ').';
            } else if (m > 0 && v > 0 && m > v) {
                message = 'Ne peut pas dépasser le prix de vente (' + fg(v) + ').';
            }
            minimum.setCustomValidity(message);
            minimum.classList.toggle('is-invalid', !!message);
            erreur.textContent = message;
            erreur.style.display = message ? 'block' : 'none';
        }
        [minimum, revient, vente].forEach(el => el.addEventListener('input', controler));
        // Le prix de vente de la création est recalculé par script (+30 %).
        revient.addEventListener('input', () => setTimeout(controler, 0));
        controler();
    });
</script>
