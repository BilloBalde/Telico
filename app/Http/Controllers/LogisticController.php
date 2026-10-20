<?php

namespace App\Http\Controllers;

use App\Exports\LogisticsExport;
use App\Models\Logistic;
use App\Models\Product;
use App\Models\Store;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class LogisticController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Frais de transport des achats (hors menu) : mêmes droits que les achats.
        $this->middleware('permission:purchases.view');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Logistic::query();

        if ($request->filled('numeroPurchase')) {
            $query->where('numeroPurchase', 'like', '%' . $request->input('numeroPurchase') . '%');
        }

        if ($request->filled('typeLogistic')) {
            $query->where('typeLogistic', 'like', '%' . $request->input('typeLogistic') . '%');
        }

        if ($request->filled('dateEmis')) {
            $query->where('dateEmis', $request->input('dateEmis'));
        }

        if ($request->filled('dateFournis')) {
            $query->where('dateFournis', $request->input('dateFournis'));
        }

        $dataTable = $query->get();

        // Pass the necessary data to the view, including options for filters
        $dataTable = Logistic::all();
        return view('logistics.index', compact('dataTable'));
    }

    public function exportExcel()
    {
        return Excel::download(new LogisticsExport, 'logistics.xlsx');
    }

    public function exportPDF()
    {
        $logistics = Logistic::all();
        $pdf = PDF::loadView('logistics.pdf', compact('logistics'));

        return $pdf->download('logistics.pdf');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // La vue parcourt $boutiques : sans lui, la liste deroulante restait
        // vide et aucun mouvement ne pouvait etre enregistre.
        $boutiques = Store::all();

        return view('logistics.create', compact('boutiques'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numeroPurchase' => 'required|string|max:255',
            'store_id' => 'required|exists:stores,id',
            'typeLogistic' => 'required|string',
            'quantity' => 'required|numeric',
            'depense' => 'required|numeric',
            'dateEmis' => 'required|date',
            'dateFournis' => 'required|date|after_or_equal:dateEmis',
        ],[
            'numeroPurchase.required' => 'Le champ numeroPurchase est obligatoire, veuillez le remplir',
            'numeroPurchase.string' => 'Le champ numeroPurchase prend une chaine de caractère',
            'store_id.required' => 'Selectionner le stock svp',
            'typeLogistic.required' => 'Veuillez selectionner le type logistic svp',
            'quantity.required' => 'la quantité doit être rempli',
            'quantity.numeric' => 'la quantité doit être une valeure entière',
            'depense.required' => 'la dépense doit être rempli',
            'depense.numeric' => 'la dépense doit être une valeure numérique',
            'dateEmis.required' => 'Veuillez selectionner la date emission',
            'dateFournis.required' => 'Veuillez selectionner la date fournis, elle doit être le même jour ou après la date fournis',
        ]);

        // If validation fails, return a JSON response with errors
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ]);
        }

        try {
            Logistic::create([
                'numeroPurchase' => $request->numeroPurchase,
                'store_id' => $request->store_id,
                'typeLogistic' => $request->typeLogistic,
                'quantity' => $request->quantity,
                'depense' => $request->depense,
                'dateEmis' => $request->dateEmis,
                'dateFournis' => $request->dateFournis
            ]);

            // If the request is expecting JSON, return a JSON success response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logistic crée avec succès.',
                    'redirect_url' => route('purchases.ajout', [$request->numeroPurchase, $request->quantity, $request->store_id])
                ]);
            }

            // Otherwise, redirect back with a success message
            return redirect()->route('purchases.ajout', [$request->numeroPurchase, $request->quantity, $request->store_id])->with([
                'success'=>'Logistic crée avec succès.'
            ]);
        } catch (\Throwable $th) {
            // Handle any other errors
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de l\'ajout. Voici le message : '.$th->getMessage()
                ], 500);
            }

            return back()->with('fall', 'Une erreur est survenue lors de l\'ajout. Voici le message : '.$th->getMessage());
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Logistic  $logistic
     * @return \Illuminate\Http\Response
     */
    public function show(Logistic $logistic)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Logistic  $logistic
     * @return \Illuminate\Http\Response
     */
    public function edit(Logistic $logistic)
    {
        return view('logistics.edit', compact('logistic'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Logistic  $logistic
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Logistic $logistic)
    {
        //dd($logistic);
        try {
            $logistic->depense = $request->depense;
            $logistic->dateEmis = $request->dateEmis;
            $logistic->dateFournis = $request->dateFournis;
            $logistic->save();
            return redirect()->route('logistics.index')->with('success', 'La modification a réussie.');
        } catch (\Throwable $th) {
            return redirect()->route('logistics.index')->with('error', 'La modification a echouée. avec le message '.$th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Logistic  $logistic
     * @return \Illuminate\Http\Response
     */
    public function destroy(Logistic $logistic)
    {
        //
    }
}
