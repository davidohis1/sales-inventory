/* =========================================================================
   Blossom theme — its own product-card and product-detail markup.
   Loaded before store.js's render calls; store.js picks these up via
   window.themeCardRenderer / window.themeDetailRenderer instead of falling
   back to the shared default templates, so Blossom never shares product
   card or product-detail markup with any other theme.
   ========================================================================= */
(function () {
    function isRecent(dateStr, days) {
        if (!dateStr) return false;
        const t = new Date(String(dateStr).replace(' ', 'T')).getTime();
        if (Number.isNaN(t)) return false;
        return (Date.now() - t) < days * 24 * 60 * 60 * 1000;
    }

    window.themeCardRenderer = function (p, u) {
        const badge = isRecent(p.created_at, 14) ? '<span class="bl-card-badge">New</span>' : '';
        return `
        <a class="bl-card" href="${u.appBase}/${u.slug}/product/${p.id}" data-id="${p.id}">
            <div class="bl-card-thumb">
                ${u.imageTag(p)}
                ${badge}
                <button class="bl-card-wish" data-wish="${p.id}" title="Wishlist" onclick="event.preventDefault()">&#9825;</button>
            </div>
            <div class="bl-card-body">
                ${p.category_name ? `<div class="bl-card-cat">${u.esc(p.category_name)}</div>` : ''}
                <div class="bl-card-name">${u.esc(p.name)}</div>
                <div class="bl-card-price">${u.fmt(p.selling_price)}</div>
                <button class="bl-card-add" data-quickadd="${p.id}" onclick="event.preventDefault()">Add to Cart</button>
            </div>
        </a>`;
    };

    window.themeDetailRenderer = function (p, ctx) {
        const { images, variantPickerHtml, specs, fmt, esc, assetUrl } = ctx;
        const isNew = isRecent(p.created_at, 14);
        return `
            <div class="bl-detail">
                <div class="bl-detail-gallery">
                    <div class="gallery-main">
                        ${isNew ? '<span class="bl-detail-badge">New</span>' : ''}
                        ${images[0].image_path ? `<img src="${esc(assetUrl(images[0].image_path))}">` : '<span class="no-image">No image available</span>'}
                    </div>
                    ${images.length > 1 ? `<div class="gallery-thumbs">${images.map((im, i) => `<div class="gallery-thumb ${i === 0 ? 'active' : ''}" data-src="${esc(assetUrl(im.image_path))}">${im.image_path ? `<img src="${esc(assetUrl(im.image_path))}">` : ''}</div>`).join('')}</div>` : ''}
                </div>
                <div class="bl-detail-info">
                    ${p.category_name ? `<span class="bl-detail-cat">${esc(p.category_name)}</span>` : ''}
                    <h1>${esc(p.name)}</h1>
                    <div class="price">${fmt(p.selling_price)}</div>
                    <p class="bl-detail-desc">${esc(p.description || 'No description provided yet.')}</p>
                    <p class="bl-detail-stock text-muted">${p.quantity} in stock</p>
                    ${variantPickerHtml}
                    <div class="bl-detail-actions">
                        <div class="qty-stepper"><button id="q-dec">−</button><input id="q-val" value="1" readonly><button id="q-inc">+</button></div>
                        <button class="btn-store" id="add-cart-btn">Add to Cart</button>
                        <button class="bl-detail-wish" title="Wishlist">&#9825;</button>
                    </div>
                    <div class="bl-detail-meta">SKU: ${esc(p.sku || '—')}</div>
                </div>
            </div>

            <div class="product-tabs bl-detail-tabs">
                <button class="product-tab-btn active" data-tab="description">Description</button>
                ${specs.length ? '<button class="product-tab-btn" data-tab="specs">Additional Information</button>' : ''}
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
})();
