/**
 * Fatura kesme formu: dinamik kalem satırları ve canlı toplam hesabı.
 * window.invoiceProducts verisi Blade şablonundan gelir.
 */
(function () {
    const tbody = document.getElementById('invoice-items');
    if (!tbody) return;

    const products = window.invoiceProducts || [];
    const addRowBtn = document.getElementById('add-row');
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax-amount');
    const totalEl = document.getElementById('grand-total');
    const taxRateSelect = document.getElementById('tax_rate');

    // Satış formu: satış fiyatı + stok kontrolü; alış formu: alış fiyatı, stok kontrolü yok
    const config = Object.assign(
        { priceKey: 'sale_price', stockCheck: true },
        window.invoiceFormConfig || {}
    );

    // PHP'nin items[N][alan] şeklinde satır bazlı gruplaması için sıradaki satır indeksi
    let rowIndex = 0;

    const fmt = (value) =>
        new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ₺';

    function findProduct(id) {
        return products.find((p) => String(p.id) === String(id));
    }

    function buildRow() {
        const i = rowIndex++;
        const row = document.createElement('tr');
        row.className = 'item-row border-t border-slate-100';

        const options = products
            .map((p) => `<option value="${p.id}">${escapeHtml(p.name)}</option>`)
            .join('');

        row.innerHTML = `
            <td class="px-2 py-2">
                <select name="items[${i}][product_id]" class="input product-select">
                    <option value="">— Ürün seçin —</option>
                    ${options}
                </select>
                <div class="stock-hint mt-1 text-[11px] text-slate-400"></div>
            </td>
            <td class="px-2 py-2">
                <input type="number" name="items[${i}][quantity]" class="input quantity" min="0.01" step="any"
                       value="1">
            </td>
            <td class="px-2 py-2">
                <input type="number" name="items[${i}][unit_price]" class="input unit-price" min="0" step="0.01"
                       value="0">
            </td>
            <td class="px-2 py-2 text-right font-semibold text-slate-700 line-total">0,00 ₺</td>
            <td class="px-2 py-2 text-right">
                <button type="button" class="remove-row inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 transition hover:bg-red-50 hover:text-red-700" title="Kalemi kaldır">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </td>`;

        return row;
    }

    function escapeHtml(text) {
        const el = document.createElement('span');
        el.textContent = text;
        return el.innerHTML;
    }

    function recalcRow(row) {
        const product = findProduct(row.querySelector('.product-select').value);
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const lineTotal = quantity * unitPrice;

        row.querySelector('.line-total').textContent = fmt(lineTotal);

        const hint = row.querySelector('.stock-hint');
        if (product && config.stockCheck) {
            const remaining = product.stock_quantity - quantity;
            if (quantity > product.stock_quantity) {
                hint.textContent = `⚠ Yetersiz stok! Mevcut: ${product.stock_quantity} ${product.unit}`;
                hint.className = 'stock-hint mt-1 text-[11px] font-semibold text-red-600';
            } else {
                hint.textContent = `Stok: ${product.stock_quantity} ${product.unit} → kalan: ${remaining} ${product.unit}`;
                hint.className = 'stock-hint mt-1 text-[11px] text-slate-400';
            }
        } else {
            hint.textContent = '';
        }

        recalcTotals();
    }

    function recalcTotals() {
        let subtotal = 0;
        document.querySelectorAll('#invoice-items .item-row').forEach((row) => {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            subtotal += quantity * unitPrice;
        });

        const taxRate = parseFloat(taxRateSelect.value) || 0;
        const taxAmount = (subtotal * taxRate) / 100;
        const total = subtotal + taxAmount;

        subtotalEl.textContent = fmt(subtotal);
        taxEl.textContent = fmt(taxAmount);
        totalEl.textContent = fmt(total);
    }

    function bindRow(row) {
        const productSelect = row.querySelector('.product-select');

        productSelect.addEventListener('change', () => {
            const product = findProduct(productSelect.value);
            if (product) {
                let price = product[config.priceKey] ?? product.sale_price;

                // Cari bazlı özel fiyat: seçili cari için tanımlıysa onu kullan
                if (window.contactPrices && product.id in window.contactPrices) {
                    price = window.contactPrices[product.id];
                }

                row.querySelector('.unit-price').value = price;
            }
            recalcRow(row);
        });

        row.querySelector('.quantity').addEventListener('input', () => recalcRow(row));
        row.querySelector('.unit-price').addEventListener('input', () => recalcRow(row));

        row.querySelector('.remove-row').addEventListener('click', () => {
            row.remove();
            if (tbody.children.length === 0) addRow();
            recalcTotals();
        });
    }

    function addRow() {
        const row = buildRow();
        tbody.appendChild(row);
        bindRow(row);
        return row;
    }

    addRowBtn.addEventListener('click', () => {
        const row = addRow();
        row.querySelector('.product-select').focus();
    });

    taxRateSelect.addEventListener('change', recalcTotals);

    // Cari seçiminde özel fiyatları getir ve mevcut satırları yeniden fiyatlandır
    const contactSelect = document.getElementById('contact_id');
    if (contactSelect) {
        contactSelect.addEventListener('change', async () => {
            if (!contactSelect.value) {
                window.contactPrices = {};
                return;
            }
            try {
                const res = await fetch(`/ara/fiyatlar?contact_id=${contactSelect.value}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                window.contactPrices = data || {};
                // Açık satırları yeni fiyatlara göre güncelle
                document.querySelectorAll('#invoice-items .item-row').forEach((row) => {
                    const productId = row.querySelector('.product-select').value;
                    if (productId && window.contactPrices[productId]) {
                        row.querySelector('.unit-price').value = window.contactPrices[productId];
                        recalcRow(row);
                    }
                });
            } catch (e) {
                window.contactPrices = {};
            }
        });
    }

    // Gönderimden önce tamamen boş satırları (ürün seçilmemiş) formdan çıkar
    document.getElementById('invoice-form').addEventListener('submit', () => {
        document.querySelectorAll('#invoice-items .item-row').forEach((row) => {
            if (!row.querySelector('.product-select').value) {
                row.querySelectorAll('input, select').forEach((el) => el.removeAttribute('required'));
                row.querySelectorAll('input, select').forEach((el) => (el.disabled = true));
            }
        });
    });

    // Başlangıç: mevcut kalemler varsa onları doldur, yoksa iki boş satır
    const prefill = window.invoicePrefill || [];
    if (prefill.length > 0) {
        prefill.forEach((item) => {
            const row = addRow();
            row.querySelector('.product-select').value = item.product_id;
            row.querySelector('.quantity').value = item.quantity;
            row.querySelector('.unit-price').value = item.unit_price;
            recalcRow(row);
        });
    } else {
        addRow();
        addRow();
    }
    recalcTotals();
})();
