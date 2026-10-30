<?php

namespace App\Services;

use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Calculs d'un fonds d'associé. Règles convenues avec le client :
 *
 * - achat : seul le prix des marchandises est prélevé sur le compte de
 *   l'associé ; le transport, la douane et les frais divers sont payés par le
 *   patron et inscrits comme dette de l'associé envers lui ;
 * - coût de l'associé : prix d'achat des marchandises financées par le fonds,
 *   moyenne pondérée de tous ses achats non annulés, par produit (les frais
 *   n'y entrent pas : l'associé les rembourse par sa dette, pas deux fois) ;
 * - plancher de vente : prix de revient complet (achat + frais), pour ne
 *   jamais vendre à perte ;
 * - bénéfice constaté à l'ENCAISSEMENT : pour chaque facture, la part payée
 *   par le client est répartie au prorata sur ses lignes ; seule la part
 *   encaissée des lignes de l'associé compte ;
 * - pertes et écarts d'inventaire sur son stock : au coût, partagés selon la
 *   même clé que le bénéfice ;
 * - l'argent des ventes est mis de côté pour rembourser : seuls les apports
 *   non encore investis peuvent financer de nouveaux achats ;
 * - chaque versement à l'associé est ventilé, au choix, entre remboursement
 *   de capital et versement de bénéfice ; le patron peut en retenir une part
 *   pour se rembourser les frais avancés. À la fin, l'associé reçoit son
 *   capital plus sa part du bénéfice, moins ses dettes.
 */
class FundService
{
    /** @var array<int, array{achat: array<int, float>, revient: array<int, float>}> */
    private array $costCache = [];

    /** Prix d'achat unitaire (marchandise seule) d'un produit pour un fonds (0 si jamais acheté). */
    public function unitCost(int $fundId, int $productId): float
    {
        return $this->unitCosts($fundId)[$productId] ?? 0.0;
    }

    /** @return array<int, float> product_id => prix d'achat unitaire moyen */
    public function unitCosts(int $fundId): array
    {
        return $this->costs($fundId)['achat'];
    }

    /**
     * Prix de revient unitaire (achat + transport, douane, frais) d'un produit
     * pour un fonds : plancher sous lequel ses marchandises ne se vendent pas.
     */
    public function landedUnitCost(int $fundId, int $productId): float
    {
        return $this->costs($fundId)['revient'][$productId] ?? 0.0;
    }

    /** @return array{achat: array<int, float>, revient: array<int, float>} */
    private function costs(int $fundId): array
    {
        if (! isset($this->costCache[$fundId])) {
            $rows = DB::table('purchase_order_items as i')
                ->join('purchase_orders as o', 'o.id', '=', 'i.purchase_order_id')
                ->where('o.fund_id', $fundId)
                ->where('o.status', '!=', 'cancelled')
                ->selectRaw('i.product_id,
                    SUM(i.quantity * i.unit_price_gnf) / NULLIF(SUM(i.quantity), 0) as achat,
                    SUM(i.quantity * i.landed_unit_cost_gnf) / NULLIF(SUM(i.quantity), 0) as revient')
                ->groupBy('i.product_id')
                ->get();

            $this->costCache[$fundId] = [
                'achat'   => $rows->mapWithKeys(fn ($r) => [$r->product_id => round((float) $r->achat, 2)])->all(),
                'revient' => $rows->mapWithKeys(fn ($r) => [$r->product_id => round((float) $r->revient, 2)])->all(),
            ];
        }

        return $this->costCache[$fundId];
    }

    public function forgetCosts(?int $fundId = null): void
    {
        if ($fundId) {
            unset($this->costCache[$fundId]);
        } else {
            $this->costCache = [];
        }
    }

    /**
     * Achats non annulés du fonds : [marchandises, frais]. Les marchandises
     * sont payées par le compte de l'associé ; les frais (transport, douane,
     * frais divers) par le patron, qui les inscrit en dette de l'associé.
     *
     * @return array{0: float, 1: float}
     */
    public function purchaseTotals(int $fundId): array
    {
        $row = DB::table('purchase_orders')->where('fund_id', $fundId)->where('status', '!=', 'cancelled')
            ->selectRaw('SUM(COALESCE(total_landed_cost_gnf, 0)) as revient,
                SUM(COALESCE(transport_cost_gnf, 0) + COALESCE(customs_cost_gnf, 0) + COALESCE(other_fees_gnf, 0)) as frais')
            ->first();
        $frais = (float) ($row->frais ?? 0);

        return [round((float) ($row->revient ?? 0) - $frais, 2), round($frais, 2)];
    }

    /** Marchandises achetées avec l'argent du fonds (hors frais avancés par le patron). */
    public function purchasesTotal(int $fundId): float
    {
        return $this->purchaseTotals($fundId)[0];
    }

    /** Somme des mouvements du compte, par type. */
    public function transactionTotals(int $fundId): array
    {
        $totals = DB::table('fund_transactions')->where('fund_id', $fundId)
            ->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');

        return collect(array_keys(FundTransaction::TYPES))->mapWithKeys(fn ($t) => [$t => (float) ($totals[$t] ?? 0)])->all();
    }

    /**
     * Capital encore disponible pour acheter des marchandises : apports non
     * investis. L'argent des ventes n'y entre pas (il est mis de côté pour
     * rembourser) ; les sorties du compte sont d'abord payées sur cet argent
     * des ventes, et ne réduisent les apports non investis que pour l'excédent.
     */
    public function capitalAvailable(int $fundId, ?int $exceptPurchaseOrderId = null): float
    {
        $fund = Fund::findOrFail($fundId);
        $s = $this->statement($fund);
        $achatExclu = $exceptPurchaseOrderId
            ? (float) PurchaseOrder::whereKey($exceptPurchaseOrderId)->where('fund_id', $fundId)
                ->where('status', '!=', 'cancelled')->first()?->montant_marchandises_gnf
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

        [$achats, $fraisAvances] = $this->purchaseTotals($fundId);
        $beneficeEncaisse = $ventesEncaissees - $coutEncaisse;
        $resultat = $beneficeEncaisse + $ecarts;
        $partAssocie = round($resultat * $fund->associate_share / 100, 2);
        $partPatron = round($resultat * $fund->owner_share / 100, 2);

        $apports = $t[FundTransaction::APPORT];
        $rembourse = $t[FundTransaction::REMBOURSEMENT_CAPITAL];
        $verseBenefice = $t[FundTransaction::VERSEMENT_BENEFICE];
        $preleve = $t[FundTransaction::PRELEVEMENT_PATRON];
        $fraisRetenus = $t[FundTransaction::RETENUE_FRAIS];

        // Ce que le compte à part doit contenir : apports, moins les marchandises
        // achetées, plus les encaissements des ventes, moins ce qui en est sorti.
        // Les frais retenus font partie des versements : pas de sortie en plus.
        $tresorerie = $apports - $achats + $ventesEncaissees - $rembourse - $verseBenefice - $preleve;

        $capitalRestant = round($apports - $rembourse, 2);
        $beneficeAVerser = round($partAssocie - $verseBenefice, 2);
        $dettes = round($fraisAvances - $fraisRetenus, 2);

        return [
            'apports'              => $apports,
            'achats'               => $achats,
            'capital_disponible'   => round($this->uninvested($apports, $achats, $rembourse + $verseBenefice + $preleve, $ventesEncaissees), 2),
            'capital_rembourse'    => $rembourse,
            'capital_restant'      => $capitalRestant,
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
            'benefice_a_verser'    => $beneficeAVerser,
            'part_patron_prelevee' => $preleve,
            'part_patron_restante' => round($partPatron - $preleve, 2),
            // Frais des achats payés par le patron : dette de l'associé.
            'frais_avances'        => $fraisAvances,
            'frais_retenus'        => $fraisRetenus,
            'dettes'               => $dettes,
            // Ce que l'associé doit recevoir à la fin : capital + bénéfice − dettes.
            'du_a_l_associe'       => round($capitalRestant + $beneficeAVerser, 2),
            'net_a_verser'         => round($capitalRestant + $beneficeAVerser - $dettes, 2),
            'tresorerie'           => round($tresorerie, 2),
            'lignes'               => $lines,
        ];
    }
}
