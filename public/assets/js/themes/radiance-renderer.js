/* =========================================================================
   Radiance theme — its own product-card and product-detail markup.
   Loaded before store.js's render calls; store.js picks these up via
   window.themeCardRenderer / window.themeDetailRenderer instead of falling
   back to the shared default templates, so Radiance never shares product
   card or product-detail markup with any other theme.
   ========================================================================= */
(function () {
    window.themeCardRenderer = function (p, u) {
        return `
        <a class="rd-card" href="${u.appBase}/${u.slug}/product/${p.id}" data-id="${p.id}">
            <div class="rd-card-thumb">
                ${u.imageTag(p)}
                <button class="rd-card-wish" data-wish="${p.id}" title="Wishlist" onclick="event.preventDefault()">&#9825;</button>
            </div>
            <div class="rd-card-body">
                <div class="rd-card-cat">${u.esc(p.category_name || '')}</div>
                <div class="rd-card-name">${u.esc(p.name)}</div>
                <div class="rd-card-price">${u.fmt(p.selling_price)}</div>
                <button class="rd-card-add" data-quickadd="${p.id}" onclick="event.preventDefault()">Add to Cart</button>
            </div>
        </a>`;
    };

    window.themeDetailRenderer = function (p, ctx) {
        const { images, variantPickerHtml, specs, fmt, esc, assetUrl } = ctx;
        const inStock = (p.quantity || 0) > 0;
        return `
            <div class="rd-detail">
                <div class="rd-gallery">
                    <div class="rd-main-wrap" id="rd-main-wrap">
                        <div class="gallery-main">${images[0].image_path ? `<img src="${esc(assetUrl(images[0].image_path))}">` : '<span class="no-image">No image available</span>'}</div>
                        ${images.length > 1 ? `<div class="rd-img-count" id="rd-img-count">1 / ${images.length}</div>` : ''}
                    </div>
                    ${images.length > 1 ? `<div class="gallery-thumbs">${images.map((im, i) => `<div class="gallery-thumb ${i === 0 ? 'active' : ''}" data-src="${esc(assetUrl(im.image_path))}">${im.image_path ? `<img src="${esc(assetUrl(im.image_path))}">` : ''}</div>`).join('')}</div>` : ''}
                </div>
                <div class="rd-detail-info">
                    ${p.category_name ? `<span class="rd-detail-cat">${esc(p.category_name)}</span>` : ''}
                    <h1>${esc(p.name)}</h1>
                    <div class="rd-detail-stock-row">
                        <span class="rd-detail-stock ${inStock ? '' : 'out'}">${inStock ? `&#10003; In Stock (${p.quantity})` : 'Out of Stock'}</span>
                    </div>
                    <div class="price">${fmt(p.selling_price)}</div>
                    <p class="rd-detail-desc">${esc(p.description || 'No description provided yet.')}</p>
                    ${variantPickerHtml}
                    <div class="rd-detail-actions">
                        <div class="qty-stepper"><button id="q-dec">−</button><input id="q-val" value="1" readonly><button id="q-inc">+</button></div>
                        <button class="btn-store" id="add-cart-btn">Add to Cart</button>
                    </div>
                    <div class="rd-delivery-box">
                        <div>&#128666; Fast, reliable delivery</div>
                        <div>&#128260; Easy returns within 7 days</div>
                        <div>&#128737; 100% authentic products</div>
                        <div>&#128274; Secure checkout</div>
                    </div>
                </div>
            </div>

            <div class="product-tabs">
                <button class="product-tab-btn active" data-tab="description">Description</button>
                ${specs.length ? '<button class="product-tab-btn" data-tab="specs">Specifications</button>' : ''}
                <button class="product-tab-btn" data-tab="reviews">Reviews <span id="review-count-badge"></span></button>
            </div>
            <div class="product-tab-panel" id="tab-description">
                <p>${esc(p.description || 'No description provided yet.')}</p>
            </div>
            ${specs.length ? `
            <div class="product-tab-panel" id="tab-specs" style="display:none;">
                <table class="spec-table">${specs.map((s) => `<tr><td class="spec-label">${esc(s.label)}</td><td>${esc(s.value)}</td></tr>`).join('')}</table>
            </div>` : ''}
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
            </div>

            <div class="similar-products-section" id="similar-products-section" style="display:none;">
                <div class="section-title-lg">You May Also Like</div>
                <div class="product-grid" id="similar-products-grid"></div>
            </div>`;
    };

    /* Radiance-only enhancement (not part of store.js's shared contract):
       clicking the main product image opens a simple fullscreen lightbox,
       matching the zoom/lightbox behaviour from the original design. Reuses
       the same .gallery-thumb elements store.js already wires up for the
       thumbnail strip, so it stays in sync with whichever image is active. */
    document.addEventListener('click', (e) => {
        const wrap = e.target.closest('#rd-main-wrap');
        if (!wrap) return;
        const root = document.getElementById('product-detail-root');
        if (!root) return;
        const thumbs = [...root.querySelectorAll('.gallery-thumb')];
        const sources = thumbs.length ? thumbs.map((t) => t.dataset.src) : [wrap.querySelector('img')?.src].filter(Boolean);
        if (!sources.length) return;
        const activeIdx = Math.max(0, thumbs.findIndex((t) => t.classList.contains('active')));
        openRdLightbox(sources, activeIdx);
    });

    let rdLbIndex = 0;
    let rdLbSources = [];
    function openRdLightbox(sources, index) {
        rdLbSources = sources;
        rdLbIndex = index;
        let lb = document.getElementById('rd-lightbox');
        if (!lb) {
            lb = document.createElement('div');
            lb.id = 'rd-lightbox';
            lb.className = 'rd-lightbox';
            lb.innerHTML = `
                <div class="rd-lb-close">&times;</div>
                <div class="rd-lb-prev">&#8249;</div>
                <img class="rd-lightbox-img" id="rd-lb-img" src="" alt="">
                <div class="rd-lb-next">&#8250;</div>`;
            document.body.appendChild(lb);
            lb.addEventListener('click', (e) => { if (e.target === lb) closeRdLightbox(); });
            lb.querySelector('.rd-lb-close').addEventListener('click', closeRdLightbox);
            lb.querySelector('.rd-lb-prev').addEventListener('click', () => rdLbNav(-1));
            lb.querySelector('.rd-lb-next').addEventListener('click', () => rdLbNav(1));
        }
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
        updateRdLightbox();
    }
    function closeRdLightbox() {
        const lb = document.getElementById('rd-lightbox');
        if (lb) lb.classList.remove('open');
        document.body.style.overflow = '';
    }
    function rdLbNav(dir) {
        rdLbIndex = (rdLbIndex + dir + rdLbSources.length) % rdLbSources.length;
        updateRdLightbox();
    }
    function updateRdLightbox() {
        const img = document.getElementById('rd-lb-img');
        if (img) img.src = rdLbSources[rdLbIndex];
    }
    document.addEventListener('keydown', (e) => {
        const lb = document.getElementById('rd-lightbox');
        if (!lb || !lb.classList.contains('open')) return;
        if (e.key === 'ArrowRight') rdLbNav(1);
        if (e.key === 'ArrowLeft') rdLbNav(-1);
        if (e.key === 'Escape') closeRdLightbox();
    });
})();
