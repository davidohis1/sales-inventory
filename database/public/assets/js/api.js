/**
 * Lightweight fetch wrapper: attaches JWT, auto-refreshes expired access
 * tokens once, and shows the top loading bar during requests.
 */
const Api = (() => {
    const base = `${window.APP_BASE || ''}/api/${window.TENANT_SLUG}`;
    let refreshing = null;

    function tokens() {
        return {
            access: localStorage.getItem('access_token'),
            refresh: localStorage.getItem('refresh_token'),
        };
    }

    function setTokens(access, refresh) {
        if (access) localStorage.setItem('access_token', access);
        if (refresh) localStorage.setItem('refresh_token', refresh);
    }

    function clearTokens() {
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('user');
    }

    function showLoading() {
        const bar = document.getElementById('loading-bar');
        if (bar) { bar.style.width = '70%'; }
    }
    function hideLoading() {
        const bar = document.getElementById('loading-bar');
        if (bar) { bar.style.width = '100%'; setTimeout(() => { bar.style.width = '0%'; }, 250); }
    }

    async function doRefresh() {
        const { refresh } = tokens();
        if (!refresh) return false;
        try {
            const res = await fetch(`${base}/auth/refresh`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ refresh_token: refresh }),
            });
            const json = await res.json();
            if (json.success) {
                setTokens(json.data.access_token, null);
                return true;
            }
        } catch (e) { /* ignore */ }
        return false;
    }

    async function request(method, path, body = null, opts = {}) {
        showLoading();
        const { access } = tokens();
        const headers = { 'Content-Type': 'application/json' };
        if (access && !opts.noAuth) headers['Authorization'] = `Bearer ${access}`;

        let res;
        try {
            res = await fetch(base + path, {
                method,
                headers,
                body: body ? JSON.stringify(body) : null,
            });
        } catch (e) {
            hideLoading();
            throw new Error('Network error — could not reach the server.');
        }

        if (res.status === 401 && !opts.noAuth && !opts._retried) {
            if (!refreshing) refreshing = doRefresh().finally(() => { refreshing = null; });
            const ok = await refreshing;
            hideLoading();
            if (ok) return request(method, path, body, { ...opts, _retried: true });
            clearTokens();
            window.location.reload();
            return;
        }

        hideLoading();
        const json = await res.json().catch(() => ({ success: false, message: 'Invalid server response' }));
        if (!json.success) {
            const err = new Error(json.message || 'Request failed');
            err.errors = json.errors;
            err.status = res.status;
            throw err;
        }
        return json.data;
    }

    async function upload(path, formData) {
        showLoading();
        const { access } = tokens();
        const res = await fetch(base + path, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${access}` },
            body: formData,
        });
        hideLoading();
        const json = await res.json().catch(() => ({ success: false, message: 'Invalid server response' }));
        if (!json.success) throw new Error(json.message || 'Upload failed');
        return json.data;
    }

    return {
        get: (path) => request('GET', path),
        post: (path, body, opts) => request('POST', path, body, opts),
        put: (path, body) => request('PUT', path, body),
        del: (path) => request('DELETE', path),
        upload,
        tokens, setTokens, clearTokens,
    };
})();
