@extends('layouts.app')

@section('title', 'Riwayat Transaksi Penjualan')

@section('content')
<div class="pos-container">
    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid #fecaca;">
            {{ session('error') }}
        </div>
    @endif

    <!-- SUMMARY METRICS -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;margin-bottom:20px;">
        <div class="card" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Penjualan Hari Ini</div>
                <div style="font-size:24px;font-weight:800;color:#059669;font-family:monospace;margin-top:4px;">
                    Rp {{ number_format($todayTotal, 0, ',', '.') }}
                </div>
            </div>
            <div style="background:#ecfdf5;padding:12px;border-radius:50%;color:#059669;">
                💵
            </div>
        </div>

        <div class="card" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">Total Transaksi Selesai (Hari Ini)</div>
                <div style="font-size:24px;font-weight:800;color:#0284c7;font-family:monospace;margin-top:4px;">
                    {{ $todayCount }} Transaksi
                </div>
            </div>
            <div style="background:#e0f2fe;padding:12px;border-radius:50%;color:#0284c7;">
                🧾
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <form method="GET" action="{{ route('transaksi.index') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                <div style="flex:1;min-width:200px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Cari No. TRX / Pelanggan</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Contoh: TRX-2026 atau Umum...">
                </div>

                <div style="width:160px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-control">
                </div>

                <div style="width:160px;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="held" {{ request('status') == 'held' ? 'selected' : '' }}>Ditahan</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>

                <div style="display:flex;gap:6px;">
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- TRANSACTIONS TABLE -->
    <div class="card">
        <div class="card-header">
            <span>Daftar Transaksi Penjualan</span>
            <a href="{{ route('kasir.index') }}" class="btn btn-primary btn-sm">➕ Transaksi Baru</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Item</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th class="text-center" width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td>
                                <strong style="font-family:monospace;color:#0284c7;">
                                    {{ $trx->transaction_number }}
                                </strong>
                            </td>
                            <td>{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div><strong>{{ $trx->customer_name }}</strong></div>
                                <div style="font-size:11px;color:#64748b;">{{ $trx->customer_type }}</div>
                            </td>
                            <td>{{ $trx->details->sum('qty') }} item</td>
                            <td>
                                <strong>Rp {{ number_format($trx->grand_total, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <span style="background:#f1f5f9;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:600;">
                                    {{ $trx->payment_method }}
                                </span>
                            </td>
                            <td>
                                @if($trx->status === 'completed')
                                    <span style="background:#d1fae5;color:#065f46;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:700;">
                                        Selesai
                                    </span>
                                @elseif($trx->status === 'held')
                                    <span style="background:#fef3c7;color:#92400e;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:700;">
                                        Ditahan
                                    </span>
                                @else
                                    <span style="background:#fee2e2;color:#991b1b;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:700;">
                                        Dibatalkan
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div style="display:inline-flex;gap:4px;">
                                    <a href="{{ route('transaksi.show', $trx->id) }}" class="btn btn-secondary btn-sm" title="Lihat Detail">
                                        Detail
                                    </a>
                                    @if($trx->status === 'completed')
                                        <a href="{{ route('kasir.print', $trx->id) }}" target="_blank" class="btn btn-primary btn-sm" title="Cetak Struk">
                                            Struk
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-cart">
                                Belum ada data transaksi yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div style="padding:16px 20px;border-top:1px solid #dee2e6;">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
