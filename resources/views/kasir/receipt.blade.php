<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - {{ $transaction->transaction_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }

        body {
            background-color: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            min-height: 100vh;
        }

        .receipt-card {
            background: #ffffff;
            width: 320px;
            padding: 20px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .store-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .store-sub {
            font-size: 11px;
            color: #333;
            margin-top: 3px;
            line-height: 1.3;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .double-divider {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            height: 3px;
            margin: 8px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .item-list {
            margin: 6px 0;
        }

        .item-row {
            margin-bottom: 6px;
            font-size: 12px;
        }

        .item-name {
            font-weight: bold;
        }

        .item-calc {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #333;
            padding-left: 8px;
        }

        .total-section {
            margin-top: 6px;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .grand-line {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin: 6px 0;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 14px;
            font-size: 11px;
            color: #444;
            line-height: 1.4;
        }

        .actions-no-print {
            margin-top: 20px;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-action {
            font-family: sans-serif;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-print {
            background-color: #0d6efd;
            color: #fff;
        }

        .btn-close {
            background-color: #6c757d;
            color: #fff;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .receipt-card {
                width: 100%;
                max-width: 58mm;
                box-shadow: none;
                padding: 4px;
            }
            .actions-no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <!-- HEADER -->
        <div class="receipt-header">
            <div class="store-title">TOKO RETAIL MAKMUR</div>
            <div class="store-sub">Jl. Contoh No. 123 • Jember</div>
            <div class="store-sub">Telp: 0812-xxxx-xxxx</div>
        </div>

        <div class="divider"></div>

        <!-- INFO -->
        <div class="info-row">
            <span>No: {{ $transaction->transaction_number }}</span>
            <span>{{ $transaction->created_at->format('d/m/y H:i') }}</span>
        </div>
        <div class="info-row">
            <span>Kasir: {{ $transaction->user->name ?? 'Kasir Utama' }}</span>
            <span>Metode: {{ $transaction->payment_method }}</span>
        </div>
        <div class="info-row">
            <span>Pelanggan: {{ $transaction->customer_name }}</span>
            <span>{{ $transaction->customer_type }}</span>
        </div>

        <div class="double-divider"></div>

        <!-- ITEMS -->
        <div class="item-list">
            @foreach($transaction->details as $item)
                <div class="item-row">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="item-calc">
                        <span>{{ $item->qty }} x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                        <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="divider"></div>

        <!-- SUMMARY -->
        <div class="total-section">
            <div class="summary-line">
                <span>Subtotal</span>
                <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>

            @if($transaction->total_discount > 0)
                <div class="summary-line">
                    <span>Diskon {{ $transaction->discount_percent > 0 ? '(' . $transaction->discount_percent . '%)' : '' }}</span>
                    <span>- Rp {{ number_format($transaction->total_discount, 0, ',', '.') }}</span>
                </div>
            @endif

            @if($transaction->tax > 0)
                <div class="summary-line">
                    <span>Pajak (PPN)</span>
                    <span>Rp {{ number_format($transaction->tax, 0, ',', '.') }}</span>
                </div>
            @endif

            @if($transaction->other_fee > 0)
                <div class="summary-line">
                    <span>Biaya Lain</span>
                    <span>Rp {{ number_format($transaction->other_fee, 0, ',', '.') }}</span>
                </div>
            @endif

            <div class="divider"></div>

            <div class="grand-line">
                <span>TOTAL</span>
                <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
            </div>

            <div class="summary-line">
                <span>Bayar ({{ $transaction->payment_method }})</span>
                <span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
            </div>

            <div class="summary-line">
                <span>Kembali</span>
                <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="double-divider"></div>

        <!-- FOOTER -->
        <div class="receipt-footer">
            <p>Terima Kasih Atas Kunjungan Anda</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan kecuali ada perjanjian.</p>
            <p style="margin-top:6px;font-size:10px;color:#888;">Simpan struk ini sebagai bukti pembayaran yang sah.</p>
        </div>

        <!-- ACTION BUTTONS (Hidden when printed) -->
        <div class="actions-no-print">
            <button type="button" class="btn-action btn-print" onclick="window.print()">🖨️ Cetak Struk</button>
            <a href="{{ route('kasir.index') }}" class="btn-action btn-close">⬅️ Kembali ke Kasir</a>
        </div>
    </div>

    <script>
        // Auto print trigger when page opens
        window.addEventListener('load', function () {
            // Small timeout to allow styles to render completely
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
