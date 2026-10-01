<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClosingCashierController extends Controller
{
    /**
     * Display cashier closing summary / end-of-day report.
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $initialCash = (float) $request->input('initial_cash', 200000); // Saldo modal kasir default

        $transactions = Transaction::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();

        $totalTransactions = $transactions->count();
        $totalSales = $transactions->sum('grand_total');
        $totalSubtotal = $transactions->sum('subtotal');
        $totalDiscount = $transactions->sum('total_discount');
        $totalTax = $transactions->sum('tax');

        // Breakdown by payment method
        $paymentMethods = [
            'Tunai' => $transactions->where('payment_method', 'Tunai')->sum('grand_total'),
            'QRIS' => $transactions->where('payment_method', 'QRIS')->sum('grand_total'),
            'Debit' => $transactions->where('payment_method', 'Debit')->sum('grand_total'),
            'Kredit' => $transactions->where('payment_method', 'Kredit')->sum('grand_total'),
            'E-Wallet' => $transactions->where('payment_method', 'E-Wallet')->sum('grand_total'),
            'Transfer' => $transactions->where('payment_method', 'Transfer')->sum('grand_total'),
        ];

        // Total cash in drawer = initial cash + cash sales
        $totalCashInDrawer = $initialCash + $paymentMethods['Tunai'];

        // Total items sold
        $trxIds = $transactions->pluck('id');
        $totalItemsSold = TransactionDetail::whereIn('transaction_id', $trxIds)->sum('qty');

        return view('kasir.tutup-kasir', compact(
            'date',
            'initialCash',
            'transactions',
            'totalTransactions',
            'totalSales',
            'totalSubtotal',
            'totalDiscount',
            'totalTax',
            'paymentMethods',
            'totalCashInDrawer',
            'totalItemsSold'
        ));
    }
}
