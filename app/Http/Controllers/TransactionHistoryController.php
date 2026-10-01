<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionHistoryController extends Controller
{
    /**
     * Display list of transactions.
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['details', 'customer'])->orderBy('created_at', 'desc');

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by transaction number or customer name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        // Summary stats
        $todayTotal = Transaction::whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('grand_total');

        $todayCount = Transaction::whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->count();

        return view('transaksi.index', compact('transactions', 'todayTotal', 'todayCount'));
    }

    /**
     * Show transaction details.
     */
    public function show($id)
    {
        $transaction = Transaction::with(['details', 'customer'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json($transaction);
        }

        return view('transaksi.show', compact('transaction'));
    }

    /**
     * Cancel an existing transaction and return stock.
     */
    public function cancel(Request $request, $id)
    {
        $transaction = Transaction::with('details')->findOrFail($id);

        if ($transaction->status === 'cancelled') {
            return back()->with('error', 'Transaksi sudah dibatalkan sebelumnya.');
        }

        DB::transaction(function () use ($transaction, $request) {
            // Restore product stock
            if ($transaction->status === 'completed') {
                foreach ($transaction->details as $detail) {
                    if ($detail->product_id) {
                        Product::where('id', $detail->product_id)->increment('stock', $detail->qty);
                    }
                }
            }

            $transaction->status = 'cancelled';
            $transaction->note = ($transaction->note ? $transaction->note . ' | ' : '') . 'Dibatalkan: ' . ($request->input('reason', 'Pembatalan kasir'));
            $transaction->save();
        });

        return back()->with('success', 'Transaksi ' . $transaction->transaction_number . ' berhasil dibatalkan dan stok produk telah dikembalikan.');
    }
}
