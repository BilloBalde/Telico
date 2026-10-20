<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Journalier;
use App\Models\PaiementJournalier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Registre de caisse de l'entreprise (avances), hors menu et sans magasin :
        // réservé à la vue globale.
        $this->middleware('permission:stores.all');
    }

    public function index(Request $request)
    {
        $query = Journalier::query();
        if ($request->filled('datePrise')) {
            $query->where('datePrise', 'like', '%' .$request->input('datePrise') . '%');
        }
        $dataTable = $query->orderBy('id', 'desc')->paginate(10);
        return view('journaliers.index', compact('dataTable'));
    }
    public function create(){
        $dataTable = Journalier::where('datePrise', today()->toDateString())->paginate(10);
        return view('journaliers.create', compact('dataTable'));
    }
    
    public function store(Request $request){
        $request->validate([
           'nomPrenant' => 'required|string',
           'montant' => 'required|numeric',
           'datePrise' => 'required|date',
           'contenu' => 'required|string'
        ]);
        try{
            Journalier::create([
               'nomPrenant' => $request->nomPrenant,
               'montant' => $request->montant,
               'datePrise' => $request->datePrise,
               'contenu' => $request->contenu,
               'reste' => $request->montant,
            ]);
            
            return redirect()->back()->with('success', 'Dette enregistré avec succès');
        }catch(\Exception $e){
            return redirect()->back()->with('fall', 'Impossible de register cette dette', $e->getMessage());
        }
    }
    
    public function edit($id){
        $dette = Journalier::findOrFail($id);
        return view('journaliers.edit', compact('dette'));
    }
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nomPrenant' => ['required', 'string', 'max:255'],
            'montant'    => ['required', 'numeric', 'min:0'],
            'contenu'    => ['nullable', 'string'],
            'datePrise'  => ['required', 'date'],
        ]);

        $journalier = Journalier::findOrFail($id);

        // Sum of all existing payments → if none, this will be 0
        $totalPaid = PaiementJournalier::where('journalier_id', $journalier->id)
            ->sum('versement'); // = 0 if no payment

        $newMontant = (float) $validated['montant'];

        // If no payment yet → $totalPaid = 0, so no restriction
        // But if payments exist, montant cannot be smaller
        if ($newMontant < $totalPaid) {
            return back()->withErrors([
                'montant' => "Le montant ($newMontant) ne peut pas être inférieur au total déjà versé ($totalPaid)."
            ])->withInput();
        }

        $reste = $newMontant - $totalPaid; // If no payments → reste = montant

        $journalier->update([
            'nomPrenant' => $validated['nomPrenant'],
            'montant'    => $newMontant,
            'contenu'    => $validated['contenu'] ?? $journalier->contenu,
            'paid'       => $totalPaid, // will be 0 if no payments
            'reste'      => $reste,     // full montant if no payments
            'datePrise'  => $validated['datePrise'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Dette mise à jour avec succès.');
    }
    
    public function destroy($id){
        // Wrap in a transaction + row lock to be safe under concurrency
        return DB::transaction(function () use ($id) {
            $journalier = Journalier::lockForUpdate()->findOrFail($id);

            // Check if any payments are linked
            $hasPayments = PaiementJournalier::where('journalier_id', $journalier->id)->exists();

            if ($hasPayments) {
                // Can't delete because FK is RESTRICT; avoid exception and inform the user
                return redirect()
                    ->back()
                    ->with('error', "Suppression impossible : des paiements sont déjà liés à cette dette (#{$journalier->id}). Veuillez d'abord supprimer les paiements.");
            }

            $journalier->delete();

            return redirect()
                ->route('journaliers.index') // adjust to your listing route
                ->with('success', "La dette #{$journalier->id} a été supprimée avec succès.");
        });
    }
    
    public function payForm($id){
        $journalier = Journalier::findOrFail($id);
        return view('journaliers.addPaiement', compact('journalier'));
    }
    
    public function paySubmit(Request $request)
    {
        // Basic validation
        $validated = $request->validate([
            'journalier_id' => ['required', 'integer'],
            'versement'     => ['required', 'string'], // we'll normalize it ourselves
            'paid_by'       => ['required'],
            'notes'         => ['nullable', 'string'],
        ]);
        
        //dd($validated);

        $amountInput = $validated['versement'];
        if ($amountInput <= 0) {
            return back()->withErrors(['versement' => 'Le montant versé doit être supérieur à 0.'])->withInput();
        }

        return DB::transaction(function () use ($validated, $amountInput) {
            // Lock parent to avoid concurrent updates
            $journalier = Journalier::lockForUpdate()->findOrFail($validated['journalier_id']);

            // Recompute totals from paiement_journaliers (source of truth)
            $totalPaidSoFar = (float) PaiementJournalier::where('journalier_id', $journalier->id)->sum('versement');
            $montant        = (float) $journalier->montant;
            $resteAvant     = round($montant - $totalPaidSoFar, 2);

            if ($resteAvant <= 0) {
                // 'journaliers.show' n'existe pas dans routes/web.php (ça produisait une
                // RouteNotFoundException / 500) — 'journaliers.paymentVoir' est la route
                // utilisée par le reste de ce contrôleur pour afficher une dette.
                return redirect()->route('journaliers.paymentVoir', $journalier->id)
                    ->with('error', 'Cette dette est déjà soldée.');
            }

            if ($amountInput > $resteAvant) {
                return back()->withErrors([
                    'versement' => "Le versement ($amountInput) dépasse le reste à payer ($resteAvant)."
                ])->withInput();
            }

            // Create the payment first with temporary reference
            $payment = PaiementJournalier::create([
                'reference'    => 'TEMP', // will update right after we get the id
                'journalier_id'=> $journalier->id,
                'versement'    => $amountInput,
                'paid_by'      => $validated['paid_by'],
                'notes'        => $validated['notes'] ?? '',
            ]);

            // Now that we have an ID, build a stable reference and update it
            $ref = 'PJJ' . Carbon::now()->format('Ym') . '-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
            $payment->update(['reference' => $ref]);

            // Recompute after this payment
            $totalPaidNow = $totalPaidSoFar + $amountInput;
            $resteNow     = round($montant - $totalPaidNow, 2);

            // Update parent
            $journalier->update([
                'paid'  => round($totalPaidNow, 2),
                'reste' => $resteNow < 0 ? 0 : $resteNow,
            ]);

            return redirect()
                ->route('journaliers.paymentVoir', $journalier->id)
                ->with('success', "Paiement enregistré (Réf: {$ref}). Reste à payer: {$journalier->reste}.");
        });
    }
    
    public function list($id){
        $payements = PaiementJournalier::where('journalier_id', $id)->get();
        $journalier = Journalier::findOrFail($id);
        return view('journaliers.showPaiement', compact('payements', 'journalier'));
    }
    
    public function paymentsIndex(){
        $payements = PaiementJournalier::all();
        
        return view('journaliers.paymentList', compact('payements'));
    }
}
