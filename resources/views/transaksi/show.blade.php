@extends('layouts.app')

@section('title', 'Detail Transaksi ' . $transaction->transaction_number)

@section('content')
<div class="pos-container" style="max-width:960px;">
    <!-- HEADER ACTION -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">
            ⬅️ Kembali ke Riwayat
        </a>
        <div style="display:flex;gap:8px;">
            @if($transaction->status === 'completed')
                <a href="{{ route('kasir.print', $transaction->id) }}" target="_blank" class="btn btn-primary">
                    🖨️ Cetak Ulang Struk
                </a>
                <button type="button" class="btn btn-danger" onclick="confirmCancelTransaction()">
                    ❌ Batalkan Transaksi
                </button>
            @endif
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card">
        <div class="card-header">
            <span>Detail Transaksi: <strong>{{ $transaction->transaction_number }}</strong></span>
            <div>
                @if($transaction->status === 'completed')
                    <span style="background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:700;">
                        Selesai
                    </span>
                @elseif($transaction->status === 'held')
                    <span style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:700;">
                        Ditahan
                    </span>
                @else
                    <span style="background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:700;">
                        Dibatalkan
                    </span>
                @endif
            </div>
        </div>

        <div class="card-body">
            <!-- GENERAL INFO GRID -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;padding-bottom:16px;border-bottom:1px solid #e2e8f0;margin-bottom:20px;">
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:600;">Waktu Transaksi</div>
                    <div style="font-weight:600;margin-top:2px;">{{ $transaction->created_at->format('d F Y, H:i:s') }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:600;">Kasir</div>
                    <div style="font-weight:600;margin-top:2px;">{{ $transaction->user->name ?? 'Kasir Utama' }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:600;">Pelanggan</div>
                    <div style="font-weight:600;margin-top:2px;">{{ $transaction->customer_name }} ({{ $transaction->customer_type }})</div>
                    @if($transaction->customer_phone)
                        <div style="font-size:12px;color:#64748b;">HP: {{ $transaction->customer_phone }}</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:600;">Metode Pembayaran</div>
                    <div style="font-weight:600;margin-top:2px;color:#0284c7;">{{ $transaction->payment_method }}</div>
                </div>
            </div>

            <!-- ITEMS TABLE -->
            <h4 style="margin-bottom:10px;font-size:14px;color:#334155;">Daftar Item Belanja</h4>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th width="40">No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th class="text-right">Harga Satuan</th>
                            <th class="text-center" width="80">Qty</th>
                            <th class="text-right" width="140">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->details as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td style="font-family:monospace;font-size:12px;color:#64748b;">{{ $detail->product_code }}</td>
                                <td><strong>{{ $detail->product_name }}</strong></td>
                                <td class="text-right">Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $detail->qty }}</td>
                                <td class="text-right"><strong>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- CALCULATION BREAKDOWN -->
            <div style="display:flex;justify-content:flex-end;margin-top:20px;">
                <div style="width:340px;background:#f8fafc;padding:16px 20px;border-radius:6px;border:1px solid #e2e8f0;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
                        <span>Subtotal:</span>
                        <strong>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</strong>
                    </div>

                    @if($transaction->total_discount > 0)
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;color:#dc2626;">
                            <span>Diskon {{ $transaction->discount_percent > 0 ? '(' . $transaction->discount_percent . '%)' : '' }}:</span>
                            <strong>- Rp {{ number_format($transaction->total_discount, 0, ',', '.') }}</strong>
                        </div>
                    @endif

                    @if($transaction->tax > 0)
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
                            <span>Pajak (PPN):</span>
                            <strong>Rp {{ number_format($transaction->tax, 0, ',', '.') }}</strong>
                        </div>
                    @endif

                    @if($transaction->other_fee > 0)
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
                            <span>Biaya Lain:</span>
                            <strong>Rp {{ number_format($transaction->other_fee, 0, ',', '.') }}</strong>
                        </div>
                    @endif

                    <div style="border-top:2px solid #cbd5e1;padding-top:8px;margin-top:8px;display:flex;justify-content:space-between;font-size:16px;color:#0f172a;">
                        <strong>TOTAL AKHIR:</strong>
                        <strong style="color:#059669;font-family:monospace;">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</strong>
                    </div>

                    <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:13px;">
                        <span>Uang Dibayar:</span>
                        <strong>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</strong>
                    </div>

                    <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:13px;">
                        <span>Kembalian:</span>
                        <strong style="color:#0284c7;">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            @if($transaction->note)
                <div style="margin-top:16px;padding:12px;background:#f1f5f9;border-radius:4px;font-size:12px;">
                    <strong>Catatan Transaksi:</strong> {{ $transaction->note }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- CANCEL CONFIRMATION FORM -->
@if($transaction->status === 'completed')
    <form id="cancelForm" method="POST" action="{{ route('transaksi.cancel', $transaction->id) }}" style="display:none;">
        @csrf
        <input type="hidden" name="reason" id="cancelReason">
    </form>
    <script>
        function confirmCancelTransaction() {
            const reason = prompt("Masukkan alasan pembatalan / retur transaksi:", "Kesalahan input kasir / Retur pelanggan");
            if (reason !== null && reason.trim() !== "") {
                document.getElementById("cancelReason").value = reason;
                document.getElementById("cancelForm").submit();
            }
        }
    </script>
@endif
@endsection
