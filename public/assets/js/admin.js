/* =========================================================================
   Admin Portal SPA — vanilla JS, no reloads on navigation.
   Uses history.pushState + a client-side router; every "page" fetches its
   own data from the JSON API (api.js) and re-renders #app's #content div.
   ========================================================================= */
(function () {
    const slug = window.TENANT_SLUG;
    const currency = window.TENANT_CURRENCY || 'NGN';
    const CUR_SYMBOL = currency === 'NGN' ? '\u20a6' : (currency + ' ');

    const fmt = (n) => CUR_SYMBOL + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const dt = (s) => s ? new Date(s.replace(' ', 'T')).toLocaleString() : '';
    const assetUrl = (path) => path ? `${window.APP_BASE || ''}${path}` : '';

    function toast(msg, type = 'success') {
        const c = document.getElementById('toast-container');
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.textContent = msg;
        c.appendChild(el);
        setTimeout(() => el.remove(), 3800);
    }

    function currentUser() {
        try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch (e) { return null; }
    }

    /* ---------------------------------------------------------------
       ROUTES
       --------------------------------------------------------------- */
    const ROUTES = [
        { path: '', label: 'Dashboard', icon: '&#9632;', roles: null, render: renderDashboard },
        { path: 'pos', label: 'Sales / POS', icon: '&#128179;', roles: null, render: renderPOS },
        { path: 'products', label: 'Products & Inventory', icon: '&#128230;', roles: null, render: renderProducts },
        { path: 'customers', label: 'Customers & Debt', icon: '&#128100;', roles: null, render: renderCustomers },
        { path: 'expenses', label: 'Expenses', icon: '&#128176;', roles: null, render: renderExpenses },
        { path: 'orders', label: 'Online Orders', icon: '&#128722;', roles: null, render: renderOrders },
        { path: 'store', label: 'Store Page', icon: '&#127968;', roles: ['owner', 'manager'], render: renderStorePage },
        { path: 'earnings', label: 'Earnings', icon: '&#128176;', roles: ['owner', 'manager'], render: renderEarnings },
        { path: 'staff', label: 'Staff', icon: '&#128101;', roles: ['owner', 'manager'], render: renderStaff },
        { path: 'branches', label: 'Branches', icon: '&#127970;', roles: ['owner', 'manager'], render: renderBranches },
        { path: 'reports', label: 'Reports', icon: '&#128202;', roles: ['owner', 'manager'], render: renderReports },
        { path: 'plans', label: 'Plans & Billing', icon: '&#128179;', roles: null, render: renderPlans },
    ];

    function portalPath(sub) {
        return `${window.APP_BASE || ''}/${slug}portal${sub ? '/' + sub : ''}`;
    }

    function currentSub() {
        const prefix = `${window.APP_BASE || ''}/${slug}portal`;
        const p = window.location.pathname.replace(prefix, '').replace(/^\//, '');
        return p;
    }

    async function navigate(sub, push = true) {
        const route = ROUTES.find((r) => r.path === sub) || ROUTES[0];
        if (route.roles && !route.roles.includes((currentUser() || {}).role)) {
            toast("You don't have permission to view that page.", 'error');
            return navigate('', true);
        }
        if (push) history.pushState({ sub: route.path }, '', portalPath(route.path));
        highlightNav(route.path);
        document.getElementById('page-title').textContent = route.label;
        const content = document.getElementById('content');
        content.innerHTML = '<div class="empty-state"><div class="spinner"></div><p>Loading…</p></div>';
        try {
            await route.render(content);
        } catch (e) {
            content.innerHTML = `<div class="card"><p class="text-muted">Couldn't load this page: ${esc(e.message)}</p></div>`;
        }
    }

    function highlightNav(path) {
        document.querySelectorAll('.nav-item').forEach((el) => {
            el.classList.toggle('active', el.dataset.path === path);
        });
    }

    window.addEventListener('popstate', () => navigate(currentSub(), false));

    /* ---------------------------------------------------------------
       SHELL (sidebar + topbar) — rendered once after login
       --------------------------------------------------------------- */
    const MAIN_PATHS = ['', 'pos'];
    const FEATURE_PATHS = ['products', 'customers', 'expenses', 'orders', 'store', 'earnings', 'branches'];
    const GENERAL_PATHS = ['staff', 'reports'];

    function planInfo() {
        try { return JSON.parse(localStorage.getItem('plan') || 'null'); } catch (e) { return null; }
    }

    function isPathLocked(path) {
        const plan = planInfo();
        if (!plan || !plan.locked_features) return false;
        // 'earnings' rides on the Online Store feature — no separate plan_features row for it.
        const key = path === 'earnings' ? 'store' : path;
        return plan.locked_features.includes(key);
    }

    function navGroupHtml(paths, user) {
        return ROUTES.filter((r) => paths.includes(r.path) && (!r.roles || r.roles.includes(user.role)))
            .map((r) => {
                const locked = isPathLocked(r.path);
                return `<div class="nav-item${locked ? ' locked' : ''}" data-path="${r.path}" data-locked="${locked ? '1' : '0'}">
                    <span class="nav-icon">${r.icon}</span> ${r.label}
                    ${locked ? '<span class="nav-lock" title="Upgrade to unlock">&#128274;</span>' : ''}
                </div>`;
            }).join('');
    }

    function renderShell() {
        const user = currentUser() || {};
        const plan = planInfo();
        const initials = (user.full_name || '?').trim().split(/\s+/).map((s) => s[0]).slice(0, 2).join('').toUpperCase();

        document.getElementById('app').innerHTML = `
        <div class="app-shell">
            <div class="sidebar" id="sidebar">
                <div class="sidebar-brand"><span class="logo-dot"></span> ${esc(window.TENANT_NAME)}</div>
                <div class="nav-section-label">Main Menu</div>
                <div class="nav-group">${navGroupHtml(MAIN_PATHS, user)}</div>
                <div class="nav-section-label">Features</div>
                <div class="nav-group">${navGroupHtml(FEATURE_PATHS, user)}</div>
                <div class="nav-section-label">General</div>
                <div class="nav-group">${navGroupHtml(GENERAL_PATHS, user)}</div>
                <div class="sidebar-footer">
                    ${plan && plan.name !== 'premium' ? `
                    <div class="upgrade-card">
                        <div class="upgrade-title">Upgrade your plan &#9889;</div>
                        <div class="upgrade-sub">Unlock more features and grow faster.</div>
                        <button class="btn" id="sidebar-upgrade-btn">Upgrade</button>
                    </div>` : ''}
                    <div class="sidebar-user">
                        <div class="avatar">${esc(initials)}</div>
                        <div>
                            <div class="user-name">${esc(user.full_name || '')}</div>
                            <div class="user-role">${esc((user.role || '').toUpperCase())}</div>
                        </div>
                        <button class="logout-btn" id="logout-btn" title="Log out">&#10140;</button>
                    </div>
                </div>
            </div>
            <div class="main">
                <div class="topbar">
                    <button class="menu-toggle" id="menu-toggle">&#9776;</button>
                    <h1 id="page-title">Dashboard</h1>
                    <div class="topbar-search"><span>&#128269;</span><span>Search…</span></div>
                    <div class="topbar-right">
                        <button class="topbar-icon-btn" title="Help">?</button>
                        <button class="topbar-icon-btn" title="Messages">&#9993;</button>
                        <button class="topbar-icon-btn" title="Notifications">&#128276;</button>
                        <div class="topbar-avatar" title="${esc(user.full_name || '')}">${esc(initials)}</div>
                    </div>
                </div>
                <div id="content"></div>
            </div>
        </div>`;

        document.querySelectorAll('.nav-item').forEach((el) => {
            el.addEventListener('click', () => {
                document.getElementById('sidebar').classList.remove('open');
                if (el.dataset.locked === '1') {
                    toast("That feature isn't included in your current plan.", 'error');
                    return navigate('plans');
                }
                navigate(el.dataset.path);
            });
        });
        const upgradeBtn = document.getElementById('sidebar-upgrade-btn');
        if (upgradeBtn) upgradeBtn.addEventListener('click', () => navigate('plans'));
        document.getElementById('menu-toggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
        document.getElementById('logout-btn').addEventListener('click', () => {
            Api.clearTokens();
            renderLogin();
        });
    }

    /* ---------------------------------------------------------------
       LOGIN
       --------------------------------------------------------------- */
    function renderLogin() {
        document.getElementById('app').innerHTML = `
        <div class="login-page">
            <div class="login-card">
                <h2>${esc(window.TENANT_NAME)}</h2>
                <p class="text-muted mt-0">Sign in to the admin portal</p>
                <form id="login-form">
                    <div class="form-group"><label>Email</label><input class="form-control" type="email" id="login-email" required></div>
                    <div class="form-group"><label>Password</label><input class="form-control" type="password" id="login-password" required></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Log In</button>
                </form>
                <div class="demo-hint">Demo accounts (seeded):<br>owner@ajtech.com / password123<br>manager@ajtech.com / password123<br>staff@ajtech.com / password123</div>
            </div>
        </div>`;

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;
            try {
                const data = await Api.post('/auth/login', { email, password }, { noAuth: true });
                Api.setTokens(data.access_token, data.refresh_token);
                localStorage.setItem('user', JSON.stringify(data.user));
                boot();
            } catch (err) {
                toast(err.message, 'error');
            }
        });
    }

    /* ---------------------------------------------------------------
       DASHBOARD
       --------------------------------------------------------------- */
    async function renderDashboard(content) {
        const d = await Api.get('/dashboard');
        const user = currentUser() || {};
        const payments = d.payment_breakdown || [];
        const bestSellers = (d.best_sellers || []).slice(0, 5);
        const todayLabel = new Date().toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        // Build a simple 12-bar overview chart from whatever monthly trend the API gives us,
        // falling back to a flat series so the card never looks broken with no data.
        const trend = (d.monthly_trend && d.monthly_trend.length === 12) ? d.monthly_trend
            : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'].map((m, i) => ({ month: m, total: i === new Date().getMonth() ? (d.today_revenue || 0) * 20 : 0 }));
        const maxVal = Math.max(1, ...trend.map((t) => Number(t.total || 0)));
        const bars = trend.map((t, i) => {
            const h = Math.max(4, Math.round((Number(t.total || 0) / maxVal) * 150));
            const isPeak = Number(t.total || 0) === maxVal && maxVal > 0;
            return `<div class="bar-col${isPeak ? ' peak' : ''}" title="${esc(t.month)}: ${fmt(t.total)}">
                <div class="bar" style="height:${h}px;"></div>
                <div class="bar-label">${esc(t.month)}</div>
            </div>`;
        }).join('');

        const walletCards = payments.length ? payments.slice(0, 4).map((p) => `
            <div class="wallet-mini">
                <div class="wm-top"><span>${esc(p.method)}</span></div>
                <div class="wm-value">${fmt(p.total)}</div>
                <div class="wm-status active">Today</div>
            </div>`).join('') : `
            <div class="wallet-mini" style="grid-column: 1 / -1;"><span class="text-muted">No payments recorded today yet.</span></div>`;

        const txRows = bestSellers.length ? bestSellers.map((b) => `
            <tr>
                <td><span class="tx-icon">&#128230;</span>${esc(b.name)}</td>
                <td>${b.total_qty} sold</td>
                <td>${fmt(b.total_revenue)}</td>
                <td><span class="status-pill">Active</span></td>
            </tr>`).join('') : `<tr><td colspan="4" class="text-muted">No sales yet — make your first sale from Quick Sale or POS.</td></tr>`;

        content.innerHTML = `
        <div class="page-header">
            <div>
                <h2>Welcome back, ${esc((user.full_name || 'there').split(' ')[0])}</h2>
                <p>Monitor and control what happens with your business today.</p>
            </div>
            <div class="page-header-actions">
                <span class="date-pill">&#128197; ${todayLabel}</span>
                <button class="btn btn-dark" id="quick-sale-btn">&#9889; Quick Sale</button>
            </div>
        </div>

        <div class="grid grid-3" style="margin-bottom:18px;">
            <div class="card stat-card">
                <div class="card-head"><div class="card-icon">&#128176;</div><button class="card-menu-dots">&#8942;</button></div>
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value">${fmt(d.today_revenue)}</div>
                <div class="stat-sub"><span class="trend-pill up">&#8593; ${d.today_sales_count || 0}</span> sale(s) today</div>
            </div>
            <div class="card stat-card">
                <div class="card-head"><div class="card-icon">&#128200;</div><button class="card-menu-dots">&#8942;</button></div>
                <div class="stat-label">Today's Profit</div>
                <div class="stat-value">${fmt(d.today_profit)}</div>
                <div class="stat-sub">Revenue − COGS − expenses</div>
            </div>
            <div class="card stat-card ${d.low_stock_count > 0 ? 'warn' : ''}">
                <div class="card-head"><div class="card-icon">&#128230;</div><button class="card-menu-dots">&#8942;</button></div>
                <div class="stat-label">Stock Value</div>
                <div class="stat-value">${fmt(d.stock_value)}</div>
                <div class="stat-sub">${d.low_stock_count > 0 ? `<span class="trend-pill down">&#9888; ${d.low_stock_count} low</span>` : ''} ${d.out_of_stock_count > 0 ? `${d.out_of_stock_count} out of stock` : 'All items stocked'}</div>
            </div>
        </div>

        <div class="dash-grid">
            <div class="card chart-card">
                <div class="card-head">
                    <div class="card-head-title"><span class="card-icon">&#128202;</span> Overview</div>
                    <div class="chart-legend"><span class="dot"></span> Revenue by month</div>
                </div>
                <div class="bar-chart">${bars}</div>
            </div>
            <div class="card">
                <div class="card-head-title" style="margin-bottom:14px;"><span class="card-icon">&#128179;</span> Payment Breakdown (Today)</div>
                <div class="wallet-grid">${walletCards}</div>
            </div>
        </div>

        <div class="dash-grid-lower">
            <div>
                <div class="section-title">Best-Selling Products <span class="text-muted" style="font-weight:400; font-size:12px;">Last 30 days</span></div>
                <div class="table-wrap"><table class="tx-table"><thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th><th>Status</th></tr></thead><tbody>${txRows}</tbody></table></div>
            </div>
            <div>
                <div class="section-title">AI Insights</div>
                <div class="ai-widget" id="ai-widget">
                    <h3>&#10024; Business Insights <span class="ai-tag">AI</span></h3>
                    <p style="opacity:0.85; margin: 6px 0 0; font-size:13px;">A plain-language summary of your trends, slow stock, and margins.</p>
                    <div id="ai-body"><div class="spinner" style="border-color: rgba(255,255,255,0.3); border-top-color:#fff; margin-top:16px;"></div></div>
                    <button class="btn" id="ai-refresh">Regenerate Insights</button>
                </div>
            </div>
        </div>
        <div id="quick-sale-modal-root"></div>`;

        loadAiInsights();
        document.getElementById('ai-refresh').addEventListener('click', loadAiInsights);
        document.getElementById('quick-sale-btn').addEventListener('click', () => openQuickSaleModal(() => renderDashboard(content)));
    }

    /* ---------------------------------------------------------------
       QUICK SALE (Dashboard) — compact product search + cart, with a
       manually-typed customer name (never a dropdown). Typing searches
       existing customers as suggestions; picking none creates a new
       customer record together with the sale.
       --------------------------------------------------------------- */
    let qsCart = [];
    let qsSelectedCustomerId = null;

    function openQuickSaleModal(onDone) {
        qsCart = [];
        qsSelectedCustomerId = null;
        document.getElementById('quick-sale-modal-root').innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal" style="max-width:720px;">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>Quick Sale</h3>
                <div class="quick-sale-grid">
                    <div>
                        <input class="form-control" id="qs-search" placeholder="Search products…" style="margin-bottom:10px;">
                        <div class="pos-product-grid" id="qs-grid" style="max-height:280px; overflow-y:auto;"></div>
                    </div>
                    <div>
                        <div id="qs-cart-items"><p class="text-muted">Cart is empty.</p></div>
                        <div class="form-group autocomplete-wrap" style="margin-top:12px;">
                            <label>Customer Name</label>
                            <input class="form-control" id="qs-customer-name" placeholder="Type customer name…" autocomplete="off">
                            <div class="autocomplete-list" id="qs-customer-suggestions" style="display:none;"></div>
                        </div>
                        <div class="form-group"><label>Discount</label><input class="form-control" id="qs-discount" type="number" value="0" min="0"></div>
                        <div class="form-group"><label>Payment Method</label>
                            <select class="form-control" id="qs-method">
                                <option value="cash">Cash</option><option value="transfer">Transfer</option>
                                <option value="pos">POS/Card</option><option value="credit">Credit (pay later)</option>
                            </select>
                        </div>
                        <div class="cart-totals">
                            <div class="flex-between grand-total"><span>Total</span><span id="qs-total">${fmt(0)}</span></div>
                        </div>
                        <button class="btn" style="width:100%; justify-content:center; margin-top:12px;" id="qs-complete-btn">Complete Sale</button>
                    </div>
                </div>
            </div>
        </div>`;

        const close = () => {
            document.removeEventListener('click', outsideClickHandler);
            document.getElementById('quick-sale-modal-root').innerHTML = '';
        };
        function outsideClickHandler(e) {
            const box = document.getElementById('qs-customer-suggestions');
            if (box && !e.target.closest('.autocomplete-wrap')) box.style.display = 'none';
        }
        document.getElementById('modal-close').addEventListener('click', close);

        async function loadGrid(q = '') {
            const products = await Api.get(`/products?q=${encodeURIComponent(q)}`);
            document.getElementById('qs-grid').innerHTML = products.map((p) => `
                <div class="pos-product-card" data-id="${p.id}" data-name="${esc(p.name)}" data-price="${p.selling_price}" data-stock="${p.quantity}">
                    <div class="name">${esc(p.name)}</div><div class="price">${fmt(p.selling_price)}</div><div class="stock">${p.quantity} in stock</div>
                </div>`).join('') || '<p class="text-muted">No products found.</p>';
            document.querySelectorAll('#qs-grid .pos-product-card').forEach((el) => el.addEventListener('click', () => {
                const id = el.dataset.id, name = el.dataset.name, price = parseFloat(el.dataset.price), stock = parseInt(el.dataset.stock, 10);
                if (stock <= 0) { toast('Out of stock', 'error'); return; }
                const existing = qsCart.find((c) => c.id == id);
                if (existing) { if (existing.qty + 1 > stock) { toast('Not enough stock', 'error'); return; } existing.qty += 1; }
                else qsCart.push({ id, name, price, qty: 1, stock });
                renderQsCart();
            }));
        }
        let gridDebounce;
        document.getElementById('qs-search').addEventListener('input', (e) => {
            clearTimeout(gridDebounce);
            gridDebounce = setTimeout(() => loadGrid(e.target.value), 250);
        });
        loadGrid();

        // Customer name — manual entry with live search suggestions; typing a name
        // that doesn't match anyone just creates a new customer at checkout time.
        const nameInput = document.getElementById('qs-customer-name');
        const suggestBox = document.getElementById('qs-customer-suggestions');
        let nameDebounce;
        nameInput.addEventListener('input', () => {
            qsSelectedCustomerId = null;
            clearTimeout(nameDebounce);
            const q = nameInput.value.trim();
            if (q.length < 2) { suggestBox.style.display = 'none'; return; }
            nameDebounce = setTimeout(async () => {
                const matches = await Api.get(`/customers?q=${encodeURIComponent(q)}`);
                suggestBox.innerHTML = matches.map((c) => `<div class="autocomplete-item" data-id="${c.id}" data-name="${esc(c.name)}">${esc(c.name)}${c.phone ? ' — ' + esc(c.phone) : ''}</div>`).join('')
                    + `<div class="autocomplete-item create-new" data-create="1">+ New customer "${esc(q)}"</div>`;
                suggestBox.style.display = 'block';
                suggestBox.querySelectorAll('[data-id]').forEach((item) => item.addEventListener('click', () => {
                    qsSelectedCustomerId = item.dataset.id;
                    nameInput.value = item.dataset.name;
                    suggestBox.style.display = 'none';
                }));
                suggestBox.querySelectorAll('[data-create]').forEach((item) => item.addEventListener('click', () => {
                    qsSelectedCustomerId = null; // backend will create a new customer with this typed name
                    suggestBox.style.display = 'none';
                }));
            }, 250);
        });
        document.addEventListener('click', outsideClickHandler);

        document.getElementById('qs-discount').addEventListener('input', renderQsCart);
        document.getElementById('qs-complete-btn').addEventListener('click', () => completeQuickSale(onDone, close));
        renderQsCart();
    }

    function renderQsCart() {
        const container = document.getElementById('qs-cart-items');
        if (qsCart.length === 0) { container.innerHTML = '<p class="text-muted">Cart is empty.</p>'; }
        else {
            container.innerHTML = qsCart.map((c, idx) => `
                <div class="cart-item">
                    <div><strong>${esc(c.name)}</strong><br><span class="text-muted">${fmt(c.price)} each</span></div>
                    <div class="qty-controls flex">
                        <button data-idx="${idx}" data-d="-1">−</button><span>${c.qty}</span><button data-idx="${idx}" data-d="1">+</button>
                        <button data-idx="${idx}" data-remove="1">&times;</button>
                    </div>
                </div>`).join('');
            container.querySelectorAll('[data-d]').forEach((btn) => btn.addEventListener('click', () => {
                const item = qsCart[btn.dataset.idx], d = parseInt(btn.dataset.d, 10);
                if (item.qty + d < 1 || item.qty + d > item.stock) return;
                item.qty += d; renderQsCart();
            }));
            container.querySelectorAll('[data-remove]').forEach((btn) => btn.addEventListener('click', () => { qsCart.splice(btn.dataset.idx, 1); renderQsCart(); }));
        }
        const subtotal = qsCart.reduce((s, c) => s + c.price * c.qty, 0);
        const discount = parseFloat(document.getElementById('qs-discount')?.value || 0);
        document.getElementById('qs-total').textContent = fmt(Math.max(0, subtotal - discount));
    }

    async function completeQuickSale(onDone, close) {
        if (qsCart.length === 0) { toast('Cart is empty', 'error'); return; }
        const customerName = document.getElementById('qs-customer-name').value.trim();
        const method = document.getElementById('qs-method').value;
        const discount = parseFloat(document.getElementById('qs-discount').value || 0);
        if (method === 'credit' && !customerName) { toast('A customer name is required for credit sales', 'error'); return; }

        const subtotal = qsCart.reduce((s, c) => s + c.price * c.qty, 0);
        const total = Math.max(0, subtotal - discount);

        try {
            const result = await Api.post('/sales', {
                items: qsCart.map((c) => ({ product_id: c.id, quantity: c.qty })),
                discount, payment_method: method,
                amount_paid: method === 'credit' ? 0 : total,
                customer_id: qsSelectedCustomerId,
                customer_name: customerName || null,
            });
            toast(`Sale completed: ${result.receipt_no}`);
            close();
            onDone();
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function loadAiInsights() {
        const body = document.getElementById('ai-body');
        if (!body) return;
        body.innerHTML = '<div class="spinner" style="border-color: rgba(255,255,255,0.3); border-top-color:#fff; margin-top:16px;"></div>';
        try {
            const data = await Api.get('/ai/insights');
            body.innerHTML = `<pre>${esc(data.summary)}</pre>`;
        } catch (e) {
            body.innerHTML = `<p style="opacity:0.85;">Couldn't generate insights: ${esc(e.message)}</p>`;
        }
    }

    /* ---------------------------------------------------------------
       PRODUCTS & INVENTORY
       --------------------------------------------------------------- */
    async function renderProducts(content, q = '') {
        const [products, categories] = await Promise.all([
            Api.get(`/products?q=${encodeURIComponent(q)}`),
            Api.get('/products/categories'),
        ]);
        const canEdit = ['owner', 'manager'].includes((currentUser() || {}).role);

        const rows = products.map((p) => {
            const stockBadge = p.quantity <= 0 ? '<span class="badge badge-danger">Out of stock</span>'
                : (p.quantity <= p.min_stock_level ? '<span class="badge badge-warn">Low stock</span>' : '<span class="badge badge-success">OK</span>');
            return `<tr>
                <td>${esc(p.name)}<br><span class="text-muted" style="font-size:12px;">${esc(p.sku)}</span></td>
                <td>${esc(p.category_name || '—')}</td>
                <td>${p.quantity} ${stockBadge}</td>
                <td>${fmt(p.buying_price)}</td>
                <td>${fmt(p.selling_price)}</td>
                <td>${p.is_on_store == 1 ? '<span class="badge badge-success">On Store</span>' : '<span class="badge badge-muted">Hidden</span>'}</td>
                <td>${canEdit ? `
                    <button class="btn btn-sm btn-secondary" data-action="edit" data-id="${p.id}">Edit</button>
                    <button class="btn btn-sm btn-secondary" data-action="stock" data-id="${p.id}">Stock</button>
                    <button class="btn btn-sm btn-secondary" data-action="image" data-id="${p.id}">Image</button>
                    <button class="btn btn-sm ${p.is_on_store == 1 ? 'btn-danger' : 'btn-accent'}" data-action="store" data-id="${p.id}" data-onstore="${p.is_on_store}">${p.is_on_store == 1 ? 'Unlist' : 'List Online'}</button>
                ` : ''}</td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" class="text-muted">No products found.</td></tr>';

        content.innerHTML = `
        <div class="flex-between" style="margin-bottom:14px;">
            <input class="form-control" style="max-width:320px;" id="product-search" placeholder="Search products or SKU…" value="${esc(q)}">
            ${canEdit ? '<button class="btn" id="add-product-btn">+ Add Product</button>' : ''}
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Buying Price</th><th>Selling Price</th><th>Store</th><th>Actions</th></tr></thead>
            <tbody id="products-tbody">${rows}</tbody>
        </table></div>
        <div id="product-modal-root"></div>`;

        // Real-time search — no page reload, debounced fetch as you type
        let debounce;
        document.getElementById('product-search').addEventListener('input', (e) => {
            clearTimeout(debounce);
            debounce = setTimeout(() => renderProducts(content, e.target.value), 300);
        });

        if (canEdit) {
            document.getElementById('add-product-btn').addEventListener('click', () => openProductForm(null, categories, () => renderProducts(content, q)));
            content.querySelectorAll('[data-action="edit"]').forEach((btn) => btn.addEventListener('click', async () => {
                const p = products.find((x) => x.id == btn.dataset.id);
                openProductForm(p, categories, () => renderProducts(content, q));
            }));
            content.querySelectorAll('[data-action="stock"]').forEach((btn) => btn.addEventListener('click', () => openStockModal(btn.dataset.id, () => renderProducts(content, q))));
            content.querySelectorAll('[data-action="image"]').forEach((btn) => btn.addEventListener('click', () => openImageModal(btn.dataset.id, () => renderProducts(content, q))));
            content.querySelectorAll('[data-action="store"]').forEach((btn) => btn.addEventListener('click', async () => {
                const goingOn = btn.dataset.onstore != '1';
                try {
                    await Api.post(`/products/${btn.dataset.id}/store`, { on_store: goingOn });
                    toast(goingOn ? 'Product listed on store' : 'Product removed from store');
                    renderProducts(content, q);
                } catch (e) { toast(e.message, 'error'); }
            }));
        }
    }

    function modalRoot() { return document.getElementById('product-modal-root') || document.body; }

    function openProductForm(product, categories, onSaved) {
        const isEdit = !!product;
        const catOptions = categories.map((c) => `<option value="${c.id}" ${product && product.category_id == c.id ? 'selected' : ''}>${esc(c.name)}</option>`).join('');
        const html = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>${isEdit ? 'Edit Product' : 'Add Product'}</h3>
                <form id="product-form">
                    <div class="form-group"><label>Product Name</label><input class="form-control" name="name" required value="${isEdit ? esc(product.name) : ''}"></div>
                    <div class="form-row">
                        <div class="form-group"><label>SKU</label><input class="form-control" name="sku" required value="${isEdit ? esc(product.sku) : ''}"></div>
                        <div class="form-group"><label>Category</label><select class="form-control" name="category_id"><option value="">—</option>${catOptions}</select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Buying Price</label><input class="form-control" name="buying_price" type="number" step="0.01" required value="${isEdit ? product.buying_price : 0}"></div>
                        <div class="form-group"><label>Selling Price</label><input class="form-control" name="selling_price" type="number" step="0.01" required value="${isEdit ? product.selling_price : 0}"></div>
                    </div>
                    <div class="form-row">
                        ${!isEdit ? '<div class="form-group"><label>Opening Quantity</label><input class="form-control" name="quantity" type="number" value="0"></div>' : ''}
                        <div class="form-group"><label>Minimum Stock Level</label><input class="form-control" name="min_stock_level" type="number" value="${isEdit ? product.min_stock_level : 5}"></div>
                    </div>
                    <div class="form-group"><label>Description (optional)</label><textarea class="form-control" name="description" rows="2">${isEdit ? esc(product.description || '') : ''}</textarea></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">${isEdit ? 'Save Changes' : 'Add Product'}</button>
                </form>
            </div>
        </div>`;
        modalRoot().innerHTML = html;
        document.getElementById('modal-close').addEventListener('click', () => modalRoot().innerHTML = '');
        document.getElementById('modal-backdrop').addEventListener('click', (e) => { if (e.target.id === 'modal-backdrop') modalRoot().innerHTML = ''; });
        document.getElementById('product-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            try {
                if (isEdit) { await Api.put(`/products/${product.id}`, fd); toast('Product updated'); }
                else { await Api.post('/products', fd); toast('Product added'); }
                modalRoot().innerHTML = '';
                onSaved();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    function openStockModal(productId, onSaved) {
        modalRoot().innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>Adjust Stock</h3>
                <form id="stock-form">
                    <div class="form-group"><label>Change Quantity (use negative to remove)</label><input class="form-control" name="change_qty" type="number" required></div>
                    <div class="form-group"><label>Reason</label>
                        <select class="form-control" name="reason"><option value="restock">Restock</option><option value="adjustment">Adjustment / Correction</option></select>
                    </div>
                    <div class="form-group"><label>Note (optional)</label><input class="form-control" name="note"></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Update Stock</button>
                </form>
            </div>
        </div>`;
        document.getElementById('modal-close').addEventListener('click', () => modalRoot().innerHTML = '');
        document.getElementById('stock-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            fd.change_qty = parseInt(fd.change_qty, 10);
            try {
                await Api.post(`/products/${productId}/stock`, fd);
                toast('Stock updated');
                modalRoot().innerHTML = '';
                onSaved();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    function openImageModal(productId, onSaved) {
        modalRoot().innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>Upload Product Image</h3>
                <p class="text-muted">Required before this product can be listed on the online store.</p>
                <form id="image-form">
                    <div class="form-group"><input class="form-control" type="file" name="image" accept="image/png,image/jpeg,image/webp" required></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Upload</button>
                </form>
            </div>
        </div>`;
        document.getElementById('modal-close').addEventListener('click', () => modalRoot().innerHTML = '');
        document.getElementById('image-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            try {
                await Api.upload(`/products/${productId}/image`, fd);
                toast('Image uploaded');
                modalRoot().innerHTML = '';
                onSaved();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------------------------------------------------------
       POS / SALES
       --------------------------------------------------------------- */
    let cart = [];

    async function renderPOS(content) {
        cart = [];
        content.innerHTML = `
        <div class="pos-layout">
            <div>
                <input class="form-control" id="pos-search" placeholder="Search products by name or SKU… (live search)" style="margin-bottom:14px;">
                <div class="pos-product-grid" id="pos-grid"></div>
            </div>
            <div class="card">
                <h3 class="mt-0">Cart</h3>
                <div id="cart-items"><p class="text-muted">Cart is empty.</p></div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Customer (optional, required for credit sales)</label>
                    <select class="form-control" id="pos-customer"><option value="">Walk-in customer</option></select>
                </div>
                <div class="form-group"><label>Discount (${currency})</label><input class="form-control" id="pos-discount" type="number" value="0" min="0"></div>
                <div class="form-group"><label>Payment Method</label>
                    <select class="form-control" id="pos-method">
                        <option value="cash">Cash</option><option value="transfer">Transfer</option>
                        <option value="pos">POS/Card</option><option value="split">Split</option><option value="credit">Credit (pay later)</option>
                    </select>
                </div>
                <div class="form-group" id="pos-amount-group"><label>Amount Paid (${currency})</label><input class="form-control" id="pos-amount-paid" type="number" value="0"></div>
                <div class="cart-totals">
                    <div class="flex-between"><span>Subtotal</span><span id="cart-subtotal">${fmt(0)}</span></div>
                    <div class="flex-between grand-total"><span>Total</span><span id="cart-total">${fmt(0)}</span></div>
                </div>
                <button class="btn" style="width:100%; justify-content:center; margin-top:12px;" id="checkout-btn">Complete Sale</button>
            </div>
        </div>
        <div id="pos-modal-root"></div>`;

        const customers = await Api.get('/customers');
        const custSelect = document.getElementById('pos-customer');
        customers.forEach((c) => {
            const opt = document.createElement('option');
            opt.value = c.id; opt.textContent = `${c.name}${c.outstanding_debt > 0 ? ' (owes ' + fmt(c.outstanding_debt) + ')' : ''}`;
            custSelect.appendChild(opt);
        });

        async function loadGrid(q = '') {
            const products = await Api.get(`/products?q=${encodeURIComponent(q)}`);
            document.getElementById('pos-grid').innerHTML = products.map((p) => `
                <div class="pos-product-card" data-id="${p.id}" data-name="${esc(p.name)}" data-price="${p.selling_price}" data-stock="${p.quantity}">
                    <div class="name">${esc(p.name)}</div>
                    <div class="price">${fmt(p.selling_price)}</div>
                    <div class="stock">${p.quantity} in stock</div>
                </div>`).join('') || '<p class="text-muted">No products found.</p>';
            document.querySelectorAll('.pos-product-card').forEach((el) => el.addEventListener('click', () => addToCart(el)));
        }

        let debounce;
        document.getElementById('pos-search').addEventListener('input', (e) => {
            clearTimeout(debounce);
            debounce = setTimeout(() => loadGrid(e.target.value), 250);
        });
        loadGrid();

        document.getElementById('pos-method').addEventListener('change', (e) => {
            document.getElementById('pos-amount-group').style.display = e.target.value === 'credit' ? 'none' : 'block';
        });

        document.getElementById('checkout-btn').addEventListener('click', completeSale);
        document.getElementById('pos-discount').addEventListener('input', renderCart);
        document.getElementById('pos-amount-paid').addEventListener('input', renderCart);
    }

    function addToCart(el) {
        const id = el.dataset.id, name = el.dataset.name, price = parseFloat(el.dataset.price), stock = parseInt(el.dataset.stock, 10);
        if (stock <= 0) { toast('This product is out of stock', 'error'); return; }
        const existing = cart.find((c) => c.id == id);
        if (existing) {
            if (existing.qty + 1 > stock) { toast('Not enough stock', 'error'); return; }
            existing.qty += 1;
        } else {
            cart.push({ id, name, price, qty: 1, stock });
        }
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cart-items');
        if (cart.length === 0) { container.innerHTML = '<p class="text-muted">Cart is empty.</p>'; }
        else {
            container.innerHTML = cart.map((c, idx) => `
                <div class="cart-item">
                    <div><strong>${esc(c.name)}</strong><br><span class="text-muted">${fmt(c.price)} each</span></div>
                    <div class="qty-controls flex">
                        <button data-idx="${idx}" data-d="-1">−</button>
                        <span>${c.qty}</span>
                        <button data-idx="${idx}" data-d="1">+</button>
                        <button data-idx="${idx}" data-remove="1">&times;</button>
                    </div>
                </div>`).join('');
            container.querySelectorAll('[data-d]').forEach((btn) => btn.addEventListener('click', () => {
                const idx = btn.dataset.idx, d = parseInt(btn.dataset.d, 10);
                const item = cart[idx];
                if (item.qty + d < 1) return;
                if (item.qty + d > item.stock) { toast('Not enough stock', 'error'); return; }
                item.qty += d;
                renderCart();
            }));
            container.querySelectorAll('[data-remove]').forEach((btn) => btn.addEventListener('click', () => {
                cart.splice(btn.dataset.idx, 1);
                renderCart();
            }));
        }
        const subtotal = cart.reduce((sum, c) => sum + c.price * c.qty, 0);
        const discount = parseFloat(document.getElementById('pos-discount')?.value || 0);
        const total = Math.max(0, subtotal - discount);
        document.getElementById('cart-subtotal').textContent = fmt(subtotal);
        document.getElementById('cart-total').textContent = fmt(total);
    }

    async function completeSale() {
        if (cart.length === 0) { toast('Cart is empty', 'error'); return; }
        const method = document.getElementById('pos-method').value;
        const discount = parseFloat(document.getElementById('pos-discount').value || 0);
        const customerId = document.getElementById('pos-customer').value || null;
        const subtotal = cart.reduce((s, c) => s + c.price * c.qty, 0);
        const total = Math.max(0, subtotal - discount);
        let amountPaid = method === 'credit' ? 0 : parseFloat(document.getElementById('pos-amount-paid').value || total);
        if (method === 'credit' && !customerId) { toast('Select a customer for credit sales', 'error'); return; }

        try {
            const result = await Api.post('/sales', {
                items: cart.map((c) => ({ product_id: c.id, quantity: c.qty })),
                discount, payment_method: method, amount_paid: amountPaid, customer_id: customerId,
            });
            toast(`Sale completed: ${result.receipt_no}`);
            showReceiptModal(result.id);
            cart = [];
            renderPOS(document.getElementById('content'));
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    async function showReceiptModal(saleId) {
        const sale = await Api.get(`/sales/${saleId}/receipt`);
        const rows = sale.items.map((i) => `<tr><td>${esc(i.product_name)}</td><td>${i.quantity}</td><td>${fmt(i.unit_price)}</td><td>${fmt(i.line_total)}</td></tr>`).join('');
        const shareUrl = `${window.location.origin}/${slug}/receipt/${saleId}`;
        const html = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal" id="receipt-print">
                <button class="modal-close no-print" id="modal-close">&times;</button>
                <h3>Receipt ${esc(sale.receipt_no)}</h3>
                <p class="text-muted">${dt(sale.created_at)}</p>
                <table style="width:100%;"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>${rows}</tbody></table>
                <div class="cart-totals">
                    <div class="flex-between"><span>Subtotal</span><span>${fmt(sale.subtotal)}</span></div>
                    <div class="flex-between"><span>Discount</span><span>${fmt(sale.discount)}</span></div>
                    <div class="flex-between grand-total"><span>Total</span><span>${fmt(sale.total)}</span></div>
                    <div class="flex-between"><span>Paid</span><span>${fmt(sale.amount_paid)}</span></div>
                    <div class="flex-between"><span>Balance Due</span><span>${fmt(sale.balance_due)}</span></div>
                </div>
                <div class="form-group no-print" style="margin-top:14px;"><label>Shareable link</label><input class="form-control" readonly value="${shareUrl}" onclick="this.select()"></div>
                <button class="btn no-print" style="width:100%; justify-content:center;" onclick="window.print()">Print Receipt</button>
            </div>
        </div>`;
        (document.getElementById('pos-modal-root') || document.body).innerHTML = html;
        document.getElementById('modal-close').addEventListener('click', () => document.getElementById('pos-modal-root').innerHTML = '');
    }

    /* ---------------------------------------------------------------
       CUSTOMERS
       --------------------------------------------------------------- */
    async function renderCustomers(content, q = '') {
        const customers = await Api.get(`/customers?q=${encodeURIComponent(q)}`);
        const rows = customers.map((c) => `
            <tr>
                <td>${esc(c.name)}</td><td>${esc(c.phone || '—')}</td>
                <td>${c.outstanding_debt > 0 ? `<span class="badge badge-warn">${fmt(c.outstanding_debt)}</span>` : fmt(0)}</td>
                <td>${fmt(c.credit_limit)}</td>
                <td>
                    <button class="btn btn-sm btn-secondary" data-view="${c.id}">View</button>
                    ${c.outstanding_debt > 0 ? `<button class="btn btn-sm btn-accent" data-pay="${c.id}">Record Payment</button>` : ''}
                </td>
            </tr>`).join('') || '<tr><td colspan="5" class="text-muted">No customers found.</td></tr>';

        content.innerHTML = `
        <div class="flex-between" style="margin-bottom:14px;">
            <input class="form-control" style="max-width:320px;" id="cust-search" placeholder="Search customers…" value="${esc(q)}">
            <button class="btn" id="add-cust-btn">+ Add Customer</button>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Phone</th><th>Outstanding Debt</th><th>Credit Limit</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table></div>
        <div id="cust-modal-root"></div>`;

        let debounce;
        document.getElementById('cust-search').addEventListener('input', (e) => {
            clearTimeout(debounce);
            debounce = setTimeout(() => renderCustomers(content, e.target.value), 300);
        });
        document.getElementById('add-cust-btn').addEventListener('click', () => openCustomerForm(() => renderCustomers(content, q)));
        content.querySelectorAll('[data-view]').forEach((btn) => btn.addEventListener('click', () => openCustomerProfile(btn.dataset.view)));
        content.querySelectorAll('[data-pay]').forEach((btn) => btn.addEventListener('click', () => openPaymentModal(btn.dataset.pay, () => renderCustomers(content, q))));
    }

    function openCustomerForm(onSaved) {
        (document.getElementById('cust-modal-root') || document.body).innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>Add Customer</h3>
                <form id="cust-form">
                    <div class="form-group"><label>Name</label><input class="form-control" name="name" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div>
                        <div class="form-group"><label>Email</label><input class="form-control" name="email" type="email"></div>
                    </div>
                    <div class="form-group"><label>Credit Limit (${currency})</label><input class="form-control" name="credit_limit" type="number" value="0"></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Add Customer</button>
                </form>
            </div>
        </div>`;
        document.getElementById('modal-close').addEventListener('click', () => document.getElementById('cust-modal-root').innerHTML = '');
        document.getElementById('cust-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            try { await Api.post('/customers', fd); toast('Customer added'); document.getElementById('cust-modal-root').innerHTML = ''; onSaved(); }
            catch (err) { toast(err.message, 'error'); }
        });
    }

    async function openCustomerProfile(id) {
        const c = await Api.get(`/customers/${id}`);
        const history = (c.purchase_history || []).map((s) => `<tr><td>${esc(s.receipt_no)}</td><td>${dt(s.created_at)}</td><td>${fmt(s.total)}</td><td>${esc(s.status)}</td></tr>`).join('') || '<tr><td colspan="4" class="text-muted">No purchases yet.</td></tr>';
        const payments = (c.payment_history || []).map((p) => `<tr><td>${dt(p.created_at)}</td><td>${fmt(p.amount)}</td><td>${esc(p.method)}</td></tr>`).join('') || '<tr><td colspan="3" class="text-muted">No payments yet.</td></tr>';
        (document.getElementById('cust-modal-root') || document.body).innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal" style="max-width:640px;">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>${esc(c.name)}</h3>
                <p class="text-muted">${esc(c.phone || '')} ${esc(c.email || '')}</p>
                <p>Outstanding debt: <strong>${fmt(c.outstanding_debt)}</strong> &middot; Credit limit: ${fmt(c.credit_limit)}</p>
                <h4>Purchase History</h4>
                <div class="table-wrap"><table><thead><tr><th>Receipt</th><th>Date</th><th>Total</th><th>Status</th></tr></thead><tbody>${history}</tbody></table></div>
                <h4>Payment History</h4>
                <div class="table-wrap"><table><thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead><tbody>${payments}</tbody></table></div>
            </div>
        </div>`;
        document.getElementById('modal-close').addEventListener('click', () => document.getElementById('cust-modal-root').innerHTML = '');
    }

    function openPaymentModal(id, onSaved) {
        (document.getElementById('cust-modal-root') || document.body).innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>Record Debt Payment</h3>
                <form id="pay-form">
                    <div class="form-group"><label>Amount (${currency})</label><input class="form-control" name="amount" type="number" step="0.01" required></div>
                    <div class="form-group"><label>Method</label><select class="form-control" name="method"><option value="cash">Cash</option><option value="transfer">Transfer</option><option value="pos">POS/Card</option></select></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Record Payment</button>
                </form>
            </div>
        </div>`;
        document.getElementById('modal-close').addEventListener('click', () => document.getElementById('cust-modal-root').innerHTML = '');
        document.getElementById('pay-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            try { await Api.post(`/customers/${id}/payment`, fd); toast('Payment recorded'); document.getElementById('cust-modal-root').innerHTML = ''; onSaved(); }
            catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------------------------------------------------------
       EXPENSES
       --------------------------------------------------------------- */
    async function renderExpenses(content) {
        const [expenses, categories] = await Promise.all([Api.get('/expenses'), Api.get('/expenses/categories')]);
        const rows = expenses.map((e) => `<tr><td>${e.expense_date}</td><td>${esc(e.title)}</td><td>${esc(e.category_name || '—')}</td><td>${fmt(e.amount)}</td></tr>`).join('') || '<tr><td colspan="4" class="text-muted">No expenses recorded.</td></tr>';
        const total = expenses.reduce((s, e) => s + parseFloat(e.amount), 0);

        content.innerHTML = `
        <div class="flex-between" style="margin-bottom:14px;">
            <div class="card stat-card" style="padding:12px 18px;"><div class="stat-label">Total Expenses</div><div class="stat-value">${fmt(total)}</div></div>
            <button class="btn" id="add-exp-btn">+ Add Expense</button>
        </div>
        <div class="table-wrap"><table><thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Amount</th></tr></thead><tbody>${rows}</tbody></table></div>
        <div id="exp-modal-root"></div>`;

        document.getElementById('add-exp-btn').addEventListener('click', () => {
            const catOptions = categories.map((c) => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
            document.getElementById('exp-modal-root').innerHTML = `
            <div class="modal-backdrop" id="modal-backdrop">
                <div class="modal">
                    <button class="modal-close" id="modal-close">&times;</button>
                    <h3>Add Expense</h3>
                    <form id="exp-form">
                        <div class="form-group"><label>Title</label><input class="form-control" name="title" required></div>
                        <div class="form-row">
                            <div class="form-group"><label>Amount (${currency})</label><input class="form-control" name="amount" type="number" step="0.01" required></div>
                            <div class="form-group"><label>Category</label><select class="form-control" name="category_id"><option value="">—</option>${catOptions}</select></div>
                        </div>
                        <div class="form-group"><label>Date</label><input class="form-control" name="expense_date" type="date" value="${new Date().toISOString().slice(0,10)}"></div>
                        <button class="btn" type="submit" style="width:100%; justify-content:center;">Save Expense</button>
                    </form>
                </div>
            </div>`;
            document.getElementById('modal-close').addEventListener('click', () => document.getElementById('exp-modal-root').innerHTML = '');
            document.getElementById('exp-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = Object.fromEntries(new FormData(e.target).entries());
                try { await Api.post('/expenses', fd); toast('Expense recorded'); document.getElementById('exp-modal-root').innerHTML = ''; renderExpenses(content); }
                catch (err) { toast(err.message, 'error'); }
            });
        });
    }

    /* ---------------------------------------------------------------
       ONLINE ORDERS
       --------------------------------------------------------------- */
    async function renderOrders(content) {
        const orders = await Api.get('/orders');
        const STAGES = ['ordered', 'accepted', 'on_delivery', 'delivered'];
        const NEXT_LABEL = { ordered: 'Accept Order', accepted: 'Mark On Delivery', on_delivery: 'Mark Delivered' };
        const statusBadge = (status) => {
            const map = { ordered: 'badge-warn', accepted: 'badge-muted', on_delivery: 'badge-muted', delivered: 'badge-success', cancelled: 'badge-danger' };
            const label = { ordered: 'Ordered', accepted: 'Accepted', on_delivery: 'On Delivery', delivered: 'Delivered', cancelled: 'Cancelled' };
            return `<span class="badge ${map[status] || 'badge-muted'}">${esc(label[status] || status)}</span>`;
        };
        const paidBadge = (o) => {
            const paid = Number(o.amount_paid || 0), total = Number(o.total || 0);
            if (paid >= total && total > 0) return `<span class="badge badge-success">${fmt(paid)} paid</span>`;
            if (paid > 0) return `<span class="badge badge-warn">${fmt(paid)} / ${fmt(total)}</span>`;
            return `<span class="badge badge-danger">Unpaid</span>`;
        };
        const actionsFor = (o) => {
            if (o.status === 'ordered') {
                // Accepting a fresh order converts it into a sale (deducts stock) via the dedicated /accept endpoint.
                return `<button class="btn btn-sm btn-accent" data-accept="${o.id}">Accept Order</button> <button class="btn btn-sm btn-danger" data-cancel="${o.id}">Cancel</button>`;
            }
            const idx = STAGES.indexOf(o.status);
            if (idx >= 0 && idx < STAGES.length - 1) {
                const next = STAGES[idx + 1];
                return `<button class="btn btn-sm btn-accent" data-advance="${o.id}" data-next="${next}">${NEXT_LABEL[o.status]}</button>`;
            }
            return '—';
        };
        const rows = orders.map((o) => `
            <tr>
                <td>${esc(o.order_no)}</td><td>${esc(o.customer_name)}</td><td>${fmt(o.total)}</td>
                <td>${paidBadge(o)}</td>
                <td>${statusBadge(o.status)}</td>
                <td>${dt(o.created_at)}</td>
                <td>${actionsFor(o)}</td>
            </tr>`).join('') || '<tr><td colspan="7" class="text-muted">No online orders yet.</td></tr>';

        content.innerHTML = `
        <p class="text-muted">Orders placed from your public online store at <a href="${window.APP_BASE || ''}/${slug}" target="_blank">/${slug}</a> appear here. Every order moves through <strong>Ordered → Accepted → On Delivery → Delivered</strong>, and the customer is emailed at each step.</p>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table></div>`;

        content.querySelectorAll('[data-accept]').forEach((btn) => btn.addEventListener('click', async () => {
            try { const r = await Api.post(`/orders/${btn.dataset.accept}/accept`); toast(`Order accepted → sale ${r.receipt_no}`); renderOrders(content); }
            catch (e) { toast(e.message, 'error'); }
        }));
        content.querySelectorAll('[data-advance]').forEach((btn) => btn.addEventListener('click', async () => {
            try { await Api.put(`/orders/${btn.dataset.advance}/status`, { status: btn.dataset.next }); toast(`Order marked as ${btn.dataset.next.replace('_', ' ')}`); renderOrders(content); }
            catch (e) { toast(e.message, 'error'); }
        }));
        content.querySelectorAll('[data-cancel]').forEach((btn) => btn.addEventListener('click', async () => {
            try { await Api.put(`/orders/${btn.dataset.cancel}/status`, { status: 'cancelled' }); toast('Order cancelled'); renderOrders(content); }
            catch (e) { toast(e.message, 'error'); }
        }));
    }

    /* ---------------------------------------------------------------
       STORE PAGE — theme picker, editable content, categories, and
       which products are listed on the public online store.
       --------------------------------------------------------------- */
    const THEME_CATALOG_FALLBACK = [
        { id: 'aurora', name: 'Aurora', description: 'Minimal grid with a category & filter sidebar.', accent: '#4f46e5' },
        { id: 'wink', name: 'Wink', description: 'Clean product-list with brand/category filters and a promo strip.', accent: '#111827' },
        { id: 'luxora', name: 'Luxora', description: 'Elegant editorial fashion storefront with hero + testimonials.', accent: '#8a6d3b' },
        { id: 'marketly', name: 'Marketly', description: 'Bold marketplace layout with flash deals and category tiles.', accent: '#2563eb' },
        { id: 'novatrend', name: 'NovaTrend', description: 'Trendy lifestyle storefront with a model hero.', accent: '#ea580c' },
    ];
    const THEME_FIELDS = {
        aurora:    [{ key: 'announcement', label: 'Announcement Bar Text', aiKind: 'announcement' }, { key: 'hero_heading', label: 'Hero Heading', aiKind: 'hero_heading' }, { key: 'hero_subheading', label: 'Hero Subheading', aiKind: 'hero_subheading' }],
        wink:      [{ key: 'collection_title', label: 'Collection Title' }, { key: 'hero_heading', label: 'Hero Heading', aiKind: 'hero_heading' }, { key: 'hero_subheading', label: 'Hero Subheading', aiKind: 'hero_subheading' }],
        luxora:    [{ key: 'eyebrow', label: 'Eyebrow Label' }, { key: 'hero_heading', label: 'Hero Heading', aiKind: 'hero_heading' }, { key: 'hero_subheading', label: 'Hero Subheading', aiKind: 'hero_subheading' }, { key: 'promo_badge', label: 'Promo Badge Text' }],
        marketly:  [{ key: 'announcement', label: 'Announcement Bar Text', aiKind: 'announcement' }, { key: 'hero_heading', label: 'Hero Heading', aiKind: 'hero_heading' }, { key: 'hero_subheading', label: 'Hero Subheading', aiKind: 'hero_subheading' }],
        novatrend: [{ key: 'eyebrow', label: 'Eyebrow Label' }, { key: 'hero_heading', label: 'Hero Heading', aiKind: 'hero_heading' }, { key: 'hero_subheading', label: 'Hero Subheading', aiKind: 'hero_subheading' }],
    };

    let storeState = null; // { theme, store_type, content, themes, store_types }

    async function renderStorePage(content) {
        storeState = await Api.get('/store-settings');
        content.innerHTML = `
        <div class="flex-between" style="margin-bottom:16px;">
            <div class="flex" style="gap:10px;">
                <button class="btn btn-secondary tab-btn active" data-tab="theme">Theme</button>
                <button class="btn btn-secondary tab-btn" data-tab="branding">Branding</button>
                <button class="btn btn-secondary tab-btn" data-tab="content">Text Content</button>
                <button class="btn btn-secondary tab-btn" data-tab="categories">Categories</button>
                <button class="btn btn-secondary tab-btn" data-tab="products">Products on Store</button>
                <button class="btn btn-secondary tab-btn" data-tab="checkout">Checkout &amp; Notifications</button>
            </div>
            <div class="flex" style="gap:8px;">
                <a class="btn btn-secondary" href="${window.APP_BASE || ''}/${slug}" target="_blank">Preview Store &#8599;</a>
                <button class="btn" id="store-save-btn">Save Changes</button>
            </div>
        </div>
        <div id="store-tab-root"></div>`;

        document.getElementById('store-save-btn').addEventListener('click', saveStoreSettings);
        content.querySelectorAll('.tab-btn').forEach((btn) => btn.addEventListener('click', () => {
            content.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            drawStoreTab(btn.dataset.tab);
        }));

        drawStoreTab('theme');
    }

    async function saveStoreSettings() {
        try {
            storeState = await Api.put('/store-settings', {
                theme: storeState.theme, store_type: storeState.store_type, content: storeState.content,
            });
            toast('Store settings saved');
        } catch (e) { toast(e.message, 'error'); }
    }

    function drawStoreTab(tab) {
        const root = document.getElementById('store-tab-root');
        if (tab === 'theme') return drawThemeTab(root);
        if (tab === 'branding') return drawBrandingTab(root);
        if (tab === 'content') return drawContentTab(root);
        if (tab === 'categories') return drawCategoriesTab(root);
        if (tab === 'products') return drawStoreProductsTab(root);
        if (tab === 'checkout') return drawCheckoutSettingsTab(root);
    }

    function drawThemeTab(root) {
        const themes = storeState.themes || THEME_CATALOG_FALLBACK;
        root.innerHTML = `
        <div class="section-title" style="margin-top:0;">1. Store Category</div>
        <p class="text-muted" style="margin-top:-6px;">Choose what you sell — this drives the header photos and decorative imagery below.</p>
        <select class="form-control" id="store-type-select" style="max-width:260px;">
            ${(storeState.store_types || ['fashion', 'tech', 'beauty', 'grocery', 'general']).map((t) => `<option value="${t}" ${storeState.store_type === t ? 'selected' : ''}>${t[0].toUpperCase() + t.slice(1)}</option>`).join('')}
        </select>

        <div class="section-title">2. Theme</div>
        <p class="text-muted" style="margin-top:-6px;">Choose the template your public storefront uses. You can switch anytime — nothing is lost.</p>
        <div class="grid grid-3" id="theme-cards"></div>

        <div class="section-title">3. Header Image</div>
        <p class="text-muted" style="margin-top:-6px;">Pick a header photo curated for your category, or <a href="#" id="go-custom-banner">upload your own</a> from the Branding tab.</p>
        <div class="grid grid-4" id="header-image-gallery"><div class="empty-state" style="grid-column:1/-1;"><div class="spinner"></div></div></div>`;

        document.getElementById('theme-cards').innerHTML = themes.map((t) => `
            <div class="card theme-card ${storeState.theme === t.id ? 'selected' : ''}" data-theme="${t.id}" style="cursor:pointer; border-color:${storeState.theme === t.id ? t.accent : ''};">
                <div style="height:8px; background:${t.accent}; border-radius:4px; margin-bottom:10px;"></div>
                <strong>${t.name}</strong>
                <p class="text-muted" style="font-size:12.5px; margin:6px 0 0;">${t.description}</p>
                ${storeState.theme === t.id ? '<span class="badge badge-success" style="margin-top:8px;">Selected</span>' : ''}
            </div>`).join('');

        document.querySelectorAll('.theme-card').forEach((card) => card.addEventListener('click', () => {
            storeState.theme = card.dataset.theme;
            drawThemeTab(root);
        }));
        document.getElementById('store-type-select').addEventListener('change', (e) => {
            storeState.store_type = e.target.value;
            loadHeaderImageGallery();
        });
        document.getElementById('go-custom-banner').addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelector('[data-tab="branding"]').click();
        });

        loadHeaderImageGallery();
    }

    async function loadHeaderImageGallery() {
        const gallery = document.getElementById('header-image-gallery');
        if (!gallery) return;
        gallery.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><div class="spinner"></div></div>';
        try {
            const images = await Api.get(`/store-settings/header-images?store_type=${encodeURIComponent(storeState.store_type)}`);
            if (!images.length) {
                gallery.innerHTML = `<p class="text-muted" style="grid-column:1/-1;">No curated header images for this category yet — <a href="#" id="go-custom-banner-2">upload your own</a> instead.</p>`;
                const link = document.getElementById('go-custom-banner-2');
                if (link) link.addEventListener('click', (e) => { e.preventDefault(); document.querySelector('[data-tab="branding"]').click(); });
                return;
            }
            gallery.innerHTML = images.map((img) => `
                <div class="card header-img-card ${storeState.content.banner_path === img.image_path ? 'selected' : ''}" data-id="${img.id}" style="cursor:pointer; padding:8px;">
                    <div class="listing-thumb" style="width:100%; height:100px;"><img src="${assetUrl(img.image_path)}" style="width:100%;height:100%;object-fit:cover;"></div>
                    ${storeState.content.banner_path === img.image_path ? '<span class="badge badge-success" style="margin-top:6px;">Selected</span>' : `<span class="text-muted" style="font-size:11px;">${esc(img.label || 'Header photo')}</span>`}
                </div>`).join('');
            gallery.querySelectorAll('.header-img-card').forEach((card) => card.addEventListener('click', async () => {
                try {
                    const updated = await Api.post('/store-settings/header-image', { header_image_id: card.dataset.id });
                    // Only merge content back — theme/store_type may have unsaved local edits pending "Save Changes".
                    storeState.content = { ...storeState.content, banner_path: updated.content.banner_path };
                    toast('Header image applied');
                    loadHeaderImageGallery();
                } catch (e) { toast(e.message, 'error'); }
            }));
        } catch (e) {
            gallery.innerHTML = `<p class="text-muted" style="grid-column:1/-1;">${esc(e.message)}</p>`;
        }
    }

    function drawBrandingTab(root) {
        const c = storeState.content;
        root.innerHTML = `
        <p class="text-muted">These appear across every template's header/footer, regardless of which theme is active.</p>

        <div class="section-title">Logo</div>
        <div class="flex" style="gap:16px; align-items:center; margin-bottom:20px;">
            <div class="branding-preview">${c.logo_path ? `<img src="${assetUrl(c.logo_path)}">` : '<span class="text-muted">No logo yet</span>'}</div>
            <form id="logo-upload-form" class="flex" style="gap:8px;">
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
                <button class="btn btn-sm" type="submit">Upload Logo</button>
            </form>
        </div>

        <div class="section-title">Header / Hero Banner Image</div>
        <p class="text-muted" style="margin-top:-6px;">Optional — overrides the default stock photo used in your theme's hero section.</p>
        <div class="flex" style="gap:16px; align-items:center; margin-bottom:20px;">
            <div class="branding-preview wide">${c.banner_path ? `<img src="${assetUrl(c.banner_path)}">` : '<span class="text-muted">Using default stock photo</span>'}</div>
            <form id="banner-upload-form" class="flex" style="gap:8px;">
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" required>
                <button class="btn btn-sm" type="submit">Upload Banner</button>
            </form>
        </div>

        <div class="section-title">WhatsApp Contact</div>
        <p class="text-muted" style="margin-top:-6px;">Shown as a floating chat button on your storefront (include country code, e.g. 2348012345678).</p>
        <div class="form-group" style="max-width:320px;"><input class="form-control" id="whatsapp-number" value="${esc(c.whatsapp_number || '')}" placeholder="e.g. 2348012345678"></div>

        <div class="section-title">Social Media Links</div>
        <p class="text-muted" style="margin-top:-6px;">Shown in your storefront footer. Leave blank to hide.</p>
        <div class="grid grid-2" style="max-width:640px;">
            <div class="form-group"><label>Facebook</label><input class="form-control" id="social-facebook" value="${esc(c.social_facebook || '')}" placeholder="https://facebook.com/..."></div>
            <div class="form-group"><label>Instagram</label><input class="form-control" id="social-instagram" value="${esc(c.social_instagram || '')}" placeholder="https://instagram.com/..."></div>
            <div class="form-group"><label>Twitter / X</label><input class="form-control" id="social-twitter" value="${esc(c.social_twitter || '')}" placeholder="https://x.com/..."></div>
            <div class="form-group"><label>TikTok</label><input class="form-control" id="social-tiktok" value="${esc(c.social_tiktok || '')}" placeholder="https://tiktok.com/@..."></div>
        </div>`;

        ['whatsapp-number', 'social-facebook', 'social-instagram', 'social-twitter', 'social-tiktok'].forEach((id) => {
            document.getElementById(id).addEventListener('input', (e) => {
                const key = id === 'whatsapp-number' ? 'whatsapp_number' : id.replace('social-', 'social_');
                storeState.content[key] = e.target.value;
            });
        });

        document.getElementById('logo-upload-form').addEventListener('submit', (e) => uploadStoreAsset(e, 'logo', root));
        document.getElementById('banner-upload-form').addEventListener('submit', (e) => uploadStoreAsset(e, 'banner', root));
    }

    async function uploadStoreAsset(e, kind, root) {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            const result = await Api.upload(`/store-settings/upload/${kind}`, fd);
            storeState.content[kind + '_path'] = result.path;
            toast(`${kind === 'logo' ? 'Logo' : 'Banner'} uploaded`);
            drawBrandingTab(root);
        } catch (err) { toast(err.message, 'error'); }
    }

    function drawCheckoutSettingsTab(root) {
        const c = storeState.content;
        root.innerHTML = `
        <p class="text-muted">Choose how you want to be notified about — and how customers complete — online orders.</p>

        <div class="section-title">Admin Notification Email</div>
        <p class="text-muted" style="margin-top:-6px;">Where sale and order alerts are sent. Leave blank to use the store owner's login email.</p>
        <div class="form-group" style="max-width:320px;"><input class="form-control" id="notif-email" type="email" value="${esc(c.notification_email || '')}" placeholder="owner@example.com"></div>

        <div class="section-title">Order Completion Method</div>
        <div class="grid grid-3" id="channel-cards"></div>

        <div id="channel-extra" style="margin-top:18px;"></div>`;

        document.getElementById('notif-email').addEventListener('input', (e) => { storeState.content.notification_email = e.target.value; });

        const channels = [
            { id: 'email', label: 'Email Only', desc: 'Customer places the order and both of you get an email. Simplest option.' },
            { id: 'whatsapp', label: 'WhatsApp', desc: 'After checkout, the customer gets a button to finish arranging payment with you on WhatsApp.' },
            { id: 'bank_transfer', label: 'Bank Transfer', desc: 'After checkout, the customer sees your account details and clicks "I Have Paid" once they\u2019ve sent the money.' },
        ];
        const drawChannelCards = () => {
            document.getElementById('channel-cards').innerHTML = channels.map((ch) => `
                <div class="card theme-card ${c.order_channel === ch.id ? 'selected' : ''}" data-channel="${ch.id}" style="cursor:pointer;">
                    <strong>${ch.label}</strong>
                    <p class="text-muted" style="font-size:12.5px; margin:6px 0 0;">${ch.desc}</p>
                    ${c.order_channel === ch.id ? '<span class="badge badge-success" style="margin-top:8px;">Selected</span>' : ''}
                </div>`).join('');
            document.querySelectorAll('[data-channel]').forEach((card) => card.addEventListener('click', () => {
                c.order_channel = card.dataset.channel;
                drawChannelCards();
                drawChannelExtra();
            }));
        };
        const drawChannelExtra = () => {
            const extra = document.getElementById('channel-extra');
            if (c.order_channel === 'whatsapp') {
                extra.innerHTML = `<p class="text-muted">Uses the WhatsApp number set on the Branding tab.</p>`;
            } else if (c.order_channel === 'bank_transfer') {
                extra.innerHTML = `
                <div class="section-title">Bank Account Details</div>
                <div class="grid grid-3" style="max-width:760px;">
                    <div class="form-group"><label>Bank Name</label><input class="form-control" id="bank-name" value="${esc(c.bank_name || '')}"></div>
                    <div class="form-group"><label>Account Name</label><input class="form-control" id="bank-account-name" value="${esc(c.bank_account_name || '')}"></div>
                    <div class="form-group"><label>Account Number</label><input class="form-control" id="bank-account-number" value="${esc(c.bank_account_number || '')}"></div>
                </div>`;
                document.getElementById('bank-name').addEventListener('input', (e) => { c.bank_name = e.target.value; });
                document.getElementById('bank-account-name').addEventListener('input', (e) => { c.bank_account_name = e.target.value; });
                document.getElementById('bank-account-number').addEventListener('input', (e) => { c.bank_account_number = e.target.value; });
            } else {
                extra.innerHTML = '';
            }
        };
        drawChannelCards();
        drawChannelExtra();
    }

    function drawContentTab(root) {
        const fields = THEME_FIELDS[storeState.theme] || THEME_FIELDS.aurora;
        root.innerHTML = `<p class="text-muted">Editable text for the <strong>${storeState.theme}</strong> template. Use the AI button to generate a suggestion, then tweak it.</p>` +
            fields.map((f) => `
            <div class="form-group">
                <label>${f.label}</label>
                <div class="flex" style="gap:8px; align-items:flex-start;">
                    <textarea class="form-control" rows="2" data-field="${f.key}">${esc(storeState.content[f.key] || '')}</textarea>
                    ${f.aiKind ? `<button class="btn btn-secondary btn-sm" data-ai="${f.key}" data-aikind="${f.aiKind}" style="white-space:nowrap;">&#10024; AI</button>` : ''}
                </div>
            </div>`).join('');

        root.querySelectorAll('[data-field]').forEach((el) => el.addEventListener('input', () => {
            storeState.content[el.dataset.field] = el.value;
        }));
        root.querySelectorAll('[data-ai]').forEach((btn) => btn.addEventListener('click', async () => {
            btn.disabled = true; btn.textContent = '…';
            try {
                const result = await Api.post('/ai/generate-text', {
                    kind: btn.dataset.aikind,
                    context: { store_type: storeState.store_type, business_name: window.TENANT_NAME },
                });
                const textarea = root.querySelector(`[data-field="${btn.dataset.ai}"]`);
                textarea.value = result.text;
                storeState.content[btn.dataset.ai] = result.text;
            } catch (e) { toast(e.message, 'error'); }
            btn.disabled = false; btn.innerHTML = '&#10024; AI';
        }));
    }

    async function drawCategoriesTab(root) {
        root.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
        const categories = await Api.get('/products/categories');
        root.innerHTML = `
        <p class="text-muted">Shoppers filter your store by these categories, plus name and price.</p>
        <form id="new-cat-form" class="flex" style="gap:8px; margin-bottom:16px;">
            <input class="form-control" name="name" placeholder="New category name" required style="max-width:260px;">
            <button class="btn" type="submit">Add Category</button>
        </form>
        <div class="table-wrap"><table><thead><tr><th>Category</th></tr></thead><tbody>
            ${categories.map((c) => `<tr><td>${esc(c.name)}</td></tr>`).join('') || '<tr><td class="text-muted">No categories yet.</td></tr>'}
        </tbody></table></div>`;

        document.getElementById('new-cat-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            try { await Api.post('/products/categories', fd); toast('Category added'); drawCategoriesTab(root); }
            catch (err) { toast(err.message, 'error'); }
        });
    }

    async function drawStoreProductsTab(root, q = '') {
        root.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
        const products = await Api.get(`/products?q=${encodeURIComponent(q)}`);
        const rows = products.map((p) => `
            <tr>
                <td>${esc(p.name)}<br><span class="text-muted" style="font-size:12px;">${esc(p.sku)}</span></td>
                <td>${p.image_count > 0 ? `<span class="badge badge-success">${p.image_count} image${p.image_count == 1 ? '' : 's'}</span>` : '<span class="badge badge-muted">No images</span>'}</td>
                <td>${p.description ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-muted">Missing</span>'}</td>
                <td>${fmt(p.selling_price)}</td>
                <td>${p.is_on_store == 1 ? '<span class="badge badge-success">On Store</span>' : '<span class="badge badge-muted">Hidden</span>'}</td>
                <td><button class="btn btn-sm btn-secondary" data-manage="${p.id}">Manage Listing</button></td>
            </tr>`).join('') || '<tr><td colspan="6" class="text-muted">No products found.</td></tr>';

        root.innerHTML = `
        <input class="form-control" id="store-prod-search" style="max-width:320px; margin-bottom:14px;" placeholder="Search your products…" value="${esc(q)}">
        <div class="table-wrap"><table><thead><tr><th>Product</th><th>Images</th><th>Description</th><th>Price</th><th>Store Status</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table></div>
        <div id="store-prod-modal-root"></div>`;

        let debounce;
        document.getElementById('store-prod-search').addEventListener('input', (e) => {
            clearTimeout(debounce);
            debounce = setTimeout(() => drawStoreProductsTab(root, e.target.value), 300);
        });
        root.querySelectorAll('[data-manage]').forEach((btn) => btn.addEventListener('click', async () => {
            const full = await Api.get(`/products/${btn.dataset.manage}`);
            openStoreListingModal(full, () => drawStoreProductsTab(root, q));
        }));
    }

    function openStoreListingModal(product, onSaved) {
        const imagesHtml = (product.images || []).map((im) => `<div class="listing-thumb"><img src="${esc(assetUrl(im.image_path))}"></div>`).join('') || '<p class="text-muted">No images uploaded yet.</p>';
        (document.getElementById('store-prod-modal-root') || document.body).innerHTML = `
        <div class="modal-backdrop" id="modal-backdrop">
            <div class="modal" style="max-width:560px;">
                <button class="modal-close" id="modal-close">&times;</button>
                <h3>${esc(product.name)}</h3>

                <label>Product Images</label>
                <div class="flex" style="flex-wrap:wrap; gap:8px; margin:8px 0 12px;">${imagesHtml}</div>
                <form id="listing-image-form" class="flex" style="gap:8px; margin-bottom:18px;">
                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp" multiple id="listing-image-input">
                    <button class="btn btn-sm" type="submit">Upload</button>
                </form>

                <div class="form-group">
                    <label>Description</label>
                    <div class="flex" style="gap:8px; align-items:flex-start;">
                        <textarea class="form-control" id="listing-description" rows="3">${esc(product.description || '')}</textarea>
                        <button class="btn btn-secondary btn-sm" id="listing-ai-btn" style="white-space:nowrap;">&#10024; AI</button>
                    </div>
                </div>

                <div class="flex-between" style="margin-top:16px;">
                    <button class="btn btn-secondary" id="listing-save-desc">Save Description</button>
                    <button class="btn ${product.is_on_store == 1 ? 'btn-danger' : 'btn-accent'}" id="listing-toggle-store">${product.is_on_store == 1 ? 'Remove from Store' : 'List on Store'}</button>
                </div>
            </div>
        </div>`;

        const close = () => document.getElementById('store-prod-modal-root').innerHTML = '';
        document.getElementById('modal-close').addEventListener('click', close);

        document.getElementById('listing-image-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const files = document.getElementById('listing-image-input').files;
            if (!files.length) { toast('Choose at least one image', 'error'); return; }
            try {
                for (const file of files) {
                    const fd = new FormData();
                    fd.append('image', file);
                    await Api.upload(`/products/${product.id}/image`, fd);
                }
                toast('Image(s) uploaded');
                const refreshed = await Api.get(`/products/${product.id}`);
                openStoreListingModal(refreshed, onSaved);
            } catch (err) { toast(err.message, 'error'); }
        });

        document.getElementById('listing-ai-btn').addEventListener('click', async () => {
            const btn = document.getElementById('listing-ai-btn');
            btn.disabled = true; btn.textContent = '…';
            try {
                const result = await Api.post('/ai/generate-text', {
                    kind: 'product_description',
                    context: { name: product.name, category: product.category_name || '', price: product.selling_price },
                });
                document.getElementById('listing-description').value = result.text;
            } catch (e) { toast(e.message, 'error'); }
            btn.disabled = false; btn.innerHTML = '&#10024; AI';
        });

        document.getElementById('listing-save-desc').addEventListener('click', async () => {
            try {
                await Api.put(`/products/${product.id}`, { description: document.getElementById('listing-description').value });
                toast('Description saved');
            } catch (e) { toast(e.message, 'error'); }
        });

        document.getElementById('listing-toggle-store').addEventListener('click', async () => {
            const goingOn = product.is_on_store != 1;
            try {
                await Api.post(`/products/${product.id}/store`, { on_store: goingOn });
                toast(goingOn ? 'Product listed on store' : 'Product removed from store');
                close();
                onSaved();
            } catch (e) { toast(e.message, 'error'); }
        });
    }

    /* ---------------------------------------------------------------
       STAFF
       --------------------------------------------------------------- */
    async function renderStaff(content) {
        const staff = await Api.get('/staff');
        const isOwner = (currentUser() || {}).role === 'owner';
        const rows = staff.map((s) => `
            <tr><td>${esc(s.full_name)}</td><td>${esc(s.email)}</td><td><span class="badge badge-muted">${esc(s.role)}</span></td>
            <td>${s.is_active == 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Disabled</span>'}</td>
            <td>${isOwner ? `<button class="btn btn-sm btn-secondary" data-toggle="${s.id}" data-active="${s.is_active}">${s.is_active == 1 ? 'Disable' : 'Enable'}</button>` : ''}</td></tr>`).join('');

        content.innerHTML = `
        <div class="flex-between" style="margin-bottom:14px;">
            <h3 class="mt-0">Staff Accounts</h3>
            ${isOwner ? '<button class="btn" id="add-staff-btn">+ Add Staff</button>' : ''}
        </div>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table></div>

        <div class="section-title">Activity Log</div>
        <div id="activity-log" class="table-wrap"><table><tbody><tr><td class="text-muted">Loading…</td></tr></tbody></table></div>
        <div id="activity-pagination"></div>
        <div id="staff-modal-root"></div>`;

        loadActivityLog(1);

        if (isOwner) {
            document.getElementById('add-staff-btn').addEventListener('click', () => {
                document.getElementById('staff-modal-root').innerHTML = `
                <div class="modal-backdrop" id="modal-backdrop">
                    <div class="modal">
                        <button class="modal-close" id="modal-close">&times;</button>
                        <h3>Add Staff Account</h3>
                        <form id="staff-form">
                            <div class="form-group"><label>Full Name</label><input class="form-control" name="full_name" required></div>
                            <div class="form-group"><label>Email</label><input class="form-control" name="email" type="email" required></div>
                            <div class="form-group"><label>Password</label><input class="form-control" name="password" type="password" required minlength="6"></div>
                            <div class="form-group"><label>Role</label><select class="form-control" name="role"><option value="staff">Sales Staff</option><option value="manager">Manager</option><option value="owner">Owner</option></select></div>
                            <button class="btn" type="submit" style="width:100%; justify-content:center;">Create Account</button>
                        </form>
                    </div>
                </div>`;
                document.getElementById('modal-close').addEventListener('click', () => document.getElementById('staff-modal-root').innerHTML = '');
                document.getElementById('staff-form').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = Object.fromEntries(new FormData(e.target).entries());
                    try { await Api.post('/staff', fd); toast('Staff account created'); document.getElementById('staff-modal-root').innerHTML = ''; renderStaff(content); }
                    catch (err) { toast(err.message, 'error'); }
                });
            });
            content.querySelectorAll('[data-toggle]').forEach((btn) => btn.addEventListener('click', async () => {
                try { await Api.put(`/staff/${btn.dataset.toggle}`, { is_active: btn.dataset.active == '1' ? 0 : 1 }); renderStaff(content); }
                catch (e) { toast(e.message, 'error'); }
            }));
        }
    }

    async function loadActivityLog(page) {
        const result = await Api.get(`/activity-log?page=${page}&per_page=15`);
        document.getElementById('activity-log').innerHTML = `<table><thead><tr><th>When</th><th>Staff</th><th>Action</th></tr></thead><tbody>
            ${result.data.map((l) => `<tr><td>${dt(l.created_at)}</td><td>${esc(l.user_name || '—')}</td><td>${esc(l.description || l.action)}</td></tr>`).join('') || '<tr><td colspan="3" class="text-muted">No activity yet.</td></tr>'}
        </tbody></table>`;

        const pager = document.getElementById('activity-pagination');
        if (result.total_pages <= 1) { pager.innerHTML = ''; return; }
        let buttons = `<button ${result.page <= 1 ? 'disabled' : ''} data-page="${result.page - 1}">&laquo; Prev</button>`;
        for (let p = 1; p <= result.total_pages; p++) {
            if (p === 1 || p === result.total_pages || Math.abs(p - result.page) <= 1) {
                buttons += `<button class="${p === result.page ? 'active' : ''}" data-page="${p}">${p}</button>`;
            } else if (Math.abs(p - result.page) === 2) {
                buttons += `<span>…</span>`;
            }
        }
        buttons += `<button ${result.page >= result.total_pages ? 'disabled' : ''} data-page="${result.page + 1}">Next &raquo;</button>`;
        pager.innerHTML = `<div class="pagination">${buttons}</div>`;
        pager.querySelectorAll('[data-page]').forEach((btn) => btn.addEventListener('click', () => loadActivityLog(parseInt(btn.dataset.page, 10))));
    }

    /* ---------------------------------------------------------------
       BRANCHES (Phase 2 — Multi-Branch)
       --------------------------------------------------------------- */
    async function renderBranches(content) {
        const [branches, products] = await Promise.all([Api.get('/branches'), Api.get('/products')]);
        const isOwner = (currentUser() || {}).role === 'owner';
        const rows = branches.map((b) => `<tr><td>${esc(b.name)}</td><td>${esc(b.address || '—')}</td><td>${b.is_main == 1 ? '<span class="badge badge-success">Main</span>' : ''}</td></tr>`).join('');
        const branchOptions = branches.map((b) => `<option value="${b.id}">${esc(b.name)}</option>`).join('');
        const productOptions = products.map((p) => `<option value="${p.id}">${esc(p.name)} (${p.quantity} in stock)</option>`).join('');

        content.innerHTML = `
        <div class="flex-between" style="margin-bottom:14px;">
            <h3 class="mt-0">Branches</h3>
            ${isOwner ? '<button class="btn" id="add-branch-btn">+ Add Branch</button>' : ''}
        </div>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Address</th><th></th></tr></thead><tbody>${rows}</tbody></table></div>

        <div class="section-title">Transfer Stock Between Branches</div>
        <div class="card">
            <form id="transfer-form">
                <div class="form-row">
                    <div class="form-group"><label>Product</label><select class="form-control" name="product_id" required>${productOptions}</select></div>
                    <div class="form-group"><label>Quantity</label><input class="form-control" name="quantity" type="number" min="1" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>From Branch</label><select class="form-control" name="from_branch_id" required>${branchOptions}</select></div>
                    <div class="form-group"><label>To Branch</label><select class="form-control" name="to_branch_id" required>${branchOptions}</select></div>
                </div>
                <button class="btn" type="submit">Transfer Stock</button>
            </form>
        </div>
        <div id="branch-modal-root"></div>`;

        document.getElementById('transfer-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target).entries());
            try { await Api.post('/branches/transfer-stock', fd); toast('Stock transferred'); renderBranches(content); }
            catch (err) { toast(err.message, 'error'); }
        });

        if (isOwner) {
            document.getElementById('add-branch-btn').addEventListener('click', () => {
                document.getElementById('branch-modal-root').innerHTML = `
                <div class="modal-backdrop" id="modal-backdrop">
                    <div class="modal">
                        <button class="modal-close" id="modal-close">&times;</button>
                        <h3>Add Branch</h3>
                        <form id="branch-form">
                            <div class="form-group"><label>Branch Name</label><input class="form-control" name="name" required></div>
                            <div class="form-group"><label>Address</label><input class="form-control" name="address"></div>
                            <button class="btn" type="submit" style="width:100%; justify-content:center;">Add Branch</button>
                        </form>
                    </div>
                </div>`;
                document.getElementById('modal-close').addEventListener('click', () => document.getElementById('branch-modal-root').innerHTML = '');
                document.getElementById('branch-form').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = Object.fromEntries(new FormData(e.target).entries());
                    try { await Api.post('/branches', fd); toast('Branch added'); document.getElementById('branch-modal-root').innerHTML = ''; renderBranches(content); }
                    catch (err) { toast(err.message, 'error'); }
                });
            });
        }
    }

    /* ---------------------------------------------------------------
       REPORTS
       --------------------------------------------------------------- */
    async function renderReports(content) {
        const today = new Date().toISOString().slice(0, 10);
        const monthStart = today.slice(0, 8) + '01';

        content.innerHTML = `
        <div class="card" style="margin-bottom:16px;">
            <div class="form-row" style="align-items:flex-end;">
                <div class="form-group"><label>From</label><input class="form-control" id="rep-from" type="date" value="${monthStart}"></div>
                <div class="form-group"><label>To</label><input class="form-control" id="rep-to" type="date" value="${today}"></div>
                <div class="form-group"><label>Report</label>
                    <select class="form-control" id="rep-type">
                        <option value="sales">Sales</option><option value="profit">Profit</option>
                        <option value="inventory">Inventory</option><option value="staff-performance">Staff Performance</option>
                        <option value="customers">Customers</option>
                    </select>
                </div>
                <div class="form-group"><button class="btn" id="rep-run">Run Report</button></div>
                <div class="form-group"><button class="btn btn-secondary" id="rep-export">Export CSV</button></div>
            </div>
        </div>
        <div id="report-output"></div>`;

        async function run() {
            const from = document.getElementById('rep-from').value;
            const to = document.getElementById('rep-to').value;
            const type = document.getElementById('rep-type').value;
            const out = document.getElementById('report-output');
            out.innerHTML = '<div class="empty-state"><div class="spinner"></div></div>';
            const data = await Api.get(`/reports/${type}?from=${from}&to=${to}`);
            out.innerHTML = renderReportTable(type, data);
        }

        document.getElementById('rep-run').addEventListener('click', run);
        document.getElementById('rep-export').addEventListener('click', async () => {
            const from = document.getElementById('rep-from').value;
            const to = document.getElementById('rep-to').value;
            const type = document.getElementById('rep-type').value;
            try {
                const { access } = Api.tokens();
                const res = await fetch(`/api/${slug}/reports/export?type=${type}&from=${from}&to=${to}`, { headers: { Authorization: `Bearer ${access}` } });
                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = `${type}_report.csv`; a.click();
                URL.revokeObjectURL(url);
            } catch (e) { toast('Export failed', 'error'); }
        });

        run();
    }

    function renderReportTable(type, data) {
        if (type === 'sales') {
            const rows = data.by_day.map((d) => `<tr><td>${d.day}</td><td>${d.sales_count}</td><td>${fmt(d.revenue)}</td></tr>`).join('') || '<tr><td colspan="3" class="text-muted">No sales in this range.</td></tr>';
            return `<div class="grid grid-3" style="margin-bottom:14px;">
                <div class="card stat-card"><div class="stat-label">Sales Count</div><div class="stat-value">${data.totals.sales_count}</div></div>
                <div class="card stat-card"><div class="stat-label">Revenue</div><div class="stat-value">${fmt(data.totals.revenue)}</div></div>
                <div class="card stat-card"><div class="stat-label">Discounts Given</div><div class="stat-value">${fmt(data.totals.total_discount)}</div></div>
            </div><div class="table-wrap"><table><thead><tr><th>Day</th><th>Sales</th><th>Revenue</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        }
        if (type === 'profit') {
            const rows = data.per_product.map((p) => `<tr><td>${esc(p.name)}</td><td>${p.units_sold}</td><td>${fmt(p.revenue)}</td><td>${fmt(p.cost)}</td><td>${fmt(p.profit)}</td><td>${p.margin_pct}%</td></tr>`).join('') || '<tr><td colspan="6" class="text-muted">No sales in this range.</td></tr>';
            return `<div class="grid grid-4" style="margin-bottom:14px;">
                <div class="card stat-card"><div class="stat-label">Revenue</div><div class="stat-value">${fmt(data.summary.total_revenue)}</div></div>
                <div class="card stat-card"><div class="stat-label">Gross Profit</div><div class="stat-value">${fmt(data.summary.gross_profit)}</div></div>
                <div class="card stat-card"><div class="stat-label">Expenses</div><div class="stat-value">${fmt(data.summary.expenses)}</div></div>
                <div class="card stat-card"><div class="stat-label">Net Profit</div><div class="stat-value">${fmt(data.summary.net_profit)}</div></div>
            </div><div class="table-wrap"><table><thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Margin</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        }
        if (type === 'inventory') {
            const rows = data.map((p) => `<tr><td>${esc(p.name)}</td><td>${esc(p.category_name || '—')}</td><td>${p.quantity}</td><td>${fmt(p.stock_value)}</td></tr>`).join('') || '<tr><td colspan="4" class="text-muted">No products.</td></tr>';
            return `<div class="table-wrap"><table><thead><tr><th>Product</th><th>Category</th><th>Qty</th><th>Stock Value</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        }
        if (type === 'staff-performance') {
            const rows = data.map((s) => `<tr><td>${esc(s.full_name)}</td><td>${esc(s.role)}</td><td>${s.total_sales}</td><td>${fmt(s.total_revenue)}</td></tr>`).join('') || '<tr><td colspan="4" class="text-muted">No data.</td></tr>';
            return `<div class="table-wrap"><table><thead><tr><th>Staff</th><th>Role</th><th>Sales</th><th>Revenue</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        }
        if (type === 'customers') {
            const rows = data.map((c) => `<tr><td>${esc(c.name)}</td><td>${esc(c.phone || '—')}</td><td>${c.total_orders}</td><td>${fmt(c.lifetime_value)}</td><td>${fmt(c.outstanding_debt)}</td></tr>`).join('') || '<tr><td colspan="5" class="text-muted">No customers.</td></tr>';
            return `<div class="table-wrap"><table><thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Lifetime Value</th><th>Debt</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        }
        return '<p class="text-muted">Unknown report.</p>';
    }

    /* ---------------------------------------------------------------
       PLANS & BILLING
       --------------------------------------------------------------- */
    async function renderPlans(content) {
        const [plansRes, status] = await Promise.all([
            fetch(`${window.APP_BASE || ''}/api/plans`).then((r) => r.json()),
            Api.get('/plan-status'),
        ]);
        const plans = (plansRes && plansRes.data) || [];
        const currentKey = status.plan ? status.plan.key : null;

        const bannerHtml = status.status === 'expired'
            ? `<div class="plan-locked-banner"><strong>Your free trial has expired.</strong> Choose a plan below to restore full access — your data is safe and waiting.</div>`
            : status.status === 'trial'
                ? `<div class="plan-locked-banner">You're on a free trial with <strong>${status.days_remaining}</strong> day(s) left. Pick a plan any time to keep going after it ends.</div>`
                : `<div class="plan-locked-banner" style="background:#e7f8f0; border-color:#bfe9d3; color:#0e7a53;">You're on the <strong>${esc(status.plan ? status.plan.name : '')}</strong> plan — renews in ${status.days_remaining} day(s).</div>`;

        const cardsHtml = plans.map((p) => {
            const isCurrent = p.key === currentKey && status.status === 'active';
            const features = (p.features || []).map((f) => `<li class="${f.enabled ? '' : 'text-muted'}">${f.enabled ? '&#10003;' : '&#10005;'} ${esc(f.feature_label)}</li>`).join('');
            return `
            <div class="card" style="${p.key === 'advanced' ? 'border-color: var(--color-primary); box-shadow: 0 0 0 2px var(--color-primary-light);' : ''}">
                <div class="plan-pill ${esc(p.key)}">${esc(p.name)}</div>
                <div style="font-size:30px; font-weight:800; margin: 14px 0 2px;">${fmt(p.price_monthly)}<span style="font-size:13px; font-weight:500; color:var(--color-text-muted);">/month</span></div>
                <p class="text-muted" style="min-height:36px;">${esc(p.description || '')}</p>
                <ul style="list-style:none; padding:0; margin: 14px 0; font-size:13px; display:flex; flex-direction:column; gap:8px;">${features}</ul>
                <button class="btn ${isCurrent ? 'btn-secondary' : ''}" style="width:100%; justify-content:center;" data-plan="${esc(p.key)}" ${isCurrent ? 'disabled' : ''}>${isCurrent ? 'Current Plan' : 'Choose ' + esc(p.name)}</button>
            </div>`;
        }).join('');

        content.innerHTML = `
        <div class="page-header"><div><h2>Plans &amp; Billing</h2><p>Pick the plan that fits your business — upgrade or renew any time.</p></div></div>
        ${bannerHtml}
        <div class="grid grid-3" style="align-items:stretch; margin-top:16px;">${cardsHtml}</div>
        <div id="pay-modal-root"></div>`;

        content.querySelectorAll('[data-plan]').forEach((btn) => {
            btn.addEventListener('click', () => startPlanCheckout(btn.dataset.plan, content));
        });
    }

    async function startPlanCheckout(planKey, content) {
        try {
            const result = await Api.post('/payments/initialize', { plan_key: planKey });
            if (result.simulated) {
                const root = document.getElementById('pay-modal-root');
                root.innerHTML = `
                <div class="modal-backdrop">
                    <div class="modal">
                        <button class="modal-close" id="sim-close">&times;</button>
                        <h3>Development checkout</h3>
                        <p class="text-muted">Flutterwave isn't configured on this server yet, so here's a stand-in checkout for testing the flow end-to-end.</p>
                        <button class="btn" id="sim-confirm" style="width:100%; justify-content:center;">Simulate Successful Payment</button>
                    </div>
                </div>`;
                document.getElementById('sim-close').addEventListener('click', () => root.innerHTML = '');
                document.getElementById('sim-confirm').addEventListener('click', async () => {
                    try {
                        await Api.post('/payments/verify', { tx_ref: result.tx_ref });
                        toast('Payment confirmed — plan activated!', 'success');
                        root.innerHTML = '';
                        renderShell();
                        navigate('');
                    } catch (e) { toast(e.message, 'error'); }
                });
                return;
            }
            // Real Flutterwave checkout — send the browser to the hosted payment page.
            window.location.href = result.link;
        } catch (e) {
            toast(e.message, 'error');
        }
    }

    /* ---------------------------------------------------------------
       EARNINGS (online store — Flutterwave payments + withdrawals)
       --------------------------------------------------------------- */
    async function renderEarnings(content) {
        const d = await Api.get('/earnings');
        const paymentsRows = (d.recent_payments || []).map((p) => `
            <tr><td>${esc(p.order_no)}</td><td>${esc(p.customer_name)}</td><td>${fmt(p.amount_paid)}</td><td>${new Date(p.created_at).toLocaleDateString()}</td></tr>
        `).join('') || `<tr><td colspan="4" class="text-muted">No Flutterwave payments yet.</td></tr>`;

        const statusBadge = (s) => ({
            requested: 'badge-warn', processing: 'badge-warn', paid: 'badge-success', rejected: 'badge-danger',
        }[s] || 'badge-muted');

        const withdrawalRows = (d.withdrawals || []).map((w) => `
            <tr>
                <td>${fmt(w.amount)}</td>
                <td>${fmt(w.net_amount)} ${Number(w.fee_percent) > 0 ? `<span class="text-muted">(${w.fee_percent}% fee)</span>` : ''}</td>
                <td><span class="badge ${statusBadge(w.status)}">${esc(w.status)}</span></td>
                <td>${new Date(w.created_at).toLocaleDateString()}</td>
            </tr>`).join('') || `<tr><td colspan="4" class="text-muted">No withdrawal requests yet.</td></tr>`;

        content.innerHTML = `
        <div class="page-header">
            <div><h2>Earnings</h2><p>Payments collected through your online store via Flutterwave.</p></div>
            <div class="page-header-actions"><button class="btn btn-dark" id="withdraw-btn">&#128176; Request Withdrawal</button></div>
        </div>
        <div class="grid grid-3" style="margin-bottom:20px;">
            <div class="card stat-card"><div class="stat-label">Total Earned</div><div class="stat-value">${fmt(d.total_earned)}</div><div class="stat-sub">All-time, via Flutterwave</div></div>
            <div class="card stat-card"><div class="stat-label">Available Balance</div><div class="stat-value">${fmt(d.available_balance)}</div><div class="stat-sub">Ready to withdraw</div></div>
            <div class="card stat-card"><div class="stat-label">Withdrawal Fee</div><div class="stat-value">${d.fee_percent}%</div><div class="stat-sub">Charged on store withdrawals</div></div>
        </div>
        <div class="section-title">Recent Payments</div>
        <div class="table-wrap" style="margin-bottom:24px;"><table><thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Date</th></tr></thead><tbody>${paymentsRows}</tbody></table></div>
        <div class="section-title">Withdrawal History</div>
        <div class="table-wrap"><table><thead><tr><th>Requested</th><th>You'll receive</th><th>Status</th><th>Date</th></tr></thead><tbody>${withdrawalRows}</tbody></table></div>
        <div id="withdraw-modal-root"></div>`;

        document.getElementById('withdraw-btn').addEventListener('click', () => openWithdrawModal('/earnings/withdraw', d.available_balance, d.fee_percent, () => renderEarnings(content)));
    }

    function openWithdrawModal(endpoint, availableBalance, feePercent, onDone) {
        const root = document.getElementById('withdraw-modal-root') || (() => {
            const el = document.createElement('div'); document.body.appendChild(el); return el;
        })();
        root.innerHTML = `
        <div class="modal-backdrop">
            <div class="modal">
                <button class="modal-close" id="wd-close">&times;</button>
                <h3>Request Withdrawal</h3>
                <p class="text-muted">Available balance: <strong>${fmt(availableBalance)}</strong>${feePercent > 0 ? ` &middot; ${feePercent}% fee applies` : ''}. Withdrawals are processed manually and take a maximum of 3 hours.</p>
                <form id="wd-form">
                    <div class="form-group"><label>Amount</label><input class="form-control" name="amount" type="number" min="1" step="0.01" max="${availableBalance}" required></div>
                    <div class="form-group"><label>Bank name</label><input class="form-control" name="bank_name" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Account name</label><input class="form-control" name="account_name" required></div>
                        <div class="form-group"><label>Account number</label><input class="form-control" name="account_number" required></div>
                    </div>
                    <div id="wd-error" class="form-error"></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Submit Request</button>
                </form>
            </div>
        </div>`;
        document.getElementById('wd-close').addEventListener('click', () => root.innerHTML = '');
        document.getElementById('wd-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());
            try {
                await Api.post(endpoint, data);
                toast('Withdrawal requested — processed within 3 hours.', 'success');
                root.innerHTML = '';
                if (onDone) onDone();
            } catch (err) {
                document.getElementById('wd-error').textContent = err.message;
            }
        });
    }

    /* ---------------------------------------------------------------
       BOOT
       --------------------------------------------------------------- */
    async function boot() {
        const { access } = Api.tokens();
        if (!access) { renderLogin(); return; }

        // Fetch plan status before drawing the shell so the sidebar's locked
        // icons and upgrade card reflect it from the very first paint.
        let status = null;
        try {
            status = await Api.get('/plan-status');
            localStorage.setItem('plan', JSON.stringify(status));
        } catch (e) { /* best-effort — shell falls back to "nothing locked" */ }

        renderShell();

        if (status && status.status === 'expired' && currentSub() !== 'plans') {
            navigate('plans');
            return;
        }
        navigate(currentSub(), false);
    }

    boot();
})();