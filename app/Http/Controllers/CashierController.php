<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    /**
     * Display the POS cashier interface.
     */
    public function index()
    {
        $todayPrefix = 'TRX-' . Carbon::now()->format('Ymd') . '-';
        $todayCount = Transaction::whereDate('created_at', Carbon::today())->count();
        $nextNumber = str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        $transactionNumber = $todayPrefix . $nextNumber;

        $products = Product::where('stock', '>', 0)
            ->orWhere('stock', 0)
            ->orderBy('name')
            ->get();

        $customers = Customer::orderBy('name')->get();
        $heldCount = Transaction::where('status', 'held')->count();

        return view('kasir.index', compact(
            'transactionNumber',
            'products',
            'customers',
            'heldCount'
        ));
    }

    /**
     * Search products for live search / barcode scanner.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (empty($query)) {
            return response()->json([]);
        }

        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$query}%")
            ->take(15)
            ->get();

        return response()->json($products);
    }

    /**
     * Process checkout & payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.name' => 'required|string',
            'items.*.code' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'payment' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'other_fee' => 'nullable|numeric|min:0',
            'held_id' => 'nullable|integer',
        ]);

        $items = $request->input('items', []);
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }

        $discountPercent = (float) $request->input('discount_percent', 0);
        $discountAmount = (float) $request->input('discount_amount', 0);
        $tax = (float) $request->input('tax', 0);
        $otherFee = (float) $request->input('other_fee', 0);

        $percentDiscount = $subtotal * ($discountPercent / 100);
        $totalDiscount = $percentDiscount + $discountAmount;
        $grandTotal = max(0, $subtotal - $totalDiscount + $tax + $otherFee);

        $payment = (float) $request->input('payment', 0);
        if ($payment < $grandTotal) {
            return response()->json([
                'success' => false,
                'message' => 'Uang pembayaran masih kurang.',
            ], 422);
        }

        $change = $payment - $grandTotal;

        $transaction = DB::transaction(function () use (
            $request,
            $items,
            $subtotal,
            $discountPercent,
            $discountAmount,
            $totalDiscount,
            $tax,
            $otherFee,
            $grandTotal,
            $payment,
            $change
        ) {
            // Generate clean transaction number
            $todayPrefix = 'TRX-' . Carbon::now()->format('Ymd') . '-';
            $todayCount = Transaction::whereDate('created_at', Carbon::today())->count();
            $transactionNumber = $todayPrefix . str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);

            // Customer info
            $customerId = $request->input('customer_id');
            $customerName = $request->input('customer_name', 'Umum');
            $customerPhone = $request->input('customer_phone');
            $customerType = $request->input('customer_type', 'Umum');

            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $customerName = $customer->name;
                    $customerPhone = $customer->phone;
                    $customerType = $customer->type;
                }
            }

            $trx = Transaction::create([
                'transaction_number' => $transactionNumber,
                'user_id' => auth()->id() ?? null,
                'customer_id' => $customerId ?: null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_type' => $customerType,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total_discount' => $totalDiscount,
                'tax' => $tax,
                'other_fee' => $otherFee,
                'grand_total' => $grandTotal,
                'paid_amount' => $payment,
                'change_amount' => $change,
                'payment_method' => $request->input('payment_method', 'Tunai'),
                'status' => 'completed',
                'note' => $request->input('note'),
            ]);

            // Save details and decrease stock
            foreach ($items as $item) {
                $itemSubtotal = $item['price'] * $item['qty'];
                TransactionDetail::create([
                    'transaction_id' => $trx->id,
                    'product_id' => $item['id'] ?? null,
                    'product_code' => $item['code'],
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'discount' => 0,
                    'subtotal' => $itemSubtotal,
                ]);

                // Reduce stock
                if (!empty($item['id'])) {
                    Product::where('id', $item['id'])->decrement('stock', $item['qty']);
                }
            }

            // If resuming an existing held transaction, delete or mark it finished
            if ($request->filled('held_id')) {
                Transaction::where('id', $request->input('held_id'))
                    ->where('status', 'held')
                    ->delete();
            }

            return $trx;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil!',
            'transaction_id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'total' => $grandTotal,
            'payment' => $payment,
            'change' => $change,
            'print_url' => route('kasir.print', $transaction->id),
        ]);
    }

    /**
     * Hold a transaction (Parkir Transaksi).
     */
    public function hold(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.name' => 'required|string',
            'items.*.code' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $items = $request->input('items', []);
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }

        $discountPercent = (float) $request->input('discount_percent', 0);
        $discountAmount = (float) $request->input('discount_amount', 0);
        $tax = (float) $request->input('tax', 0);
        $otherFee = (float) $request->input('other_fee', 0);

        $percentDiscount = $subtotal * ($discountPercent / 100);
        $totalDiscount = $percentDiscount + $discountAmount;
        $grandTotal = max(0, $subtotal - $totalDiscount + $tax + $otherFee);

        $heldTrx = DB::transaction(function () use (
            $request,
            $items,
            $subtotal,
            $discountPercent,
            $discountAmount,
            $totalDiscount,
            $tax,
            $otherFee,
            $grandTotal
        ) {
            $todayPrefix = 'HLD-' . Carbon::now()->format('Ymd') . '-';
            $heldCount = Transaction::where('transaction_number', 'like', $todayPrefix . '%')->count();
            $trxNumber = $todayPrefix . str_pad($heldCount + 1, 3, '0', STR_PAD_LEFT);

            $customerId = $request->input('customer_id');
            $customerName = $request->input('customer_name', 'Umum');
            $customerPhone = $request->input('customer_phone');
            $customerType = $request->input('customer_type', 'Umum');

            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $customerName = $customer->name;
                    $customerPhone = $customer->phone;
                    $customerType = $customer->type;
                }
            }

            $trx = Transaction::create([
                'transaction_number' => $trxNumber,
                'user_id' => auth()->id() ?? null,
                'customer_id' => $customerId ?: null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_type' => $customerType,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total_discount' => $totalDiscount,
                'tax' => $tax,
                'other_fee' => $otherFee,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'change_amount' => 0,
                'payment_method' => $request->input('payment_method', 'Tunai'),
                'status' => 'held',
                'note' => $request->input('note'),
            ]);

            foreach ($items as $item) {
                TransactionDetail::create([
                    'transaction_id' => $trx->id,
                    'product_id' => $item['id'] ?? null,
                    'product_code' => $item['code'],
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'discount' => 0,
                    'subtotal' => $item['price'] * $item['qty'],
                ]);
            }

            return $trx;
        });

        $totalHeld = Transaction::where('status', 'held')->count();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil ditahan (' . $heldTrx->transaction_number . ').',
            'held_id' => $heldTrx->id,
            'held_count' => $totalHeld,
        ]);
    }

    /**
     * Get all held transactions list.
     */
    public function getHeld()
    {
        $held = Transaction::with('details')
            ->where('status', 'held')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($held);
    }

    /**
     * Resume a held transaction into active cart.
     */
    public function resumeHeld($id)
    {
        $transaction = Transaction::with('details')->where('status', 'held')->findOrFail($id);

        $cart = [];
        foreach ($transaction->details as $d) {
            $product = Product::find($d->product_id);
            $cart[] = [
                'id' => $d->product_id,
                'code' => $d->product_code,
                'barcode' => $product ? $product->barcode : '',
                'name' => $d->product_name,
                'price' => (float) $d->price,
                'qty' => (int) $d->qty,
            ];
        }

        return response()->json([
            'success' => true,
            'held_id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'customer_id' => $transaction->customer_id,
            'customer_name' => $transaction->customer_name,
            'customer_phone' => $transaction->customer_phone,
            'customer_type' => $transaction->customer_type,
            'discount_percent' => (float) $transaction->discount_percent,
            'discount_amount' => (float) $transaction->discount_amount,
            'tax' => (float) $transaction->tax,
            'other_fee' => (float) $transaction->other_fee,
            'payment_method' => $transaction->payment_method,
            'cart' => $cart,
        ]);
    }

    /**
     * Delete a held transaction.
     */
    public function destroyHeld($id)
    {
        $transaction = Transaction::where('status', 'held')->findOrFail($id);
        $transaction->delete();

        $totalHeld = Transaction::where('status', 'held')->count();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi ditahan berhasil dihapus.',
            'held_count' => $totalHeld,
        ]);
    }

    /**
     * Printable thermal receipt.
     */
    public function printReceipt($id)
    {
        $transaction = Transaction::with(['details', 'customer'])->findOrFail($id);

        return view('kasir.receipt', compact('transaction'));
    }
}
