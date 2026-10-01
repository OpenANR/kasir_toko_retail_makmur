@extends('layouts.app')

@section('title', 'Tutup Kasir & Rekapitulasi Penjualan')

@section('content')
<div class="pos-container" style="max-width:960px;">
    <!-- HEADER ACTION -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h2 style="font-size:20px;font-weight:700;color:#0f172a;">Laporan Tutup Kasir / Rekap Shift</h2>
        <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                🖨️ Cetak Rekap Tutup Kasir
            </button>
            <a href="{{ route('kasir.index') }}" class="btn btn-secondary">
                ⬅️ Kembali ke Kasir
            </a>
        </div>
    </div>

    <!-- FILTER DATE & INITIAL CASH -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <form method="GET" action="{{ route('kasir.closing') }}" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:200px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Pilih Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>
                <div style="flex:1;min-width:200px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Saldo Kas Awal (Modal Kasir)</label>
                    <input type="number" name="initial_cash" value="{{ $initialCash }}" class="form-control" placeholder="200000">
                </div>
                <button type="submit" class="btn btn-primary">Hitung Ulang Rekap</button>
            </form>
        </div>
    </div>

    <!-- REKAPITULASI SUMMARY -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:20px;">
        <div class="card" style="padding:16px;">
            <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Total Omset (Penjualan)</div>
            <div style="font-size:24px;font-weight:800;color:#059669;font-family:monospace;margin-top:4px;">
                Rp {{ number_format($totalSales, 0, ',', '.') }}
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $totalTransactions }} Transaksi • {{ $totalItemsSold }} Pcs</div>
        </div>

        <div class="card" style="padding:16px;">
            <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Saldo Akhir di Laci (Fisik Tunai)</div>
            <div style="font-size:24px;font-weight:800;color:#0284c7;font-family:monospace;margin-top:4px;">
                Rp {{ number_format($totalCashInDrawer, 0, ',', '.') }}
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Modal Awal + Tunai Masuk</div>
        </div>

        <div class="card" style="padding:16px;">
            <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Total Diskon Diberikan</div>
            <div style="font-size:24px;font-weight:800;color:#dc2626;font-family:monospace;margin-top:4px;">
                Rp {{ number_format($totalDiscount, 0, ',', '.') }}
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Diskon Member & Promo</div>
        </div>
    </div>

    <!-- BREAKDOWN PER PAYMENT METHOD -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span>Rincian Pembayaran Berdasarkan Metode ({{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }})</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Metode Pembayaran</th>
                        <th class="text-right">Total Transaksi</th>
                        <th class="text-right">Nominal Masuk</th>
                        <th class="text-right">% Kontribusi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentMethods as $method => $amount)
                        @php
                            $percentage = $totalSales > 0 ? ($amount / $totalSales) * 100 : 0;
                            $count = $transactions->where('payment_method', $method)->count();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $method }}</strong>
                                @if($method === 'Tunai')
                                    <span style="font-size:11px;color:#059669;margin-left:4px;">(Uang Tunai Laci)</span>
                                @else
                                    <span style="font-size:11px;color:#0284c7;margin-left:4px;">(Non-Tunai)</span>
                                @endif
                            </td>
                            <td class="text-right">{{ $count }} TRX</td>
                            <td class="text-right">
                                <strong style="font-family:monospace;">Rp {{ number_format($amount, 0, ',', '.') }}</strong>
                            </td>
                            <td class="text-right">{{ number_format($percentage, 1) }}%</td>
                        </tr>
                    @endforeach
                    <tr style="background:#f8fafc;font-weight:bold;">
                        <td>TOTAL PENJUALAN</td>
                        <td class="text-right">{{ $totalTransactions }} TRX</td>
                        <td class="text-right" style="color:#059669;font-family:monospace;font-size:15px;">
                            Rp {{ number_format($totalSales, 0, ',', '.') }}
                        </td>
                        <td class="text-right">100%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- REKONSILIASI KAS FISIK -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span>Rekonsiliasi Kas Tunai Fisik (Cash Drawer)</span>
        </div>
        <div class="card-body">
            <div style="max-width:480px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;">
                    <span>1. Saldo Modal Awal Kasir:</span>
                    <strong>Rp {{ number_format($initialCash, 0, ',', '.') }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span>2. Total Penjualan Tunai:</span>
                    <strong>Rp {{ number_format($paymentMethods['Tunai'], 0, ',', '.') }}</strong>
                </div>
                <div style="border-top:1px dashed #cbd5e1;padding-top:8px;display:flex;justify-content:space-between;font-size:15px;">
                    <strong>Total Fisik Uang Tunai di Laci Kasir:</strong>
                    <strong style="color:#0284c7;font-family:monospace;">Rp {{ number_format($totalCashInDrawer, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- LIST OF TRANSACTIONS FOR THIS DATE -->
    <div class="card">
        <div class="card-header">
            <span>Daftar Transaksi Tanggal {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Jam</th>
                        <th>Pelanggan</th>
                        <th>Metode</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td style="font-family:monospace;color:#0284c7;">{{ $trx->transaction_number }}</td>
                            <td>{{ $trx->created_at->format('H:i') }}</td>
                            <td>{{ $trx->customer_name }}</td>
                            <td>{{ $trx->payment_method }}</td>
                            <td class="text-right">Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ route('transaksi.show', $trx->id) }}" class="btn btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cart">Tidak ada transaksi selesai pada tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        .top-navbar, .btn, form, .actions-no-print {
            display: none !important;
        }
        body {
            background: #fff;
        }
        .pos-container {
            padding: 0;
            max-width: 100%;
        }
        .card {
            border: 1px solid #ccc;
            box-shadow: none;
            margin-bottom: 12px;
        }
    }
</style>
@endsection
