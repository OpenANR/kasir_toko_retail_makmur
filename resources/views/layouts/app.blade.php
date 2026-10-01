<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kasir Retail POS') - Toko Retail Makmur</title>
    <link rel="stylesheet" href="{{ url(asset('css/chasier.css')) }}">
    @stack('styles')
</head>
<body>
    <!-- TOP NAVIGATION BAR -->
    <nav class="top-navbar">
        <a href="{{ route('kasir.index') }}" class="brand-logo">
            <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12zm-7-8c-1.66 0-3-1.34-3-3H7c0 2.76 2.24 5 5 5s5-2.24 5-5h-2c0 1.66-1.34 3-3 3z"/>
            </svg>
            <span>TOKO RETAIL MAKMUR</span>
            <span class="brand-badge">POS v1.0</span>
        </a>

        <div class="nav-links">
            <a href="{{ route('kasir.index') }}" class="nav-link {{ request()->routeIs('kasir.index') || request()->is('/') ? 'active' : '' }}">
                <span>🛒 Kasir (POS)</span>
            </a>
            <button type="button" class="nav-link" style="background:none;border:none;cursor:pointer;" onclick="openHeldModal()">
                <span>⏸️ Ditahan</span>
                <span class="badge-count" id="heldCountBadge" style="{{ ($heldCount ?? 0) > 0 ? '' : 'display:none;' }}">
                    {{ $heldCount ?? 0 }}
                </span>
            </button>
            <a href="{{ route('transaksi.index') }}" class="nav-link {{ request()->routeIs('transaksi.*') ? 'active' : '' }}">
                <span>📋 Riwayat Transaksi</span>
            </a>
            <a href="{{ route('produk.index') }}" class="nav-link {{ request()->routeIs('produk.*') ? 'active' : '' }}">
                <span>📦 Kelola Produk</span>
            </a>
            <a href="{{ route('kasir.closing') }}" class="nav-link {{ request()->routeIs('kasir.closing') ? 'active' : '' }}">
                <span>💰 Tutup Kasir</span>
            </a>
        </div>
    </nav>

    <!-- CONTENT WRAPPER -->
    @yield('content')

    <!-- HELD TRANSACTIONS MODAL -->
    <div class="modal-overlay" id="heldModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Transaksi Ditahan (Parkir Transaksi)</h3>
                <button type="button" class="modal-close" onclick="closeHeldModal()">&times;</button>
            </div>
            <div class="modal-body" id="heldListContainer">
                <div style="text-align:center;padding:20px;color:#64748b;">
                    Memuat daftar transaksi...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeHeldModal()">Tutup</button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
