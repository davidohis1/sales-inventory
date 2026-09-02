/* =========================================================================
   Public storefront app — theme-agnostic.
   Every theme template shares these optional container IDs; store.js wires
   up whichever ones are present, so one script serves all 5 templates:
     #product-grid      required — where product cards are injected
     #store-search       optional — live text search input
     #cat-filter-list     optional — container for category checkboxes/pills
     #price-min / #price-max / #filter-apply   optional — price range filter
     #cart-count         optional — badge showing items in cart
   ========================================================================= */
const StoreApp = (() => {
    const slug = window.TENANT_SLUG;
    const currency = window.TENANT_CURRENCY || 'NGN';
    const CUR_SYMBOL = currency === 'NGN' ? '\u20a6' : (currency + ' ');
    const fmt = (n) => CUR_SYMBOL + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function toast(msg, type = 'success') {
        const c = document.getElementById('toast-container');
        if (!c) return;
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.textContent = msg;
        c.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    function getCart() { try { return JSON.parse(localStorage.getItem(`cart_${slug}`) || '[]'); } catch (e) { return []; } }
    function setCart(cart) { localStorage.setItem(`cart_${slug}`, JSON.stringify(cart)); updateCartCount(); }
    function updateCartCount() {
        const el = document.getElementById('cart-count');
        if (el) el.textContent = getCart().reduce((s, i) => s + i.qty, 0);
    }

    async function apiGet(path) {
        const res = await fetch(`${window.APP_BASE || ''}/api/${slug}${path}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        return json.data;
    }
    async function apiPost(path, body) {
        const res = await fetch(`${window.APP_BASE || ''}/api/${slug}${path}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        return json.data;
    }

    function assetUrl(path) { return path ? `${window.APP_BASE || ''}${path}` : ''; }

    function imageTag(product, ratio = '1/1') {
        const img = product.primary_image || (product.images && product.images[0] && product.images[0].image_path);
        return img ? `<img src="${esc(assetUrl(img))}" alt="${esc(product.name)}" loading="lazy">` : `<span class="no-image">No image</span>`;
    }

    function cardHtml(p) {
        return `
        <a class="product-card" href="${window.APP_BASE || ''}/${slug}/product/${p.id}" data-id="${p.id}">
            <div class="thumb">${imageTag(p)}
                <button class="wish-btn" data-wish="${p.id}" title="Wishlist" onclick="event.preventDefault()">&#9825;</button>
            </div>
            <div class="info">
                <div class="name">${esc(p.name)}</div>
                <div class="cat-tag">${esc(p.category_name || '')}</div>
                <div class="price-row"><span class="price">${fmt(p.selling_price)}</span>
                    <button class="quick-add" data-quickadd="${p.id}" title="Add to cart" onclick="event.preventDefault()">&#128722;</button>
                </div>
            </div>
        </a>`;
    }

    let allProducts = [];
    let currentQuery = { q: '', category_id: '', min_price: '', max_price: '' };

    async function loadCategories() {
        const listEl = document.getElementById('cat-filter-list');
        if (!listEl) return;
        try {
            const cats = await apiGet('/store/categories');
            const mode = listEl.dataset.mode || 'checkbox'; // 'checkbox' or 'pills'
            if (mode === 'pills') {
                listEl.innerHTML = `<button class="cat-pill ${currentQuery.category_id ? '' : 'active'}" data-cat="">All</button>` +
                    cats.map((c) => `<button class="cat-pill ${String(currentQuery.category_id) === String(c.id) ? 'active' : ''}" data-cat="${c.id}">${esc(c.name)}</button>`).join('');
                listEl.querySelectorAll('.cat-pill').forEach((btn) => btn.addEventListener('click', () => {
                    listEl.querySelectorAll('.cat-pill').forEach((b) => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentQuery.category_id = btn.dataset.cat;
                    loadProducts();
                }));
            } else {
                listEl.innerHTML = cats.map((c) => `
                    <label class="cat-checkbox"><input type="checkbox" value="${c.id}" ${String(currentQuery.category_id) === String(c.id) ? 'checked' : ''}> ${esc(c.name)}</label>`).join('');
                listEl.querySelectorAll('input[type=checkbox]').forEach((cb) => cb.addEventListener('change', () => {
                    const checked = [...listEl.querySelectorAll('input:checked')];
                    // single-select behaviour for simplicity: checking one unchecks the rest
                    if (cb.checked) {
                        checked.forEach((other) => { if (other !== cb) other.checked = false; });
                        currentQuery.category_id = cb.value;
                    } else {
                        currentQuery.category_id = '';
                    }
                    loadProducts();
                }));
            }
        } catch (e) { /* categories are optional decoration — fail quietly */ }
    }

    async function loadProducts() {
        const grid = document.getElementById('product-grid');
        if (!grid) return;
        grid.innerHTML = '<div class="grid-loading">Loading products…</div>';
        const params = new URLSearchParams();
        if (currentQuery.q) params.set('q', currentQuery.q);
        if (currentQuery.category_id) params.set('category_id', currentQuery.category_id);
        if (currentQuery.min_price) params.set('min_price', currentQuery.min_price);
        if (currentQuery.max_price) params.set('max_price', currentQuery.max_price);

        try {
            const data = await apiGet(`/store/products?${params.toString()}`);
            allProducts = data.products;
            const countEl = document.getElementById('result-count');
            if (countEl) countEl.textContent = `${allProducts.length} product${allProducts.length === 1 ? '' : 's'}`;
            if (allProducts.length === 0) {
                grid.innerHTML = '<div class="empty-store">No products match your filters right now. Try adjusting your search.</div>';
                return;
            }
            grid.innerHTML = allProducts.map(cardHtml).join('');
            grid.querySelectorAll('[data-quickadd]').forEach((btn) => btn.addEventListener('click', () => {
                const p = allProducts.find((x) => x.id == btn.dataset.quickadd);
                if (p) { addToCart(p, 1); toast('Added to cart'); }
            }));
        } catch (e) {
            grid.innerHTML = `<div class="empty-store">${esc(e.message)}</div>`;
        }
    }

    function addToCart(p, qty) {
        const cart = getCart();
        const existing = cart.find((c) => c.id == p.id);
        const image = p.primary_image || (p.images && p.images[0] && p.images[0].image_path) || null;
        if (existing) existing.qty += qty;
        else cart.push({ id: p.id, name: p.name, price: p.selling_price, qty, image });
        setCart(cart);
    }

    function wireSearchAndFilters() {
        const searchInput = document.getElementById('store-search');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(debounce);
                currentQuery.q = e.target.value;
                debounce = setTimeout(loadProducts, 300);
            });
        }
        const applyBtn = document.getElementById('filter-apply');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                const minEl = document.getElementById('price-min');
                const maxEl = document.getElementById('price-max');
                currentQuery.min_price = minEl ? minEl.value : '';
                currentQuery.max_price = maxEl ? maxEl.value : '';
                loadProducts();
            });
        }
    }

    /* ---------------------------------------------------------------
       PAGE ENTRY POINTS (called by each theme/page template)
       --------------------------------------------------------------- */
    function renderProductList() {
        updateCartCount();
        // Footer/category links land here as ?category_id=N — pre-select that filter.
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('category_id')) currentQuery.category_id = urlParams.get('category_id');
        wireSearchAndFilters();
        loadCategories();
        loadProducts();
    }

    async function renderProductDetail(id) {
        updateCartCount();
        const root = document.getElementById('product-detail-root');
        try {
            const p = await apiGet(`/store/products/${id}`);
            const images = (p.images && p.images.length ? p.images : [{ image_path: null }]);
            root.innerHTML = `
            <div class="product-detail">
                <div>
                    <div class="gallery-main">${images[0].image_path ? `<img src="${esc(assetUrl(images[0].image_path))}">` : '<span class="no-image">No image available</span>'}</div>
                    ${images.length > 1 ? `<div class="gallery-thumbs">${images.map((im, i) => `<div class="gallery-thumb ${i === 0 ? 'active' : ''}" data-src="${esc(assetUrl(im.image_path))}">${im.image_path ? `<img src="${esc(assetUrl(im.image_path))}">` : ''}</div>`).join('')}</div>` : ''}
                </div>
                <div>
                    <h1>${esc(p.name)}</h1>
                    <p class="text-muted">${esc(p.description || 'No description provided yet.')}</p>
                    <div class="price">${fmt(p.selling_price)}</div>
                    <p class="text-muted">${p.quantity} in stock</p>
                    <div class="qty-stepper"><button id="q-dec">−</button><input id="q-val" value="1" readonly><button id="q-inc">+</button></div>
                    <div style="margin-top:16px;"><button class="btn-store" id="add-cart-btn">Add to Cart</button></div>
                </div>
            </div>

            <div class="product-tabs">
                <button class="product-tab-btn active" data-tab="description">Description</button>
                <button class="product-tab-btn" data-tab="reviews">Reviews <span id="review-count-badge"></span></button>
            </div>
            <div class="product-tab-panel" id="tab-description">
                <p>${esc(p.description || 'No description provided yet.')}</p>
            </div>
            <div class="product-tab-panel" id="tab-reviews" style="display:none;">
                <div id="reviews-list"><p class="text-muted">Loading reviews…</p></div>
                <div class="review-form-box">
                    <h4>Write a Review</h4>
                    <form id="review-form">
                        <div class="form-row">
                            <div class="form-group"><label>Your Name</label><input name="name" required></div>
                            <div class="form-group"><label>Your Email</label><input name="email" type="email" required></div>
                        </div>
                        <div class="form-group"><label>Your Review</label><textarea name="review" rows="3" required></textarea></div>
                        <button class="btn-store" type="submit">Submit Review</button>
                    </form>
                    <p class="text-muted review-privacy-note">Your email is never shown publicly — only your name and review.</p>
                </div>
            </div>`;

            root.querySelectorAll('.gallery-thumb').forEach((t) => t.addEventListener('click', () => {
                root.querySelectorAll('.gallery-thumb').forEach((x) => x.classList.remove('active'));
                t.classList.add('active');
                root.querySelector('.gallery-main').innerHTML = `<img src="${esc(t.dataset.src)}">`;
            }));
            let qty = 1;
            document.getElementById('q-inc').addEventListener('click', () => { qty = Math.min(p.quantity, qty + 1); document.getElementById('q-val').value = qty; });
            document.getElementById('q-dec').addEventListener('click', () => { qty = Math.max(1, qty - 1); document.getElementById('q-val').value = qty; });
            document.getElementById('add-cart-btn').addEventListener('click', () => { addToCart(p, qty); toast('Added to cart'); });

            root.querySelectorAll('.product-tab-btn').forEach((btn) => btn.addEventListener('click', () => {
                root.querySelectorAll('.product-tab-btn').forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                root.querySelectorAll('.product-tab-panel').forEach((panel) => panel.style.display = 'none');
                document.getElementById(`tab-${btn.dataset.tab}`).style.display = 'block';
            }));

            loadReviews(id);
            document.getElementById('review-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = Object.fromEntries(new FormData(e.target).entries());
                const submitBtn = e.target.querySelector('button[type=submit]');
                submitBtn.disabled = true;
                try {
                    await apiPost(`/store/products/${id}/reviews`, fd);
                    toast('Thanks for your review!');
                    e.target.reset();
                    loadReviews(id);
                } catch (err) {
                    toast(err.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                }
            });
        } catch (e) {
            root.innerHTML = `<p class="text-muted">${esc(e.message)}</p>`;
        }
    }

    async function loadReviews(productId) {
        const listEl = document.getElementById('reviews-list');
        const badge = document.getElementById('review-count-badge');
        try {
            const reviews = await apiGet(`/store/products/${productId}/reviews`);
            if (badge) badge.textContent = reviews.length ? `(${reviews.length})` : '';
            listEl.innerHTML = reviews.length
                ? reviews.map((r) => `
                    <div class="review-item">
                        <div class="review-item-head"><strong>${esc(r.reviewer_name)}</strong><span class="text-muted">${new Date(r.created_at.replace(' ', 'T')).toLocaleDateString()}</span></div>
                        <p>${esc(r.review_text)}</p>
                    </div>`).join('')
                : '<p class="text-muted">No reviews yet — be the first to write one!</p>';
        } catch (e) {
            listEl.innerHTML = `<p class="text-muted">${esc(e.message)}</p>`;
        }
    }

    function renderCartPage() {
        updateCartCount();
        const root = document.getElementById('cart-root');
        function draw() {
            const cart = getCart();
            if (cart.length === 0) { root.innerHTML = '<div class="empty-store">Your cart is empty. <a href="/' + slug + '">Continue shopping</a></div>'; return; }
            const rows = cart.map((c, idx) => `
                <div class="cart-row">
                    <div class="thumb">${c.image ? `<img src="${esc(assetUrl(c.image))}">` : ''}</div>
                    <div class="grow"><strong>${esc(c.name)}</strong><br><span class="text-muted">${fmt(c.price)} each</span></div>
                    <div class="qty-stepper"><button data-dec="${idx}">−</button><input value="${c.qty}" readonly><button data-inc="${idx}">+</button></div>
                    <div>${fmt(c.price * c.qty)}</div>
                    <button data-remove="${idx}" class="btn-store outline" style="padding:6px 12px;">Remove</button>
                </div>`).join('');
            const subtotal = cart.reduce((s, c) => s + c.price * c.qty, 0);
            root.innerHTML = rows + `
                <div class="cart-summary">
                    <div style="display:flex; justify-content:space-between; font-size:18px; font-weight:700;"><span>Total</span><span>${fmt(subtotal)}</span></div>
                    <button class="btn-store" style="width:100%; margin-top:14px;" onclick="window.location.href='${window.APP_BASE || ''}/${slug}/checkout'">Proceed to Checkout</button>
                </div>`;
            root.querySelectorAll('[data-inc]').forEach((b) => b.addEventListener('click', () => { cart[b.dataset.inc].qty++; setCart(cart); draw(); }));
            root.querySelectorAll('[data-dec]').forEach((b) => b.addEventListener('click', () => { if (cart[b.dataset.dec].qty > 1) cart[b.dataset.dec].qty--; setCart(cart); draw(); }));
            root.querySelectorAll('[data-remove]').forEach((b) => b.addEventListener('click', () => { cart.splice(b.dataset.remove, 1); setCart(cart); draw(); }));
        }
        draw();
    }

    function renderCheckoutPage() {
        updateCartCount();
        const root = document.getElementById('checkout-root');
        const cart = getCart();
        if (cart.length === 0) { root.innerHTML = '<div class="empty-store">Your cart is empty.</div>'; return; }
        const subtotal = cart.reduce((s, c) => s + c.price * c.qty, 0);
        const cfg = window.STORE_CHECKOUT_CONFIG || { orderChannel: 'email' };

        root.innerHTML = `
        <div class="checkout-form">
            <p><strong>Order total: ${fmt(subtotal)}</strong></p>
            <form id="checkout-form">
                <div class="form-group"><label>Full Name</label><input name="name" required></div>
                <div class="form-group"><label>Phone</label><input name="phone" required></div>
                <div class="form-group"><label>Email</label><input name="email" type="email" required></div>
                <div class="form-group"><label>Delivery Address</label><textarea name="address" rows="3" required></textarea></div>
                <button class="btn-store" style="width:100%;" type="submit">Place Order</button>
            </form>
        </div>`;

        document.getElementById('checkout-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            fd.items = cart.map((c) => ({ product_id: c.id, quantity: c.qty }));
            try {
                const result = await apiPost('/store/order', fd);
                localStorage.removeItem(`cart_${slug}`);
                renderOrderConfirmation(root, result, cfg);
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    }

    /**
     * What the customer sees right after placing an order — varies by the
     * store's chosen notification channel (set by the admin in Store Settings):
     *   - 'whatsapp'      -> a click-to-chat WhatsApp button with the order pre-filled
     *   - 'bank_transfer' -> account details + an "I Have Paid" button
     *   - 'email' (default) -> a plain confirmation (an email has already been sent)
     * In every case, a "Pay with Flutterwave" button is also offered as the
     * fastest way to pay instantly by card/bank/USSD.
     */
    function renderOrderConfirmation(root, result, cfg) {
        const storeLink = `${window.APP_BASE || ''}/${slug}`;
        const base = `<h2>Thank you!</h2><p>Your order <strong>${esc(result.order_no)}</strong> has been placed. A confirmation email is on its way to you.</p>`;
        const flwBlock = `
            <button class="btn-store" style="width:100%; margin-top:14px; background:#f5a623;" id="flw-pay-btn">&#9889; Pay ${fmt(result.total)} with Flutterwave</button>
            <div id="flw-pay-status" style="margin-top:10px;"></div>`;

        function wireFlutterwaveButton() {
            const btn = document.getElementById('flw-pay-btn');
            if (!btn) return;
            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.textContent = 'Starting checkout…';
                try {
                    const pay = await apiPost(`/store/order/${result.id}/pay`, {});
                    if (pay.simulated) {
                        document.getElementById('flw-pay-status').innerHTML = `
                            <p class="text-muted" style="font-size:13px;">Flutterwave isn't configured on this store yet (dev mode).</p>
                            <button class="btn-store outline" id="flw-simulate-btn" style="width:100%;">Simulate Successful Payment</button>`;
                        document.getElementById('flw-simulate-btn').addEventListener('click', async (e) => {
                            e.target.disabled = true; e.target.textContent = 'Confirming…';
                            try {
                                await apiPost(`/store/order/${result.id}/pay/verify`, { tx_ref: pay.tx_ref });
                                document.getElementById('flw-pay-status').innerHTML = '<p style="color:#16a34a; font-weight:600;">Payment confirmed! The store has been notified.</p>';
                                btn.style.display = 'none';
                            } catch (err) { toast(err.message, 'error'); }
                        });
                        btn.disabled = false; btn.textContent = `\u26A1 Pay ${fmt(result.total)} with Flutterwave`;
                        return;
                    }
                    window.location.href = pay.link;
                } catch (err) {
                    toast(err.message, 'error');
                    btn.disabled = false; btn.textContent = `\u26A1 Pay ${fmt(result.total)} with Flutterwave`;
                }
            });
        }

        if (cfg.orderChannel === 'whatsapp' && cfg.whatsappNumber) {
            const digits = String(cfg.whatsappNumber).replace(/[^\d+]/g, '');
            const text = encodeURIComponent(`Hi! I just placed order ${result.order_no} for ${fmt(result.total)}. I'd like to arrange payment and delivery.`);
            root.innerHTML = `<div class="empty-store">${base}
                <a class="btn-store" href="https://wa.me/${digits}?text=${text}" target="_blank" style="background:#25D366; margin-top:10px;">&#128172; Chat on WhatsApp to Complete Your Order</a>
                ${flwBlock}
                <div style="margin-top:12px;"><a class="btn-store outline" href="${storeLink}">Continue Shopping</a></div>
            </div>`;
            wireFlutterwaveButton();
            return;
        }

        if (cfg.orderChannel === 'bank_transfer' && (cfg.bankAccountNumber || cfg.bankName)) {
            root.innerHTML = `<div class="checkout-form">
                <h2 style="margin-top:0;">Thank you!</h2>
                <p>Your order <strong>${esc(result.order_no)}</strong> (${fmt(result.total)}) has been placed. Pay instantly with Flutterwave, or transfer manually using the account details below and click "I Have Paid".</p>
                ${flwBlock}
                <div class="bank-details-box" style="margin-top:16px;">
                    ${cfg.bankName ? `<div><span>Bank</span><strong>${esc(cfg.bankName)}</strong></div>` : ''}
                    ${cfg.bankAccountName ? `<div><span>Account Name</span><strong>${esc(cfg.bankAccountName)}</strong></div>` : ''}
                    ${cfg.bankAccountNumber ? `<div><span>Account Number</span><strong>${esc(cfg.bankAccountNumber)}</strong></div>` : ''}
                    <div><span>Amount</span><strong>${fmt(result.total)}</strong></div>
                </div>
                <button class="btn-store outline" style="width:100%; margin-top:14px;" id="paid-btn">I Have Paid by Transfer</button>
                <div id="paid-confirm" style="margin-top:12px;"></div>
            </div>`;
            wireFlutterwaveButton();
            document.getElementById('paid-btn').addEventListener('click', async (e) => {
                e.target.disabled = true;
                e.target.textContent = 'Notifying store…';
                try {
                    const msg = await apiPost(`/store/order/${result.id}/mark-paid`, {});
                    document.getElementById('paid-confirm').innerHTML = `<p style="color:#16a34a; font-weight:600;">${esc(msg.text || "Thanks! We've been notified and will confirm your payment shortly.")}</p>
                        <a class="btn-store outline" href="${storeLink}">Continue Shopping</a>`;
                    e.target.style.display = 'none';
                } catch (err) {
                    toast(err.message, 'error');
                    e.target.disabled = false;
                    e.target.textContent = 'I Have Paid by Transfer';
                }
            });
            return;
        }

        root.innerHTML = `<div class="empty-store">${base}${flwBlock}<div style="margin-top:12px;"><a class="btn-store outline" href="${storeLink}">Continue Shopping</a></div></div>`;
        wireFlutterwaveButton();
    }

    return { renderProductList, renderProductDetail, renderCartPage, renderCheckoutPage };
})();