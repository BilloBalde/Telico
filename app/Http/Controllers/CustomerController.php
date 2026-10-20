<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Store;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();
        // Les clients sont communs à tous les magasins, mais un employé rattaché
        // ne voit que le solde de SES factures (comme la page des débiteurs).
        $dataTable = $user->isStoreScoped()
            ? Customer::withSum(['factures as balance_perimetre' => fn ($q) => $q->whereIn('store_id', $user->accessibleStoreIds())], 'reste')->get()
            : Customer::all();
        return view('customers.index', compact('dataTable'));
    }

    public function creditors()
    {
        // whereNull('deleted_at') : DB::table court-circuite le filtre de
        // suppression douce du modèle Facture. Sans lui, les factures mises à la
        // corbeille continuent d'être réclamées aux clients.
        $query = DB::table('factures')
            ->whereNull('factures.deleted_at')
            ->join('customers', 'factures.customer_id', '=', 'customers.id')
            ->join('stores', 'factures.store_id', '=', 'stores.id')
            ->select(
                'factures.store_id',
                'stores.store_name',
                'customers.id as customer_id',
                'customers.customerName',
                'customers.mark',
                'customers.tel',
                'customers.email',
                DB::raw('SUM(factures.reste) as balance')
            )
            ->where('factures.reste', '>', 0)
            ->groupBy(
                'factures.store_id',
                'stores.store_name',
                'customers.id',
                'customers.customerName',
                'customers.mark',
                'customers.tel',
                'customers.email'
            )
            ->orderBy('stores.store_name')
            ->orderByDesc('balance');

        if (auth()->user()->isStoreScoped()) {
            $store = Store::find(auth()->user()->assignedStoreId());
            if (!$store) {
                return redirect('login')->with('error', 'Dites au manager de vous attribuer à une boutique afin de pouvoir continuer');
            }
            $query->where('factures.store_id', $store->id);
        }

        $creditors = $query->get()->groupBy('store_id');

        return view('customers.creditors', compact('creditors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function validation(Request $request){
        $request->validate([
            'customerName'=>'nullable|max:250',
            'tel'=>'nullable|max:18',
            'mark' => 'nullable',
            'address' => 'nullable|string',
            'email' => 'nullable|email',
        ],[
            'customerName.max' => 'champ nom prend maximum 250 charactere.',
            'tel.max' => 'champ tel prend maximum 18 caracteres.',
            'address.string' => 'champ address prend que des caracteres.',
            'email.email' => 'Le champ email doit être un email valide.',
        ]);
    }

    public function store(Request $request)
    {
        $this->validation($request);
        try {
            DB::beginTransaction();
            Customer::updateOrCreate(['id' => $request->id], [
                'customerName' => $request->customerName ?? 'Client sans nom',  // Valeur par défaut
                'tel' => $request->tel ?? 'Non renseigné',
                'address' => $request->address ?? 'Non renseignée',
                'mark' => $request->mark ?? 'N/A',
                'email' => $request->email ?? '',
            ]);
            DB::commit();
            return redirect()->route('customers.index')->with('success', 'Client ajouté avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
        
            \Log::error('Customer creation error: ' . $e->getMessage());
            return redirect()->route('customers.index')->with('error', 'Erreur lors de l\'ajout du client.');
        }
    }

    public function search(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::where('customerName', 'LIKE', "%{$search}%")
                            ->orWhere('mark', 'LIKE', "%{$search}%")
                            ->get(['id', 'customerName', 'mark']);
        if ($customers->isEmpty()) {
            return response()->json(['status' => 'no_results']);
        }

        return response()->json(['status' => 'found', 'customers' => $customers]);
    }

    public function quickAdd(Request $request)
    {
        try {
            $request->validate([
                'customerName' => 'required|string|max:255',
                'mark' => 'nullable|string|max:255',
                'tel' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string'
            ]);

            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark ?? 'N/A',
                'tel' => $request->tel ?? '',
                'email' => $request->email ?? '',
                'address' => $request->address ?? '',
                'fidelite' => 0
            ]);

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'customerName' => $customer->customerName,
                    'mark' => $customer->mark
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $customer = Customer::find($id);
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customerName = $customer->customerName;
            
            // Check if customer can be deleted
            if ($customer->balance > 0) {
                return redirect()->back()
                                ->with('warning', 'Ce client a encore un solde impayé. Impossible de supprimer.');
            }

            if ($customer->factures()->where('statut', '!=', 'payé')->exists()) {
                return redirect()->back()
                                ->with('warning', 'Ce client a des factures impayées. Impossible de supprimer.');
            }
            
            // Soft delete the customer
            $customer->delete();
            
            return redirect()->route('customers.index')
                            ->with('success', "Le client '{$customerName}' a été archivé avec succès.");
                            
        } catch (\Throwable $th) {
            return redirect()->route('customers.index')
                            ->with('error', 'Erreur lors de l\'archivage : ' . $th->getMessage());
        }
    }

    public function restore($id)
{
    try {
        $customer = Customer::withTrashed()->findOrFail($id);
        $customerName = $customer->customerName;
        
        // Restore the customer
        $customer->restore();
        
        return redirect()->route('customers.index')
                        ->with('success', "Le client '{$customerName}' a été restauré avec succès.");
                        
    } catch (\Throwable $th) {
        return redirect()->route('customers.index')
                        ->with('error', 'Erreur lors de la restauration : ' . $th->getMessage());
    }
}
public function forceDelete($id)
{
    try {
        DB::beginTransaction();
        
        $customer = Customer::withTrashed()->findOrFail($id);
        $customerName = $customer->customerName;

        // Cette méthode efface définitivement les factures ET les paiements du
        // client : sans ce contrôle, la protection posée sur la suppression d'une
        // facture encaissée se contournerait en passant par la fiche client.
        // withTrashed : une facture archivée conserve ses paiements, la table
        // `payments` n'ayant pas de suppression douce.
        $encaissees = $customer->factures()->withTrashed()->get()->filter->aRecuUnPaiement();

        if ($encaissees->isNotEmpty()) {
            DB::rollBack();

            return redirect()->back()->with('error', sprintf(
                'Le client « %s » a %d facture(s) déjà encaissée(s) (%s GNF au total). '
                .'Il ne peut pas être supprimé définitivement.',
                $customerName,
                $encaissees->count(),
                number_format($encaissees->sum->montantEncaisse(), 0, ',', ' ')
            ));
        }

        // Get invoice IDs before deleting
        $invoiceIds = $customer->factures()->pluck('id')->toArray();
        
        // Permanently delete related payments
        if (!empty($invoiceIds)) {
            Payment::whereIn('facture_id', $invoiceIds)->delete();
        }
        
        // Permanently delete related invoices
        $customer->factures()->delete();
        
        // Permanently delete the customer
        $customer->forceDelete();
        
        DB::commit();
        
        return redirect()->route('customers.trashed')
                        ->with('success', "Le client '{$customerName}' a été définitivement supprimé.");
                        
    } catch (\Throwable $th) {
        DB::rollBack();
        return redirect()->route('customers.trashed')
                        ->with('error', 'Erreur : ' . $th->getMessage());
    }
}
}
