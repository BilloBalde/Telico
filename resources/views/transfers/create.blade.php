<div class="modal fade" id="addTransfer" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau transfert</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('transfers.store') }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="product_id">Produit</label>
                                <select name="product_id" id="product_id" class="form-control" required>
                                    <option value="">Sélectionner le produit</option>
                                    @foreach ($produits as $item)
                                    <option value="{{ $item->id }}" data-unite-pluriel="{{ \App\Models\Product::motUnite($item->unite, 2) }}" {{ old('product_id') == $item->id ? 'selected' : '' }}>{{ $item->libelle }} ({{ $item->sku }}) — {{ $item->unite_libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- Tous les sens sont possibles : entrepôt → entrepôt, entrepôt → magasin,
                             magasin → entrepôt, magasin → magasin. Listes issues de la base. --}}
                        <div class="col-12">
                            <div class="form-group">
                                <label for="from_store_id">Source</label>
                                <select id="from_store_id" name="from_store_id" class="form-control" required>
                                    <option value="">Choisir l'emplacement source</option>
                                    @foreach($boutiques as $boutique)
                                    <option value="{{ $boutique->id }}" {{ old('from_store_id') == $boutique->id ? 'selected' : '' }}>{{ $boutique->full_name }} ({{ $boutique->type_label }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="to_store_id">Destination</label>
                                <select id="to_store_id" name="to_store_id" class="form-control" required>
                                    <option value="">Choisir l'emplacement de destination</option>
                                    @foreach($boutiques as $boutique)
                                    <option value="{{ $boutique->id }}" {{ old('to_store_id') == $boutique->id ? 'selected' : '' }}>{{ $boutique->full_name }} ({{ $boutique->type_label }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if (isset($funds) && $funds->isNotEmpty())
                        <div class="col-12">
                            <div class="form-group">
                                <label for="fund_id">Propriétaire de la marchandise</label>
                                <select id="fund_id" name="fund_id" class="form-control">
                                    <option value="">{{ \App\Models\Fund::OWNER_LABEL }}</option>
                                    @foreach ($funds as $fund)
                                    <option value="{{ $fund->id }}" {{ (string) old('fund_id') === (string) $fund->id ? 'selected' : '' }}>{{ $fund->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">La marchandise garde son propriétaire en changeant d'emplacement.</small>
                            </div>
                        </div>
                        @endif
                        <div class="col-12">
                            <div class="form-group">
                                <label for="quantity">Quantité <span class="text-muted" id="transfertUnite"></span></label>
                                <input type="number" min="1" name="quantity" id="quantity" class="form-control" value="{{ old('quantity') }}" required>
                                <small class="text-muted">Dans l'unité du produit : un produit au carton se transfère en cartons.</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="notes">Commentaire</label>
                                <input type="text" name="notes" id="notes" class="form-control" value="{{ old('notes') }}">
                            </div>
                        </div>
                        @if (auth()->user()->hasPermission('transfers.approve') && auth()->user()->hasPermission('transfers.execute'))
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="execute_now" value="1" id="execute_now">
                                <label class="form-check-label" for="execute_now">
                                    Autoriser et exécuter immédiatement (le stock est déplacé tout de suite)
                                </label>
                            </div>
                        </div>
                        @else
                        <div class="col-12">
                            <p class="text-muted" style="font-size:12px;">
                                Le transfert sera enregistré « en attente » : un responsable doit l'autoriser,
                                puis il sera expédié et réceptionné.
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="submit" class="btn btn-submit">Enregistrer</button>
                    <button type="reset" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Unité du produit choisi, affichée à côté de la quantité (« en cartons »).
    (function () {
        const produit = document.getElementById('product_id');
        const unite = document.getElementById('transfertUnite');
        function rafraichir() {
            const opt = produit.selectedOptions[0];
            unite.textContent = opt && opt.value ? '(en ' + opt.dataset.unitePluriel + ')' : '';
        }
        produit.addEventListener('change', rafraichir);
        if (window.jQuery) jQuery(produit).on('change', rafraichir);
        rafraichir();
    })();
</script>
