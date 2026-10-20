<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Payment;
use App\Models\StockTransfer;
use App\Models\TransactionFacture;
use App\Models\Sale;
use App\Models\User;

class ReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function payment(Payment $payment)
    {
        // Même règle que les factures : un employé rattaché à un magasin ne voit
        // que les reçus des factures de son périmètre.
        abort_unless(
            Payment::visibleTo(auth()->user())->whereKey($payment->id)->exists(),
            403,
            "Ce reçu concerne une facture d'un autre magasin."
        );
        $payment->load(['facture.customer', 'facture.store']);
        $facture = $payment->facture;
        $customer = $facture?->customer;
        $company = Company::current();

        return view('receipts.payment', [
            'payment' => $payment,
            'facture' => $facture,
            'customer' => $customer,
            'company' => $company,
            // Articles de la facture payée, dans l'unité de chaque produit.
            'lignes' => $facture ? $facture->sales()->with(['product', 'partner'])->get() : collect(),
        ]);
    }

    public function transaction(TransactionFacture $transaction)
    {
        abort_unless(
            TransactionFacture::visibleTo(auth()->user())->whereKey($transaction->id)->exists(),
            403,
            "Ce versement concerne des factures d'un autre magasin."
        );
        // Factures réglées par ce versement, avec leurs articles (quantités par unité).
        $transaction->load(['customer', 'payments.facture.sales:id,numeroFacture,product_id,quantity,unite']);
        $company = Company::current();

        return view('receipts.transaction', [
            'transaction' => $transaction,
            'customer' => $transaction->customer,
            'company' => $company,
            'user' => auth()->user(),
        ]);
    }

    public function transfer(StockTransfer $transfer)
    {
        // Comme la liste des transferts : un employé rattaché à un magasin ne
        // voit que les transferts qui partent de son périmètre ou y arrivent.
        abort_unless(
            StockTransfer::visibleTo(auth()->user())->whereKey($transfer->id)->exists(),
            403,
            "Ce transfert ne concerne pas votre magasin."
        );
        $transfer->load(['product', 'fromStore', 'toStore', 'requester', 'approver', 'shipper', 'receiver']);
        $company = Company::current();
        $receiptNumber = generateReceiptNumber('TRF', $transfer->id);

        return view('receipts.transfer', [
            'transfer' => $transfer,
            'company' => $company,
            'receiptNumber' => $receiptNumber,
            'user' => auth()->user(),
        ]);
    }
}
