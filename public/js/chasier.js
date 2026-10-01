/**
 * TOKO RETAIL MAKMUR - POINT OF SALE (POS) JAVASCRIPT
 * File: chasier.js
 * Sesuai Prosedur Kerja Praktikum Ke-3 + Rekomendasi Fitur Laravel 13
 */

// Cart state
let cart = [];
let selectedPaymentMethod = "Tunai";
let activeHeldId = null;

// DOM Elements
const searchInput = document.getElementById("searchProduct");
const resultBox = document.getElementById("productResults");

/* ============================================
   1. SEARCH PRODUCT & BARCODE SCANNER
   ============================================ */
if (searchInput) {
    searchInput.addEventListener("input", searchProduct);

    // Scanner Barcode Handler (Enter Key)
    searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            handleBarcodeOrEnter();
        }
    });
}

// Close search dropdown on click outside
document.addEventListener("click", function (event) {
    if (resultBox && !event.target.closest(".search-input")) {
        resultBox.style.display = "none";
    }
});

function searchProduct() {
    if (!searchInput || !resultBox) return;
    const keyword = searchInput.value.toLowerCase().trim();

    if (!keyword) {
        resultBox.style.display = "none";
        return;
    }

    if (typeof products === "undefined" || !products.length) {
        resultBox.innerHTML = '<div class="product-item">Memuat produk...</div>';
        resultBox.style.display = "block";
        return;
    }

    const results = products.filter(
        (product) =>
            product.name.toLowerCase().includes(keyword) ||
            product.code.toLowerCase().includes(keyword) ||
            (product.barcode && product.barcode.includes(keyword))
    );

    if (results.length === 0) {
        resultBox.innerHTML = `
            <div class="product-item" style="color:#ef4444; justify-content:center;">
                Barang tidak ditemukan
            </div>
        `;
    } else {
        resultBox.innerHTML = results
            .slice(0, 10)
            .map(
                (product) => `
            <div class="product-item" onclick="addToCart(${product.id})">
                <div>
                    <div class="product-name">${escapeHtml(product.name)}</div>
                    <div class="product-code">${escapeHtml(product.code)} ${product.barcode ? '• ' + escapeHtml(product.barcode) : ''} • Stok: ${product.stock ?? '-'}</div>
                </div>
                <div class="product-price">${formatRupiah(product.price)}</div>
            </div>
        `
            )
            .join("");
    }
    resultBox.style.display = "block";
}

/**
 * Barcode Scanner flow:
 * Scan Barcode -> Enter key -> Match product -> if exists: qty+1, if new: add to cart
 */
function handleBarcodeOrEnter() {
    const keyword = searchInput.value.trim().toLowerCase();
    if (!keyword) return;

    if (typeof products === "undefined" || !products.length) return;

    // 1. Exact match by barcode or code
    let matched = products.find(
        (p) =>
            (p.barcode && p.barcode.toLowerCase() === keyword) ||
            p.code.toLowerCase() === keyword
    );

    // 2. Exact match by name
    if (!matched) {
        matched = products.find((p) => p.name.toLowerCase() === keyword);
    }

    // 3. If only one product in search results
    if (!matched) {
        const filtered = products.filter(
            (p) =>
                p.name.toLowerCase().includes(keyword) ||
                p.code.toLowerCase().includes(keyword) ||
                (p.barcode && p.barcode.includes(keyword))
        );
        if (filtered.length === 1) {
            matched = filtered[0];
        }
    }

    if (matched) {
        addToCart(matched.id);
        searchInput.value = "";
        resultBox.style.display = "none";
        searchInput.focus();
    } else {
        // Flash border red briefly
        searchInput.style.borderColor = "#ef4444";
        setTimeout(() => {
            searchInput.style.borderColor = "";
        }, 800);
    }
}

/* ============================================
   2. FORMAT RUPIAH
   ============================================ */
function formatRupiah(value) {
    const num = Number(value) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(num);
}

function escapeHtml(string) {
    const str = String(string || "");
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* ============================================
   3. CART MANAGEMENT
   ============================================ */
function addToCart(productId) {
    const product = products.find((p) => p.id === productId);
    if (!product) return;

    const existing = cart.find((item) => item.id === productId);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({
            id: product.id,
            code: product.code,
            barcode: product.barcode,
            name: product.name,
            price: Number(product.price),
            qty: 1,
        });
    }

    if (searchInput) searchInput.value = "";
    if (resultBox) resultBox.style.display = "none";
    renderCart();

    // Re-focus search input for next scan
    if (searchInput) searchInput.focus();
}

function renderCart() {
    const body = document.getElementById("cartBody");
    if (!body) return;

    if (cart.length === 0) {
        body.innerHTML = `
            <tr id="emptyRow">
                <td colspan="6" class="empty-cart">
                    <svg style="width:48px;height:48px;margin-bottom:8px;fill:#cbd5e1" viewBox="0 0 24 24">
                        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                    <br>
                    <strong>Keranjang masih kosong.</strong>
                    <br>
                    Silakan cari nama barang atau scan barcode.
                </td>
            </tr>
        `;
        calculateTotal();
        return;
    }

    body.innerHTML = cart
        .map(
            (item, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>
                <strong>${escapeHtml(item.name)}</strong>
                <div style="font-size:11px; color:#6c757d">
                    ${escapeHtml(item.code)} ${item.barcode ? '• ' + escapeHtml(item.barcode) : ''}
                </div>
            </td>
            <td>${formatRupiah(item.price)}</td>
            <td class="text-center">
                <div class="qty-control">
                    <button type="button" onclick="changeQty(${item.id}, -1)">−</button>
                    <input type="number" value="${item.qty}" min="1"
                        onchange="updateQty(${item.id}, this.value)">
                    <button type="button" onclick="changeQty(${item.id}, 1)">+</button>
                </div>
            </td>
            <td class="text-right">
                <strong>${formatRupiah(item.price * item.qty)}</strong>
            </td>
            <td class="text-center">
                <button type="button" class="remove-btn" title="Hapus Item"
                    onclick="removeItem(${item.id})">×</button>
            </td>
        </tr>
    `
        )
        .join("");

    calculateTotal();
}

function changeQty(id, amount) {
    const item = cart.find((item) => item.id === id);
    if (!item) return;

    item.qty += amount;
    if (item.qty <= 0) {
        removeItem(id);
        return;
    }
    renderCart();
}

function updateQty(id, qty) {
    const item = cart.find((item) => item.id === id);
    if (!item) return;

    item.qty = Math.max(1, parseInt(qty) || 1);
    renderCart();
}

function removeItem(id) {
    cart = cart.filter((item) => item.id !== id);
    renderCart();
}

/* ============================================
   4. CALCULATIONS & TOTALS
   ============================================ */
function calculateTotal() {
    let totalQty = 0;
    let subtotal = 0;

    cart.forEach((item) => {
        totalQty += item.qty;
        subtotal += item.price * item.qty;
    });

    const discountPercent =
        parseFloat(document.getElementById("discountPercent")?.value) || 0;
    const manualDiscount =
        parseFloat(document.getElementById("discountAmount")?.value) || 0;
    const tax = parseFloat(document.getElementById("tax")?.value) || 0;
    const otherFee = parseFloat(document.getElementById("otherFee")?.value) || 0;

    const percentDiscount = subtotal * (discountPercent / 100);
    const totalDiscount = percentDiscount + manualDiscount;
    const grandTotal = Math.max(0, subtotal - totalDiscount + tax + otherFee);

    const totalQtyEl = document.getElementById("totalQty");
    const itemCountEl = document.getElementById("itemCount");
    const subtotalEl = document.getElementById("subtotal");
    const grandTotalEl = document.getElementById("grandTotal");

    if (totalQtyEl) totalQtyEl.innerText = totalQty;
    if (itemCountEl) itemCountEl.innerText = totalQty + " Item";
    if (subtotalEl) subtotalEl.innerText = formatRupiah(subtotal);
    if (grandTotalEl) grandTotalEl.innerText = formatRupiah(grandTotal);

    updateQuickCashButtons(grandTotal);
    calculateChange();
}

function getGrandTotal() {
    let subtotal = 0;
    cart.forEach((item) => {
        subtotal += item.price * item.qty;
    });

    const discountPercent =
        parseFloat(document.getElementById("discountPercent")?.value) || 0;
    const discountAmount =
        parseFloat(document.getElementById("discountAmount")?.value) || 0;
    const tax = parseFloat(document.getElementById("tax")?.value) || 0;
    const otherFee = parseFloat(document.getElementById("otherFee")?.value) || 0;

    const discount = (subtotal * discountPercent) / 100 + discountAmount;
    return Math.max(0, subtotal - discount + tax + otherFee);
}

function calculateChange() {
    const grandTotal = getGrandTotal();
    const paymentInput = document.getElementById("payment");
    const payment = parseFloat(paymentInput ? paymentInput.value : 0) || 0;
    const change = payment - grandTotal;

    const changeBox = document.getElementById("changeBox");
    const changeElement = document.getElementById("change");
    const changeLabel = document.querySelector(".change-label");

    if (!changeBox || !changeElement) return;

    if (change >= 0) {
        changeBox.classList.remove("short-payment");
        changeElement.innerText = formatRupiah(change);
        if (changeLabel) changeLabel.innerText = "KEMBALIAN";
    } else {
        changeBox.classList.add("short-payment");
        changeElement.innerText = formatRupiah(Math.abs(change));
        if (changeLabel) changeLabel.innerText = "UANG KURANG";
    }
}

/* ============================================
   5. QUICK CASH BUTTONS (NOMINAL CEPAT)
   ============================================ */
function setPaymentAmount(amount) {
    const paymentInput = document.getElementById("payment");
    if (!paymentInput) return;
    paymentInput.value = amount;
    calculateChange();
}

function setExactPayment() {
    const total = getGrandTotal();
    setPaymentAmount(total);
}

function updateQuickCashButtons(grandTotal) {
    const container = document.getElementById("quickCashContainer");
    if (!container) return;

    if (grandTotal <= 0) {
        container.innerHTML = `
            <button type="button" class="btn-quick-cash exact" onclick="setExactPayment()">Uang Pas</button>
            <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(10000)">Rp 10.000</button>
            <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(20000)">Rp 20.000</button>
            <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(50000)">Rp 50.000</button>
            <button type="button" class="btn-quick-cash" onclick="setPaymentAmount(100000)">Rp 100.000</button>
        `;
        return;
    }

    // Generate dynamic smart cash suggestions:
    // 1. Exact
    // 2. Next nearest rounded denominations (10k, 20k, 50k, 100k)
    const nominals = [10000, 20000, 50000, 100000, 200000];
    const suggestions = new Set();

    // Round up to nearest 5.000 / 10.000
    if (grandTotal % 10000 !== 0) {
        suggestions.add(Math.ceil(grandTotal / 10000) * 10000);
    }
    if (grandTotal % 50000 !== 0) {
        suggestions.add(Math.ceil(grandTotal / 50000) * 50000);
    }
    if (grandTotal % 100000 !== 0) {
        suggestions.add(Math.ceil(grandTotal / 100000) * 100000);
    }

    nominals.forEach((n) => {
        if (n >= grandTotal) {
            suggestions.add(n);
        }
    });

    const sortedSuggestions = Array.from(suggestions)
        .filter((n) => n > grandTotal)
        .sort((a, b) => a - b)
        .slice(0, 4);

    let html = `<button type="button" class="btn-quick-cash exact" onclick="setExactPayment()">Uang Pas (${formatRupiah(grandTotal)})</button>`;
    sortedSuggestions.forEach((val) => {
        html += `<button type="button" class="btn-quick-cash" onclick="setPaymentAmount(${val})">${formatRupiah(val)}</button>`;
    });

    container.innerHTML = html;
}

/* ============================================
   6. PAYMENT METHOD
   ============================================ */
function selectPayment(button) {
    document.querySelectorAll(".payment-method button").forEach((btn) => {
        btn.classList.remove("active");
    });
    button.classList.add("active");
    selectedPaymentMethod = button.innerText.trim();

    // If non-cash (QRIS, Debit, Kredit, Transfer, E-Wallet), set exact amount
    if (selectedPaymentMethod !== "Tunai") {
        setExactPayment();
    }
}

/* ============================================
   7. CUSTOMER MEMBER CHANGE HANDLER
   ============================================ */
function handleCustomerChange(select) {
    const selectedOption = select.options[select.selectedIndex];
    const customerType = selectedOption.value;
    const discountPercent = parseFloat(selectedOption.getAttribute("data-discount")) || 0;
    const phone = selectedOption.getAttribute("data-phone") || "";

    const phoneInput = document.getElementById("customerPhone");
    if (phoneInput && phone) {
        phoneInput.value = phone;
    }

    const discountPercentInput = document.getElementById("discountPercent");
    if (discountPercentInput) {
        discountPercentInput.value = discountPercent;
        calculateTotal();
    }
}

/* ============================================
   8. HOLD TRANSACTION (PARKIR TRANSAKSI)
   ============================================ */
async function holdTransaction() {
    if (cart.length === 0) {
        alert("Tidak ada transaksi untuk ditahan.");
        return;
    }

    if (!confirm("Tahan transaksi ini untuk melayani pelanggan lain?")) {
        return;
    }

    const customerSelect = document.getElementById("customerSelect");
    const customerPhone = document.getElementById("customerPhone")?.value || "";

    const payload = {
        items: cart,
        customer_id: customerSelect ? customerSelect.value : null,
        customer_name: customerSelect ? customerSelect.options[customerSelect.selectedIndex].text : "Umum",
        customer_phone: customerPhone,
        discount_percent: parseFloat(document.getElementById("discountPercent")?.value) || 0,
        discount_amount: parseFloat(document.getElementById("discountAmount")?.value) || 0,
        tax: parseFloat(document.getElementById("tax")?.value) || 0,
        other_fee: parseFloat(document.getElementById("otherFee")?.value) || 0,
        payment_method: selectedPaymentMethod,
    };

    try {
        const response = await fetch("/kasir/tahan", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            alert(data.message);
            updateHeldBadge(data.held_count);
            // Clear current cart for next customer
            resetPosForm();
        } else {
            alert("Gagal menahan transaksi: " + (data.message || "Terjadi kesalahan."));
        }
    } catch (err) {
        console.error(err);
        alert("Terjadi kesalahan jaringan saat menahan transaksi.");
    }
}

// Open Held Transactions Modal
async function openHeldModal() {
    const modal = document.getElementById("heldModal");
    const listContainer = document.getElementById("heldListContainer");
    if (!modal || !listContainer) return;

    modal.style.display = "flex";
    listContainer.innerHTML = '<div style="text-align:center;padding:20px;">Memuat transaksi ditahan...</div>';

    try {
        const response = await fetch("/kasir/transaksi-ditahan", {
            headers: { Accept: "application/json" },
        });
        const heldTransactions = await response.json();

        if (heldTransactions.length === 0) {
            listContainer.innerHTML = `
                <div style="text-align:center;padding:30px;color:#64748b;">
                    Tidak ada transaksi yang sedang ditahan.
                </div>
            `;
            updateHeldBadge(0);
            return;
        }

        updateHeldBadge(heldTransactions.length);

        listContainer.innerHTML = heldTransactions
            .map((trx) => {
                const itemCount = trx.details ? trx.details.reduce((acc, d) => acc + d.qty, 0) : 0;
                return `
                <div class="held-item">
                    <div>
                        <div class="held-info-title">${trx.transaction_number}</div>
                        <div class="held-info-desc">
                            <strong>${trx.customer_name || 'Umum'}</strong> • ${itemCount} Item • ${formatRupiah(trx.grand_total)}
                            <br>
                            <span style="font-size:11px;color:#94a3b8;">${new Date(trx.created_at).toLocaleTimeString('id-ID')}</span>
                        </div>
                    </div>
                    <div class="held-actions">
                        <button type="button" class="btn btn-primary btn-sm" onclick="resumeHeldTransaction(${trx.id})">
                            Lanjutkan
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteHeldTransaction(${trx.id})">
                            Hapus
                        </button>
                    </div>
                </div>
            `;
            })
            .join("");
    } catch (err) {
        listContainer.innerHTML = '<div style="color:red;padding:20px;">Gagal memuat transaksi ditahan.</div>';
    }
}

function closeHeldModal() {
    const modal = document.getElementById("heldModal");
    if (modal) modal.style.display = "none";
}

async function resumeHeldTransaction(heldId) {
    if (cart.length > 0) {
        if (!confirm("Keranjang saat ini tidak kosong. Menimpa keranjang dengan transaksi yang ditahan ini?")) {
            return;
        }
    }

    try {
        const response = await fetch(`/kasir/transaksi-ditahan/${heldId}/lanjutkan`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
        });
        const data = await response.json();

        if (response.ok && data.success) {
            activeHeldId = data.held_id;
            cart = data.cart || [];

            // Restore inputs
            if (document.getElementById("discountPercent")) {
                document.getElementById("discountPercent").value = data.discount_percent || 0;
            }
            if (document.getElementById("discountAmount")) {
                document.getElementById("discountAmount").value = data.discount_amount || 0;
            }
            if (document.getElementById("tax")) {
                document.getElementById("tax").value = data.tax || 0;
            }
            if (document.getElementById("otherFee")) {
                document.getElementById("otherFee").value = data.other_fee || 0;
            }

            const customerSelect = document.getElementById("customerSelect");
            if (customerSelect && data.customer_id) {
                customerSelect.value = data.customer_id;
            }
            const phoneInput = document.getElementById("customerPhone");
            if (phoneInput && data.customer_phone) {
                phoneInput.value = data.customer_phone;
            }

            // Restore payment method
            if (data.payment_method) {
                document.querySelectorAll(".payment-method button").forEach((btn) => {
                    if (btn.innerText.trim() === data.payment_method) {
                        selectPayment(btn);
                    }
                });
            }

            renderCart();
            closeHeldModal();
            alert(`Transaksi ${data.transaction_number} berhasil dimuat kembali ke kasir.`);
        } else {
            alert("Gagal melanjutkan transaksi: " + (data.message || ""));
        }
    } catch (err) {
        alert("Terjadi kesalahan sistem saat melanjutkan transaksi.");
    }
}

async function deleteHeldTransaction(heldId) {
    if (!confirm("Hapus transaksi ditahan ini secara permanen?")) {
        return;
    }

    try {
        const response = await fetch(`/kasir/transaksi-ditahan/${heldId}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
        });
        const data = await response.json();
        if (response.ok && data.success) {
            updateHeldBadge(data.held_count);
            openHeldModal(); // refresh modal
        }
    } catch (err) {
        alert("Gagal menghapus transaksi ditahan.");
    }
}

function updateHeldBadge(count) {
    const badge = document.getElementById("heldCountBadge");
    if (badge) {
        badge.innerText = count;
        badge.style.display = count > 0 ? "inline-block" : "none";
    }
}

/* ============================================
   9. CANCEL TRANSACTION
   ============================================ */
function cancelTransaction() {
    if (cart.length === 0) {
        return;
    }

    if (!confirm("Batalkan transaksi ini? Semua item di keranjang akan dikosongkan.")) {
        return;
    }

    resetPosForm();
}

function resetPosForm() {
    cart = [];
    activeHeldId = null;

    if (document.getElementById("payment")) {
        document.getElementById("payment").value = "";
    }
    if (document.getElementById("discountPercent")) {
        document.getElementById("discountPercent").value = 0;
    }
    if (document.getElementById("discountAmount")) {
        document.getElementById("discountAmount").value = 0;
    }
    if (document.getElementById("tax")) {
        document.getElementById("tax").value = 0;
    }
    if (document.getElementById("otherFee")) {
        document.getElementById("otherFee").value = 0;
    }
    if (document.getElementById("customerPhone")) {
        document.getElementById("customerPhone").value = "";
    }
    if (document.getElementById("customerSelect")) {
        document.getElementById("customerSelect").selectedIndex = 0;
    }

    // Default to Tunai
    const cashBtn = document.querySelector(".payment-method button:first-child");
    if (cashBtn) selectPayment(cashBtn);

    renderCart();
    if (searchInput) searchInput.focus();
}

/* ============================================
   10. PROCESS PAYMENT & PRINT RECEIPT
   ============================================ */
async function processPayment() {
    if (cart.length === 0) {
        alert("Keranjang masih kosong. Silakan tambahkan barang terlebih dahulu.");
        if (searchInput) searchInput.focus();
        return;
    }

    const grandTotal = getGrandTotal();
    const payment = parseFloat(document.getElementById("payment")?.value) || 0;

    if (payment < grandTotal) {
        alert("Uang pembayaran masih kurang!\n\nTotal: " + formatRupiah(grandTotal) + "\nBayar: " + formatRupiah(payment));
        document.getElementById("payment")?.focus();
        return;
    }

    const change = payment - grandTotal;
    const customerSelect = document.getElementById("customerSelect");

    const payload = {
        items: cart,
        payment: payment,
        payment_method: selectedPaymentMethod,
        discount_percent: parseFloat(document.getElementById("discountPercent")?.value) || 0,
        discount_amount: parseFloat(document.getElementById("discountAmount")?.value) || 0,
        tax: parseFloat(document.getElementById("tax")?.value) || 0,
        other_fee: parseFloat(document.getElementById("otherFee")?.value) || 0,
        customer_id: customerSelect ? customerSelect.value : null,
        customer_name: customerSelect ? customerSelect.options[customerSelect.selectedIndex].text : "Umum",
        customer_phone: document.getElementById("customerPhone")?.value || "",
        held_id: activeHeldId,
    };

    // Show loading on pay button
    const payBtn = document.querySelector(".btn-pay");
    const originalText = payBtn ? payBtn.innerText : "BAYAR & CETAK";
    if (payBtn) {
        payBtn.innerText = "Memproses...";
        payBtn.disabled = true;
    }

    try {
        const response = await fetch("/kasir/transaksi", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Success alert / feedback as in PDF
            alert(
                "Pembayaran berhasil!\n\n" +
                "No. Transaksi : " + data.transaction_number + "\n" +
                "Total : " + formatRupiah(data.total) + "\n" +
                "Bayar : " + formatRupiah(data.payment) + "\n" +
                "Kembali : " + formatRupiah(data.change)
            );

            // Open printable thermal receipt in popup or new tab
            if (data.print_url) {
                const printWindow = window.open(
                    data.print_url,
                    "CetakStruk",
                    "width=420,height=600,scrollbars=yes,resizable=yes"
                );
                if (printWindow) {
                    printWindow.focus();
                }
            }

            // Reset cart & reload next transaction number
            resetPosForm();

            // Refresh transaction number
            fetchNextTransactionNumber();
        } else {
            alert("Pembayaran Gagal: " + (data.message || "Periksa data input."));
        }
    } catch (err) {
        console.error(err);
        alert("Terjadi kesalahan sistem saat memproses transaksi.");
    } finally {
        if (payBtn) {
            payBtn.innerText = originalText;
            payBtn.disabled = false;
        }
    }
}

async function fetchNextTransactionNumber() {
    try {
        const res = await fetch(window.location.href);
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, "text/html");
        const nextTrxNumber = doc.getElementById("transactionNumber")?.innerText;
        if (nextTrxNumber && document.getElementById("transactionNumber")) {
            document.getElementById("transactionNumber").innerText = nextTrxNumber;
        }
    } catch (e) {
        // quiet ignore
    }
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
}

/* ============================================
   11. CURRENT DATE & REALTIME CLOCK
   ============================================ */
function updateClock() {
    const dateEl = document.getElementById("currentDate");
    if (dateEl) {
        const now = new Date();
        dateEl.innerText = now.toLocaleString("id-ID", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
    }
}
setInterval(updateClock, 1000);
updateClock();

/* ============================================
   12. KEYBOARD SHORTCUTS
   ============================================ */
document.addEventListener("keydown", function (event) {
    // F2 = fokus pencarian barang / barcode
    if (event.key === "F2") {
        event.preventDefault();
        if (searchInput) searchInput.focus();
    }
    // F4 = fokus pembayaran
    if (event.key === "F4") {
        event.preventDefault();
        const pInput = document.getElementById("payment");
        if (pInput) {
            pInput.focus();
            pInput.select();
        }
    }
    // ESC = batal transaksi
    if (event.key === "Escape") {
        cancelTransaction();
    }
    // F9 = Bayar & Cetak
    if (event.key === "F9") {
        event.preventDefault();
        processPayment();
    }
});

/* ============================================
   INITIALIZATION
   ============================================ */
document.addEventListener("DOMContentLoaded", function () {
    renderCart();
    if (searchInput) searchInput.focus();
});
