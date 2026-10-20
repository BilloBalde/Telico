<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\FundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Associés : leur fonds, leur compte à part et leur relevé.
 *
 * Le patron (associates.manage) gère tout ; un associé (associates.self) ne
 * voit, en lecture seule, que le relevé de son propre fonds.
 */
class FundController extends Controller
{
    public function __construct(private FundService $funds)
    {
        $this->middleware('auth.check');
        $this->middleware('permission:associates.manage')->except(['show', 'me']);
    }

    public function index()
    {
        $funds = Fund::orderByDesc('active')->orderBy('name')->get()
            ->map(fn ($f) => (object) ['fund' => $f, 's' => $this->funds->statement($f)]);

        return view('funds.index', compact('funds'));
    }

    public function create()
    {
        return view('funds.form', ['fund' => new Fund(['owner_share' => 20, 'associate_share' => 80, 'active' => true]), 'associateUsers' => $this->associateUsers(), 'locked' => false]);
    }

    public function store(Request $request)
    {
        $fund = Fund::create($this->validated($request));
        $this->linkUser($fund, $request->input('user_id'));

        return redirect()->route('associates.show', $fund)->with('success', 'Associé « ' . $fund->associate_name . ' » créé.');
    }

    public function edit(Fund $fund)
    {
        return view('funds.form', ['fund' => $fund, 'associateUsers' => $this->associateUsers(), 'locked' => $this->sharesLocked($fund)]);
    }

    public function update(Request $request, Fund $fund)
    {
        $data = $this->validated($request);

        // Clé de partage fixe : elle ne change plus une fois l'activité commencée.
        if ($this->sharesLocked($fund)) {
            unset($data['owner_share'], $data['associate_share']);
        }

        $fund->update($data);
        $this->linkUser($fund, $request->input('user_id'));

        return redirect()->route('associates.show', $fund)->with('success', 'Associé mis à jour.');
    }

    /** Relevé : le patron, ou l'associé lui-même (lecture seule). */
    public function show(Fund $fund)
    {
        $user = auth()->user();
        $canManage = $user->hasPermission('associates.manage');

        if (! $canManage && ! ($user->hasPermission('associates.self') && (int) $user->fund_id === (int) $fund->id)) {
            abort(403, 'Ce relevé ne vous est pas accessible.');
        }

        $s = $this->funds->statement($fund);
        $transactions = $fund->transactions()->with('user:id,name')->get();
        $purchases = PurchaseOrder::where('fund_id', $fund->id)->with(['store:id,store_name', 'supplier:id,name'])->latest('date_emis')->get();
        $productNames = DB::table('products')->whereIn('id', collect($s['lignes'])->pluck('product_id')->unique())->pluck('libelle', 'id');
        $stock = DB::table('store_products as sp')
            ->join('products as p', 'p.id', '=', 'sp.product_id')
            ->join('stores as st', 'st.id', '=', 'sp.store_id')
            ->where('sp.fund_id', $fund->id)->where('sp.quantity', '!=', 0)
            ->select('p.id as product_id', 'p.libelle', 'p.unite', 'st.store_name', 'sp.quantity')
            ->orderBy('p.libelle')->get();
        $costs = $this->funds->unitCosts($fund->id);

        return view('funds.show', compact('fund', 's', 'transactions', 'purchases', 'productNames', 'stock', 'costs', 'canManage'));
    }

    /** Raccourci de l'associé connecté vers son relevé. */
    public function me()
    {
        $user = auth()->user();
        if (! $user->fund_id) {
            abort(403, 'Votre compte n\'est rattaché à aucun fonds d\'associé.');
        }

        return redirect()->route('associates.show', $user->fund_id);
    }

    /**
     * Mouvement du compte à part :
     *  - apport : l'associé apporte du capital ;
     *  - versement : à l'associé, ventilé au choix entre remboursement de
     *    capital et versement de bénéfice ;
     *  - prélèvement : le patron prend sa part du bénéfice.
     */
    public function storeTransaction(Request $request, Fund $fund)
    {
        $data = $request->validate([
            'kind'      => 'required|in:apport,versement,prelevement',
            'date'      => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes'     => 'nullable|string|max:500',
            'amount'    => 'nullable|numeric|min:0',
            'capital'   => 'nullable|numeric|min:0',
            'benefice'  => 'nullable|numeric|min:0',
        ]);

        $s = $this->funds->statement($fund);
        $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' FG';
        $rows = [];

        if ($data['kind'] === 'apport') {
            $montant = (float) ($data['amount'] ?? 0);
            if ($montant <= 0) {
                throw ValidationException::withMessages(['amount' => 'Indiquez le montant de l\'apport.']);
            }
            $rows[] = [FundTransaction::APPORT, $montant];
        } elseif ($data['kind'] === 'versement') {
            $capital = (float) ($data['capital'] ?? 0);
            $benefice = (float) ($data['benefice'] ?? 0);
            if ($capital + $benefice <= 0) {
                throw ValidationException::withMessages(['capital' => 'Indiquez la part capital et / ou la part bénéfice du versement.']);
            }
            if ($capital > $s['capital_restant'] + 0.01) {
                throw ValidationException::withMessages(['capital' => 'Capital restant dû : ' . $fmt($s['capital_restant']) . '.']);
            }
            if ($benefice > $s['benefice_a_verser'] + 0.01) {
                throw ValidationException::withMessages(['benefice' => 'Bénéfice encaissé restant à verser à l\'associé : ' . $fmt($s['benefice_a_verser']) . '.']);
            }
            if ($capital + $benefice > $s['tresorerie'] + 0.01) {
                throw ValidationException::withMessages(['capital' => 'Le compte de l\'associé ne contient que ' . $fmt($s['tresorerie']) . '.']);
            }
            if ($capital > 0) {
                $rows[] = [FundTransaction::REMBOURSEMENT_CAPITAL, $capital];
            }
            if ($benefice > 0) {
                $rows[] = [FundTransaction::VERSEMENT_BENEFICE, $benefice];
            }
        } else {
            $montant = (float) ($data['amount'] ?? 0);
            if ($montant <= 0 || $montant > $s['part_patron_restante'] + 0.01) {
                throw ValidationException::withMessages(['amount' => 'Part du patron disponible : ' . $fmt(max(0, $s['part_patron_restante'])) . '.']);
            }
            if ($montant > $s['tresorerie'] + 0.01) {
                throw ValidationException::withMessages(['amount' => 'Le compte de l\'associé ne contient que ' . $fmt($s['tresorerie']) . '.']);
            }
            $rows[] = [FundTransaction::PRELEVEMENT_PATRON, $montant];
        }

        DB::transaction(function () use ($rows, $fund, $data) {
            $reference = ($data['reference'] ?? null) ?: ('MVT-' . $fund->id . '-' . now()->format('YmdHis'));
            foreach ($rows as [$type, $montant]) {
                FundTransaction::create([
                    'fund_id'   => $fund->id,
                    'type'      => $type,
                    'amount'    => round($montant, 2),
                    'date'      => $data['date'],
                    'reference' => $reference,
                    'notes'     => $data['notes'] ?? null,
                    'user_id'   => auth()->id(),
                ]);
            }
        });

        return redirect()->route('associates.show', $fund)->with('success', 'Mouvement enregistré.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'associate_name'  => 'required|string|max:255',
            'phone'           => 'nullable|string|max:40',
            'owner_share'     => 'required|numeric|min:0|max:100',
            'associate_share' => 'required|numeric|min:0|max:100',
            'notes'           => 'nullable|string|max:1000',
            'user_id'         => 'nullable|exists:users,id',
        ]);

        if (abs($data['owner_share'] + $data['associate_share'] - 100) > 0.001) {
            throw ValidationException::withMessages(['owner_share' => 'Les deux parts doivent faire 100 % au total.']);
        }

        unset($data['user_id']);
        $data['active'] = $request->boolean('active', true);

        return $data;
    }

    private function sharesLocked(Fund $fund): bool
    {
        return $fund->exists && ($fund->transactions()->exists() || $fund->sales()->exists() || $fund->purchaseOrders()->exists());
    }

    private function associateUsers()
    {
        return User::whereHas('roleRelation', fn ($q) => $q->where('slug', 'associe'))->orderBy('name')->get(['id', 'name', 'email', 'fund_id']);
    }

    /** Rattache (ou détache) le compte utilisateur de l'associé à ce fonds. */
    private function linkUser(Fund $fund, $userId): void
    {
        User::where('fund_id', $fund->id)->when($userId, fn ($q) => $q->where('id', '!=', $userId))->update(['fund_id' => null]);

        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->isAssociate()) {
                $user->forceFill(['fund_id' => $fund->id])->save();
            }
        }
    }
}
