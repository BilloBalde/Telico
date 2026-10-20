<?php

namespace App\Services;

use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Calculs d'un fonds d'associé. Règles convenues avec le client :
 *
 * - coût : coût de revient des achats financés par le fonds (achat + transport
 *   + douane), moyenne pondérée de tous ses achats non annulés, par produit ;
 * - bénéfice constaté à l'ENCAISSEMENT : pour chaque facture, la part payée
 *   par le client est répartie au prorata sur ses lignes ; seule la part
 *   encaissée des lignes de l'associé compte ;
 * - pertes et écarts d'inventaire sur son stock : au coût, partagés selon la
 *   même clé que le bénéfice ;
 * - l'argent des ventes est mis de côté pour rembourser : seuls les apports
 *   non encore investis peuvent financer de nouveaux achats ;
 * - chaque versement à l'associé est ventilé, au choix, entre remboursement
 *   de capital et versement de bénéfice.
 */
class FundService
{
    /** @var array<int, array<int, float>> */
    private array $costCache = [];

    /** Coût de revient unitaire d'un produit pour un fonds (0 si jamais acheté). */
    public function unitCost(int $fundId, int $productId): float
    {
        return $this->unitCosts($fundId)[$productId] ?? 0.0;
    }

    /** @return array<int, float> product_id => coût unitaire moyen */
    public function unitCosts(int $fundId): array
    {
        return $this->costCache[$fundId] ??= DB::table('purchase_order_items as i')
            ->join('purchase_orders as o', 'o.id', '=', 'i.purchase_order_id')
            ->where('o.fund_id', $fundId)
            ->where('o.status', '!=', 'cancelled')
            ->selectRaw('i.product_id, SUM(i.quantity * i.landed_unit_cost_gnf) / NULLIF(SUM(i.quantity), 0) as cost')
            ->groupBy('i.product_id')
            ->pluck('cost', 'product_id')
            ->map(fn ($c) => round((float) $c, 2))
            ->all();
    }

    public function forgetCosts(?int $fundId = null): void
    {
        if ($fundId) {
            unset($this->costCache[$fundId]);
        } else {
            $this->costCache = [];
        }
    }

    /** Total investi en achats (achats non annulés financés par le fonds). */
    public function purchasesTotal(int $fundId): float
    {
        return (float) DB::table('purchase_orders')->where('fund_id', $fundId)->where('status', '!=', 'cancelled')->sum('total_landed_cost_gnf');
    }

    /** Somme des mouvements du compte, par type. */
    public function transactionTotals(int $fundId): array
    {
        $totals = DB::table('fund_transactions')->where('fund_id', $fundId)
            ->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');

        return collect(array_keys(FundTransaction::TYPES))->mapWithKeys(fn ($t) => [$t => (float) ($totals[$t] ?? 0)])->all();
    }

    /**
     * Capital encore disponible pour acheter : apports non investis. L'argent
     * des ventes n'y entre pas (il est mis de côté pour rembourser) ; les
     * sorties du compte sont d'abord payées sur cet argent des ventes, et ne
     * réduisent les apports non investis que pour l'excédent.
     */
    public function capitalAvailable(int $fundId, ?int $exceptPurchaseOrderId = null): float
    {
        $fund = Fund::findOrFail($fundId);
        $s = $this->statement($fund);
        $achatExclu = $exceptPurchaseOrderId
            ? (float) DB::table('purchase_orders')->where('id', $exceptPurchaseOrderId)->where('fund_id', $fundId)
                ->where('status', '!=', 'cancelled')->value('total_landed_cost_gnf')
            : 0.0;

        return round($s['capital_disponible'] + $achatExclu, 2);
    }

    /** Apports non investis, après les sorties excédant l'argent des ventes. */
    private function uninvested(float $apports, float $achats, float $sorties, float $ventesEncaissees): float
    {
        return $apports - $achats - max(0.0, $sorties - $ventesEncaissees);
    }

    /** Relevé complet du fonds, à date. */
    public function statement(Fund $fund): array
    {
        $fundId = $fund->id;
        $t = $this->transactionTotals($fundId);
        $costs = $this->unitCosts($fundId);

        // Ventes des marchandises du fonds, avec la part encaissée de chaque facture.
        $lines = DB::table('sales as s')
            ->join('factures as f', 'f.numero_facture', '=', 's.numeroFacture')
            ->whereNull('f.deleted_at')
            ->where('s.fund_id', $fundId)
            ->select('s.id', 's.numeroFacture', 's.product_id', 's.quantity', 's.unite', 's.prixTotal', 's.unit_cost', 's.created_at',
                'f.montant_total', 'f.reste', 'f.customer_id')
            ->orderBy('s.id')
            ->get();

        $ventesFacturees = 0.0; $coutFacture = 0.0;
        $ventesEncaissees = 0.0; $coutEncaisse = 0.0;
        foreach ($lines as $line) {
            $cout = (float) ($line->unit_cost ?? ($costs[$line->product_id] ?? 0)) * (int) $line->quantity;
            $ratio = (float) $line->montant_total > 0
                ? max(0, min(1, ((float) $line->montant_total - (float) $line->reste) / (float) $line->montant_total))
                : 0;
            $line->cout = $cout;
            $line->ratio = $ratio;
            $ventesFacturees += (float) $line->prixTotal;
            $coutFacture += $cout;
            $ventesEncaissees += (float) $line->prixTotal * $ratio;
            $coutEncaisse += $cout * $ratio;
        }

        // Pertes / écarts d'inventaire sur le stock du fonds, valorisés au coût.
        $ecarts = StockMovement::where('fund_id', $fundId)
            ->whereIn('type', [StockMovement::INVENTORY, StockMovement::ADJUSTMENT])
            ->get(['product_id', 'quantity', 'unit_price'])
            ->sum(fn ($m) => (int) $m->quantity * (float) ($m->unit_price ?? ($costs[$m->product_id] ?? 0)));

        // Stock restant du fonds, tous emplacements confondus.
        $stockRows = DB::table('store_products')->where('fund_id', $fundId)
            ->selectRaw('product_id, SUM(quantity) as qty')->groupBy('product_id')->get();
        $stockQty = (int) $stockRows->sum('qty');
        $stockValue = (float) $stockRows->sum(fn ($r) => (int) $r->qty * ($costs[$r->product_id] ?? 0));

        $achats = $this->purchasesTotal($fundId);
        $beneficeEncaisse = $ventesEncaissees - $coutEncaisse;
        $resultat = $beneficeEncaisse + $ecarts;
        $partAssocie = round($resultat * $fund->associate_share / 100, 2);
        $partPatron = round($resultat * $fund->owner_share / 100, 2);

        $apports = $t[FundTransaction::APPORT];
        $rembourse = $t[FundTransaction::REMBOURSEMENT_CAPITAL];
        $verseBenefice = $t[FundTransaction::VERSEMENT_BENEFICE];
        $preleve = $t[FundTransaction::PRELEVEMENT_PATRON];

        // Ce que le compte à part doit contenir : apports, moins les achats,
        // plus les encaissements des ventes, moins ce qui en est sorti.
        $tresorerie = $apports - $achats + $ventesEncaissees - $rembourse - $verseBenefice - $preleve;

        return [
            'apports'              => $apports,
            'achats'               => $achats,
            'capital_disponible'   => round($this->uninvested($apports, $achats, $rembourse + $verseBenefice + $preleve, $ventesEncaissees), 2),
            'capital_rembourse'    => $rembourse,
            'capital_restant'      => round($apports - $rembourse, 2),
            'stock_qty'            => $stockQty,
            'stock_valeur'         => round($stockValue, 2),
            'ventes_facturees'     => round($ventesFacturees, 2),
            'ventes_encaissees'    => round($ventesEncaissees, 2),
            'reste_a_encaisser'    => round($ventesFacturees - $ventesEncaissees, 2),
            'cout_encaisse'        => round($coutEncaisse, 2),
            'benefice_encaisse'    => round($beneficeEncaisse, 2),
            'benefice_en_attente'  => round(($ventesFacturees - $coutFacture) - $beneficeEncaisse, 2),
            'ecarts_stock'         => round($ecarts, 2),
            'resultat'             => round($resultat, 2),
            'part_associe'         => $partAssocie,
            'part_patron'          => $partPatron,
            'benefice_verse'       => $verseBenefice,
            'benefice_a_verser'    => round($partAssocie - $verseBenefice, 2),
            'part_patron_prelevee' => $preleve,
            'part_patron_restante' => round($partPatron - $preleve, 2),
            'tresorerie'           => round($tresorerie, 2),
            'lignes'               => $lines,
        ];
    }
}
