{{-- Choix de l'emplacement source article par article, lors de l'approbation
     d'une commande. Partagé par la liste et la fiche commande. Le contrôle qui
     fait foi reste celui du serveur (OrderManagementController::approve). --}}
<script>
    window.renderOrderStockCheck = function (data) {
        var html = '';
        $.each(data.items, function (i, item) {
            // Quantités dans l'unité du produit (« cartons »).
            var unite = $('<div>').text(item.unite || 'pièces').html();
            // Présélection : l'emplacement déjà choisi, sinon le premier qui a
            // assez de stock, sinon rien (le vendeur doit choisir).
            var preselect = item.store_id;
            if (!preselect) {
                var suffisant = (item.per_store || []).find(function (s) { return s.available >= item.requested; });
                preselect = suffisant ? suffisant.store_id : '';
            }
            var opts = '<option value="" data-available="-1">— Choisir l’emplacement —</option>';
            $.each(item.per_store, function (j, s) {
                var selected = String(s.store_id) === String(preselect) ? ' selected' : '';
                opts += '<option value="' + s.store_id + '" data-available="' + s.available + '"' + selected + '>' +
                            $('<div>').text(s.store_name).html() + ' — ' + s.available + ' ' + unite + ' dispo' +
                        '</option>';
            });
            html += '<div class="order-line py-2 px-3 mb-2" data-requested="' + item.requested + '" data-unite="' + unite + '" style="border-radius:10px;">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                            '<span style="font-size:13px;font-weight:600;">' + $('<div>').text(item.name).html() +
                                ' <em class="text-muted fw-normal">× ' + item.requested + ' ' + unite + ' demandé' + (item.requested > 1 ? 's' : '') + '</em></span>' +
                            '<span class="line-badge badge"></span>' +
                        '</div>' +
                        '<label class="text-muted mb-0 mt-1" style="font-size:11px;">Emplacement du stock</label>' +
                        '<select class="form-control form-control-sm item-location" data-item-id="' + item.item_id + '">' + opts + '</select>' +
                    '</div>';
        });
        html += '<div class="alert alert-danger py-2 mb-0 mt-2 stock-ko-alert" style="font-size:13px;border-radius:10px;display:none;">' +
                    '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                    'Choisissez, pour chaque produit en rouge, un emplacement qui a assez de stock.' +
                '</div>';
        $('#stockCheckList').html(html);

        return window.refreshOrderLines();
    };

    window.refreshOrderLines = function () {
        var allOk = true;
        $('#stockCheckList .order-line').each(function () {
            var requested = parseInt($(this).data('requested'), 10) || 0;
            var unite = $(this).data('unite') || '';
            var available = parseInt($(this).find('.item-location option:selected').data('available'), 10);
            var choisi = available >= 0;
            var ok = choisi && available >= requested;
            allOk = allOk && ok;
            $(this).css({ background: ok ? '#f0fdf4' : '#fef2f2', borderLeft: '3px solid ' + (ok ? '#22c55e' : '#ef4444') });
            $(this).find('.line-badge')
                .attr('class', 'line-badge badge ' + (ok ? 'badge-success' : 'badge-danger'))
                .text(!choisi ? 'À choisir' : (ok ? available + ' ' + unite + ' dispo' : available + ' dispo / ' + requested + ' ' + unite + ' demandés'));
        });
        $('#stockCheckList .stock-ko-alert').toggle(!allOk);

        return allOk;
    };

    window.collectOrderLocations = function () {
        var locations = {};
        $('#stockCheckList .item-location').each(function () {
            locations[$(this).data('item-id')] = $(this).val();
        });

        return locations;
    };

    /** Charge le stock et affiche une ligne « emplacement » par produit. */
    window.loadOrderLines = function (checkUrl, onDone) {
        $('#stockCheckZone').hide();
        $('#stockCheckLoading').show();
        $.ajax({
            url: checkUrl,
            timeout: 15000,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (data) {
                $('#stockCheckLoading').hide();
                var ok = window.renderOrderStockCheck(data);
                $('#stockCheckZone').show();
                onDone(ok);
            },
            error: function () {
                $('#stockCheckLoading').hide();
                $('#stockCheckList').html('<div class="alert alert-warning py-2" style="font-size:13px;">Impossible de vérifier le stock.</div>');
                $('#stockCheckZone').show();
                onDone(false);
            }
        });
    };
</script>
