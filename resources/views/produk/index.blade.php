@extends('layouts.app')

@section('title', 'Kelola Produk & Stok')

@section('content')
<div class="pos-container">
    @if(session('success'))
        <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:16px;border:1px solid #fecaca;">
            <ul style="margin-left:20px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- FILTER & ACTION BAR -->
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <form method="GET" action="{{ route('produk.index') }}" style="display:flex;gap:10px;flex:1;max-width:650px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama barang / barcode / kode...">
                <select name="category" class="form-control" style="width:180px;">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">🔍 Cari</button>
                <a href="{{ route('produk.index') }}" class="btn btn-secondary">Reset</a>
            </form>

            <button type="button" class="btn btn-success" onclick="openAddModal()">
                ➕ Tambah Produk Baru
            </button>
        </div>
    </div>

    <!-- PRODUCTS TABLE -->
    <div class="card">
        <div class="card-header">
            <span>Daftar Produk Retail Makmur</span>
            <span style="font-size:12px;font-weight:normal;color:#64748b;">Total: {{ $products->total() }} Produk</span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th width="100">Kode</th>
                        <th width="140">Barcode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-right">Harga Jual</th>
                        <th class="text-center" width="100">Stok</th>
                        <th class="text-center" width="140">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $item)
                        <tr>
                            <td>{{ $products->firstItem() + $index }}</td>
                            <td><strong style="font-family:monospace;color:#0284c7;">{{ $item->code }}</strong></td>
                            <td style="font-family:monospace;font-size:12px;">{{ $item->barcode }}</td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td><span style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:11px;">{{ $item->category ?? 'Umum' }}</span></td>
                            <td class="text-right"><strong>Rp {{ number_format($item->price, 0, ',', '.') }}</strong></td>
                            <td class="text-center">
                                @if($item->stock <= 5)
                                    <span style="background:#fee2e2;color:#dc2626;padding:3px 8px;border-radius:4px;font-weight:700;font-size:11px;">
                                        {{ $item->stock }} {{ $item->unit }}
                                    </span>
                                @else
                                    <span style="background:#ecfdf5;color:#059669;padding:3px 8px;border-radius:4px;font-weight:700;font-size:11px;">
                                        {{ $item->stock }} {{ $item->unit }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div style="display:inline-flex;gap:4px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(@json($item))'>
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('produk.destroy', $item->id) }}" onsubmit="return confirm('Hapus produk {{ $item->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-cart">Belum ada data produk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div style="padding:16px 20px;border-top:1px solid #dee2e6;">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ADD / EDIT PRODUCT MODAL -->
<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <form id="productForm" method="POST" action="{{ route('produk.store') }}">
            @csrf
            <div id="methodSpoof"></div>
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Produk Baru</h3>
                <button type="button" class="modal-close" onclick="closeProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div class="form-group">
                        <label>Kode Produk *</label>
                        <input type="text" name="code" id="prodCode" class="form-control" required placeholder="Contoh: BRG013">
                    </div>
                    <div class="form-group">
                        <label>Barcode *</label>
                        <input type="text" name="barcode" id="prodBarcode" class="form-control" required placeholder="Contoh: 899123456013">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:12px;">
                    <label>Nama Produk *</label>
                    <input type="text" name="name" id="prodName" class="form-control" required placeholder="Nama lengkap barang">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div class="form-group">
                        <label>Harga Jual (Rp) *</label>
                        <input type="number" name="price" id="prodPrice" class="form-control" required min="0" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Harga Modal (Rp)</label>
                        <input type="number" name="cost_price" id="prodCostPrice" class="form-control" min="0" placeholder="0">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Stok Awal *</label>
                        <input type="number" name="stock" id="prodStock" class="form-control" required min="0" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="unit" id="prodUnit" class="form-control" placeholder="pcs / kg / btl">
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <input type="text" name="category" id="prodCategory" class="form-control" placeholder="Minuman / Makanan">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="saveProdBtn">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        const modal = document.getElementById("productModal");
        const form = document.getElementById("productForm");
        form.action = "{{ route('produk.store') }}";
        document.getElementById("methodSpoof").innerHTML = "";
        document.getElementById("modalTitle").innerText = "Tambah Produk Baru";
        document.getElementById("saveProdBtn").innerText = "Simpan Produk";

        form.reset();
        modal.style.display = "flex";
    }

    function openEditModal(product) {
        const modal = document.getElementById("productModal");
        const form = document.getElementById("productForm");
        form.action = `/produk/${product.id}`;
        document.getElementById("methodSpoof").innerHTML = '@method("PUT")';
        document.getElementById("modalTitle").innerText = "Edit Produk: " + product.name;
        document.getElementById("saveProdBtn").innerText = "Perbarui Produk";

        document.getElementById("prodCode").value = product.code;
        document.getElementById("prodBarcode").value = product.barcode;
        document.getElementById("prodName").value = product.name;
        document.getElementById("prodPrice").value = product.price;
        document.getElementById("prodCostPrice").value = product.cost_price || 0;
        document.getElementById("prodStock").value = product.stock;
        document.getElementById("prodUnit").value = product.unit || 'pcs';
        document.getElementById("prodCategory").value = product.category || '';

        modal.style.display = "flex";
    }

    function closeProductModal() {
        document.getElementById("productModal").style.display = "none";
    }
</script>
@endsection
