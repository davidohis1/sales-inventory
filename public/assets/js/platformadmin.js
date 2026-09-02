/* =========================================================================
   Platform Admin SPA — vanilla JS, mirrors the structure of admin.js but for
   the SaaS operator's own two-page dashboard (not tenant-scoped).
   ========================================================================= */
(function () {
    const BASE = window.APP_BASE || '';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const fmt = (n) => '\u20a6' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const dt = (s) => s ? new Date(s.replace(' ', 'T')).toLocaleDateString() : '—';

    function toast(msg, type = 'success') {
        const c = document.getElementById('toast-container');
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.textContent = msg;
        c.appendChild(el);
        setTimeout(() => el.remove(), 3800);
    }
    function loading(on) {
        const bar = document.getElementById('loading-bar');
        if (!bar) return;
        bar.style.width = on ? '70%' : '100%';
        if (!on) setTimeout(() => { bar.style.width = '0%'; }, 250);
    }

    /* ---------------------------------------------------------------
       API
       --------------------------------------------------------------- */
    const PA = {
        token: () => localStorage.getItem('pa_access_token'),
        setToken: (t) => localStorage.setItem('pa_access_token', t),
        clearToken: () => localStorage.removeItem('pa_access_token'),
        async request(method, path, body) {
            loading(true);
            const headers = { 'Content-Type': 'application/json' };
            const token = PA.token();
            if (token) headers['Authorization'] = `Bearer ${token}`;
            let res;
            try {
                res = await fetch(`${BASE}/api/platformadmin${path}`, { method, headers, body: body ? JSON.stringify(body) : null });
            } catch (e) {
                loading(false);
                throw new Error('Network error — could not reach the server.');
            }
            loading(false);
            const json = await res.json().catch(() => ({ success: false, message: 'Invalid server response' }));
            if (!json.success) {
                if (res.status === 401) { PA.clearToken(); renderLogin(); }
                throw new Error(json.message || 'Request failed');
            }
            return json.data;
        },
        get: (p) => PA.request('GET', p),
        post: (p, b) => PA.request('POST', p, b),
        put: (p, b) => PA.request('PUT', p, b),
    };

    /* ---------------------------------------------------------------
       LOGIN
       --------------------------------------------------------------- */
    function renderLogin() {
        document.getElementById('pa-app').innerHTML = `
        <div class="login-page">
            <div class="login-card">
                <h2>Platform Admin</h2>
                <p class="text-muted" style="margin-top:-8px;">Sign in to manage the platform.</p>
                <form id="pa-login-form">
                    <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required autofocus></div>
                    <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div>
                    <div id="pa-login-error" class="form-error" style="display:none; margin-bottom:10px;"></div>
                    <button class="btn" type="submit" style="width:100%; justify-content:center;">Log In</button>
                </form>
                <p class="demo-hint">This is a separate login from tenant business accounts — for the platform operator only.</p>
            </div>
        </div>`;
        document.getElementById('pa-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errBox = document.getElementById('pa-login-error');
            errBox.style.display = 'none';
            const form = new FormData(e.target);
            try {
                const data = await PA.post('/auth/login', Object.fromEntries(form.entries()));
                PA.setToken(data.access_token);
                boot();
            } catch (err) {
                errBox.textContent = err.message;
                errBox.style.display = 'block';
            }
        });
    }

    /* ---------------------------------------------------------------
       SHELL
       --------------------------------------------------------------- */
    const PAGES = [
        { path: '', label: 'Overview', icon: '&#128202;', render: renderOverview },
        { path: 'businesses', label: 'Businesses', icon: '&#127970;', render: renderBusinesses },
    ];

    function paPath(sub) { return `${BASE}/platformadmin${sub ? '/' + sub : ''}`; }
    function currentSub() {
        const p = window.location.pathname.replace(`${BASE}/platformadmin`, '').replace(/^\//, '');
        return p === 'businesses' ? 'businesses' : '';
    }

    function renderShell() {
        document.getElementById('pa-app').innerHTML = `
        <div class="pa-shell">
            <div class="pa-sidebar" id="pa-sidebar">
                <div class="pa-brand"><span class="logo-dot"></span> Platform Admin</div>
                ${PAGES.map((p) => `<div class="pa-nav-item" data-path="${p.path}"><span>${p.icon}</span> ${p.label}</div>`).join('')}
                <button class="pa-logout" id="pa-logout">&#10140; Log out</button>
            </div>
            <div class="pa-main">
                <div class="pa-topbar">
                    <button class="menu-toggle" id="pa-menu-toggle" style="display:none;">&#9776;</button>
                    <h1 id="pa-page-title" style="font-size:17px; margin:0; font-weight:700;">Overview</h1>
                </div>
                <div class="pa-content" id="pa-content"></div>
            </div>
        </div>`;

        document.querySelectorAll('.pa-nav-item').forEach((el) => {
            el.addEventListener('click', () => { document.getElementById('pa-sidebar').classList.remove('open'); navigate(el.dataset.path); });
        });
        document.getElementById('pa-logout').addEventListener('click', () => { PA.clearToken(); renderLogin(); });
        document.getElementById('pa-menu-toggle').addEventListener('click', () => document.getElementById('pa-sidebar').classList.toggle('open'));
        window.matchMedia('(max-width: 900px)').matches && (document.getElementById('pa-menu-toggle').style.display = 'inline-flex');
    }

    async function navigate(sub, push = true) {
        const page = PAGES.find((p) => p.path === sub) || PAGES[0];
        if (push) history.pushState({}, '', paPath(page.path));
        document.querySelectorAll('.pa-nav-item').forEach((el) => el.classList.toggle('active', el.dataset.path === page.path));
        document.getElementById('pa-page-title').textContent = page.label;
        const content = document.getElementById('pa-content');
        content.innerHTML = '<div class="empty-state"><div class="spinner"></div><p>Loading…</p></div>';
        try { await page.render(content); }
        catch (e) { content.innerHTML = `<div class="card"><p class="text-muted">Couldn't load this page: ${esc(e.message)}</p></div>`; }
    }
    window.addEventListener('popstate', () => navigate(currentSub(), false));

    /* ---------------------------------------------------------------
       PAGE 1: OVERVIEW — stats + plan/price/feature management
       --------------------------------------------------------------- */
    async function renderOverview(content) {
        const [stats, plans] = await Promise.all([PA.get('/stats'), PA.get('/plans')]);

        const recentRows = (stats.recent_payments || []).map((p) => `
            <tr><td>${esc(p.business_name)}</td><td>${esc(p.plan_name)}</td><td>${fmt(p.amount)}</td><td>${dt(p.created_at)}</td></tr>
        `).join('') || '<tr><td colspan="4" class="text-muted">No payments yet.</td></tr>';

        content.innerHTML = `
        <div class="page-header"><div><h2>Platform Overview</h2><p>How the whole platform is doing, at a glance.</p></div></div>
        <div class="grid grid-4">
            <div class="card stat-card"><div class="card-icon">&#127970;</div><div class="stat-label">Total Businesses</div><div class="stat-value">${stats.total_businesses}</div></div>
            <div class="card stat-card"><div class="card-icon">&#9200;</div><div class="stat-label">On Trial</div><div class="stat-value">${stats.trial_count}</div></div>
            <div class="card stat-card"><div class="card-icon">&#9989;</div><div class="stat-label">Active Subscriptions</div><div class="stat-value">${stats.active_count}</div></div>
            <div class="card stat-card ${stats.expired_count > 0 ? 'danger' : ''}"><div class="card-icon">&#9888;</div><div class="stat-label">Expired</div><div class="stat-value">${stats.expired_count}</div></div>
        </div>

        <div class="section-title">Revenue (last 30 days)</div>
        <div class="card" style="margin-bottom:10px;"><div class="stat-value">${fmt(stats.revenue_last_30_days)}</div></div>

        <div class="section-title">Recent Payments</div>
        <div class="table-wrap" style="margin-bottom:26px;"><table><thead><tr><th>Business</th><th>Plan</th><th>Amount</th><th>Date</th></tr></thead><tbody>${recentRows}</tbody></table></div>

        <div class="section-title">Manage Plans, Pricing &amp; Features</div>
        <div class="grid grid-3" id="plan-editor-grid"></div>`;

        renderPlanEditors(plans);
    }

    function renderPlanEditors(plans) {
        const grid = document.getElementById('plan-editor-grid');
        grid.innerHTML = plans.map((p) => `
            <div class="card" data-plan-id="${p.id}">
                <div class="plan-pill ${esc(p.key)}">${esc(p.key)}</div>
                <div class="form-group" style="margin-top:12px;">
                    <label>Plan name</label>
                    <input class="form-control plan-name-input" value="${esc(p.name)}">
                </div>
                <div class="form-group">
                    <label>Price / month (&#8358;)</label>
                    <input class="form-control plan-price-input" type="number" step="0.01" value="${p.price_monthly}">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input class="form-control plan-desc-input" value="${esc(p.description || '')}">
                </div>
                <label style="font-size:12.5px; font-weight:600; color:var(--color-text-muted); display:block; margin: 10px 0 6px;">Features included</label>
                <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:14px;">
                    ${p.features.map((f) => `
                        <label class="flex" style="font-size:13px; cursor:pointer;">
                            <input type="checkbox" class="plan-feature-check" data-feature="${esc(f.feature_key)}" ${f.enabled ? 'checked' : ''}>
                            ${esc(f.feature_label)}
                        </label>`).join('')}
                </div>
                <button class="btn btn-save-plan" style="width:100%; justify-content:center;">Save Changes</button>
            </div>`).join('');

        grid.querySelectorAll('.btn-save-plan').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const card = btn.closest('[data-plan-id]');
                const id = card.dataset.planId;
                const features = {};
                card.querySelectorAll('.plan-feature-check').forEach((chk) => { features[chk.dataset.feature] = chk.checked; });
                btn.disabled = true; btn.textContent = 'Saving…';
                try {
                    await PA.put(`/plans/${id}`, {
                        name: card.querySelector('.plan-name-input').value,
                        price_monthly: parseFloat(card.querySelector('.plan-price-input').value || '0'),
                        description: card.querySelector('.plan-desc-input').value,
                        features,
                    });
                    toast('Plan updated', 'success');
                } catch (e) { toast(e.message, 'error'); }
                btn.disabled = false; btn.textContent = 'Save Changes';
            });
        });
    }

    /* ---------------------------------------------------------------
       PAGE 2: BUSINESSES — plan, days-to-expiry (color-coded), reminder
       --------------------------------------------------------------- */
    function expiryClass(days) {
        if (days <= 7) return 'expiry-red';
        if (days <= 14) return 'expiry-amber';
        return 'expiry-green';
    }
    function expiryLabel(days) {
        if (days < 0) return `Expired ${Math.abs(days)}d ago`;
        if (days === 0) return 'Expires today';
        return `${days} day(s) left`;
    }

    async function renderBusinesses(content) {
        const businesses = await PA.get('/businesses');
        const rows = businesses.map((b) => `
            <tr data-id="${b.id}">
                <td><strong>${esc(b.business_name)}</strong><div class="text-muted" style="font-size:11.5px;">/${esc(b.slug)}</div></td>
                <td>${esc(b.owner_email || '—')}</td>
                <td>${b.plan_name ? `<span class="badge badge-muted">${esc(b.plan_name)}</span>` : '<span class="badge badge-warn">Trial</span>'}</td>
                <td>${b.status === 'expired' ? '<span class="badge badge-danger">Expired</span>' : b.status === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warn">Trial</span>'}</td>
                <td class="${expiryClass(b.days_remaining)}">${expiryLabel(b.days_remaining)}</td>
                <td>${b.last_reminder_sent_at ? dt(b.last_reminder_sent_at) : '—'}</td>
                <td><button class="btn btn-sm btn-secondary btn-remind">Send Reminder</button></td>
            </tr>`).join('') || '<tr><td colspan="7" class="text-muted">No businesses yet.</td></tr>';

        content.innerHTML = `
        <div class="page-header"><div><h2>Businesses</h2><p>Every business on the platform, their plan, and how soon they expire.</p></div></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Business</th><th>Owner Email</th><th>Plan</th><th>Status</th><th>Expiry</th><th>Last Reminder</th><th></th></tr></thead>
            <tbody>${rows}</tbody>
        </table></div>`;

        content.querySelectorAll('.btn-remind').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const id = btn.closest('tr').dataset.id;
                btn.disabled = true; btn.textContent = 'Sending…';
                try {
                    const res = await PA.post(`/businesses/${id}/remind`);
                    toast(`Reminder sent to ${res.sent_to}`, 'success');
                    btn.textContent = 'Sent ✓';
                } catch (e) {
                    toast(e.message, 'error');
                    btn.disabled = false; btn.textContent = 'Send Reminder';
                }
            });
        });
    }

    /* ---------------------------------------------------------------
       BOOT
       --------------------------------------------------------------- */
    function boot() {
        if (!PA.token()) { renderLogin(); return; }
        renderShell();
        navigate(currentSub(), false);
    }
    boot();
})();
