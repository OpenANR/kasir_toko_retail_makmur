@extends('layouts.app')

@section('title', 'Kasir Retail POS')

@section('content')
<div class="pos-container">
    <!-- HEADER -->
    <header class="header">
        <div>
            <div class="store-name">TOKO RETAIL MAKMUR</div>
            <div class="store-info">
                Jl. Contoh No. 123 • Jember • Telp. 0812-xxxx-xxxx
            </div>
        </div>
        <div class="transaction-info">
            <div>No. Transaksi</div>
            <div class="transaction-number" id="transactionNumber">
                {{ $transactionNumber }}
            </div>
            <div id="currentDate"></div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">
        <!-- ================= LEFT ================= -->
        <section>
            <!-- SEARCH -->
            <div class="card">
                <div class="card-header">
                    <span>Tambah Barang</span>
                    <span style="font-size:12px;font-weight:normal;color:#64748b;">
                        ⌨️ <strong>F2</strong> untuk pencarian / scanner
                    </span>
                </div>
                <div class="card-body">
                    <div class="search-area">
                        <div class="search-input">
                            <input type="text" id="searchProduct" placeholder="Cari nama barang / kode / barcode..." autocomplete="off">
                            <div class="product-results" id="productResults"></div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="searchProduct()">Cari</button>
                    </div>

                    <!-- CUSTOMER -->
                    <div class="customer-area">
                        <div class="form-group">
                            <label>Pelanggan</label>
                            <select class="form-control" id="customerSelect" onchange="handleCustomerChange(this)">
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}"
                                            data-type="{{ $c->type }}"
                                            data-discount="{{ $c->discount_percent }}"
                                            data-phone="{{ $c->phone }}">
                                        {{ $c->name }} ({{ $c->type }}{{ $c->discount_percent > 0 ? ' - Diskon ' . $c->discount_percent . '%' : '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No. Member / HP</label>
                            <input type="text" id="customerPhone" class="form-control" placeholder="Opsional (Otomatis jika member)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- CART -->
            <div class="card" style="margin-top:15px">
                <div class="card-header">
                    <div style="display:flex; justify-content:space-between; align-items:center; width: 100%;">
                        <span>Keranjang Belanja</span>
                        <span id="itemCount">0 Item</span>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Barang</th>
                                <th width="110">Harga</th>
                                <th width="120" class="text-center">Qty</th>
                                <th width="130" class="text-right">Subtotal</th>
                                <th width="40" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr id="emptyRow">
                                <td colspan="6" class="empty-cart">
                                    Keranjang masih kosong.
                                    <br>
                                    Silakan cari atau scan barang.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS INFO -->
            <div class="shortcut-bar">
                <span class="shortcut-item"><kbd>F2</kbd> Cari / Scan Barcode</span>
                <span class="shortcut-item"><kbd>F4</kbd> Uang Pembayaran</span>
                <span class="shortcut-item"><kbd>F9</kbd> Bayar & Cetak</span>
                <span class="shortcut-item"><kbd>ESC</kbd> Batal Transaksi</span>
            </div>
        </section>

        <!-- ================= RIGHT ================= -->
        <aside>
            <div class="card">
                <div class="card-header">Ringkasan Pembayaran</div>
                <div class="card-body">
                    <div class="payment-summary">
                        <!-- TOTAL ITEM -->
                        <div class="summary-row">
                            <span>Total Item</span>
                            <strong id="totalQty">0</strong>
                        </div>

                        <!-- SUBTOTAL -->
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <strong id="subtotal">Rp 0</strong>
                        </div>

                        <!-- DISCOUNT -->
                        <div class="summary-row">
                            <span>Diskon (%)</span>
                            <input type="number" id="discountPercent" value="0" min="0" max="100" onchange="calculateTotal()" oninput="calculateTotal()">
                        </div>
                        <div class="summary-row">
                            <span>Diskon (Rp)</span>
                            <input type="number" id="discountAmount" value="0" min="0" onchange="calculateTotal()" oninput="calculateTotal()">
                        </div>

                        <!-- TAX -->
                        <div class="summary-row">
                            <span>Pajak / PPN</span>
                            <input type="number" id="tax" value="0" min="0" onchange="calculateTotal()" oninput="calculateTotal()">
                        </div>

                        <!-- OTHER FEE -->
                        <div class="summary-row">
                            <span>Biaya Lain</span>
                            <input type="number" id="otherFee" value="0" min="0" onchange="calculateTotal()" oninput="calculateTotal()">
                        </div>

                        <!-- TOTAL -->
                        <div class="total-box">
                            <div class="total-label">TOTAL AKHIR</div>
                            <div class="total-value" id="grandTotal">Rp 0</div>
                        </div>

                        <!-- PAYMENT -->
                        <div class="payment-box">
                            <div class="payment-label">Uang Dibayar (F4)</div>
                            <input type="number" id="payment" class="payment-input" placeholder="0" oninput="calculateChange()">

                            <!-- NOMINAL CEPAT (SARAN #5) -->
                            <div class="quick-cash-area" id="quickCashContainer">
                                <button type="button" class="btn-quick-cash exact" onclick="setExactPayment()">Uang Pas</button>
                                <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(10000)">Rp 10.000</button>
                                <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(20000)">Rp 20.000</button>
                                <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(50000)">Rp 50.000</button>
                                <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(100000)">Rp 100.000</button>
                            </div>

                            <!-- PAYMENT METHOD -->
                            <div class="payment-label" style="margin-top:10px">Metode Pembayaran</div>
                            <div class="payment-method">
                                <button type="button" class="active" onclick="selectPayment(this)">Tunai</button>
                                <button type="button" onclick="selectPayment(this)">QRIS</button>
                                <button type="button" onclick="selectPayment(this)">Debit</button>
                                <button type="button" onclick="selectPayment(this)">Kredit</button>
                                <button type="button" onclick="selectPayment(this)">E-Wallet</button>
                                <button type="button" onclick="selectPayment(this)">Transfer</button>
                            </div>

                            <!-- CHANGE -->
                            <div class="change-box" id="changeBox">
                                <div class="change-label">KEMBALIAN</div>
                                <div class="change-value" id="change">Rp 0</div>
                            </div>
                        </div>

                        <!-- ACTION -->
                        <div class="action-area">
                            <button type="button" class="btn btn-warning" onclick="holdTransaction()">Tahan</button>
                            <button type="button" class="btn btn-danger" onclick="cancelTransaction()">Batal</button>
                            <button type="button" class="btn btn-success btn-pay" onclick="processPayment()">BAYAR &amp; CETAK</button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </main>
</div>
@endsection

@push('scripts')
<script>
    // Product data directly injected from database for lightning-fast autocomplete & offline resilience
    const products = @json($products);
</script>
<script src="{{ url(asset('js/chasier.js')) }}"></script>
@endpush
