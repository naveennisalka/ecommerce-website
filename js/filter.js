/* =========================================
   FILTER — Popup, tags, range slider, fetch
   ========================================= */

(function () {
  'use strict';

  // ── ELEMENTS ──
  const filterToggleBtn = document.querySelector('.filter-toggle-btn');
  const filterPopup     = document.querySelector('.filter-popup');
  const filterOverlay   = document.querySelector('.filter-overlay');
  const filterCloseBtn  = document.querySelector('.filter-close-btn');
  const filterApplyBtn  = document.querySelector('.filter-apply-btn');
  const filterResetBtn  = document.querySelector('.filter-reset-btn');
  const filterTagsWrap  = document.querySelector('.filter-tags');
  const clearAllBtn     = document.querySelector('.clear-all-btn');
  const productsGrid    = document.getElementById('menu-products-grid');
  const resultsInfo     = document.getElementById('results-info');

  // State
  const filters = {
    brands:   [],
    delivery: '',
    priceMin: 0,
    priceMax: 10000,
    sort:     'default',
    categories: [],
  };

  // Current page
  let currentPage = 1;

  // ── OPEN/CLOSE POPUP ──
  function openFilter() {
    filterPopup && filterPopup.classList.add('open');
    filterOverlay && filterOverlay.classList.add('active');
    filterToggleBtn && filterToggleBtn.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeFilter() {
    filterPopup && filterPopup.classList.remove('open');
    filterOverlay && filterOverlay.classList.remove('active');
    filterToggleBtn && filterToggleBtn.classList.remove('active');
    document.body.style.overflow = '';
  }

  filterToggleBtn && filterToggleBtn.addEventListener('click', openFilter);
  filterCloseBtn  && filterCloseBtn.addEventListener('click', closeFilter);
  filterOverlay   && filterOverlay.addEventListener('click', closeFilter);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFilter(); });

  // ── PRICE RANGE SLIDER ──
  const rangeMin   = document.getElementById('price-min');
  const rangeMax   = document.getElementById('price-max');
  const fillEl     = document.getElementById('range-fill');
  const minValEl   = document.getElementById('price-val-min');
  const maxValEl   = document.getElementById('price-val-max');

  function updateRange() {
    if (!rangeMin || !rangeMax) return;
    let lo = parseInt(rangeMin.value);
    let hi = parseInt(rangeMax.value);
    if (lo > hi) { let t = lo; lo = hi; hi = t; }
    const pMin = (lo / parseInt(rangeMin.max)) * 100;
    const pMax = (hi / parseInt(rangeMax.max)) * 100;
    if (fillEl) {
      fillEl.style.left  = pMin + '%';
      fillEl.style.right = (100 - pMax) + '%';
    }
    if (minValEl) minValEl.textContent = 'Rs. ' + lo.toLocaleString();
    if (maxValEl) maxValEl.textContent = 'Rs. ' + hi.toLocaleString();
    filters.priceMin = lo;
    filters.priceMax = hi;
  }
  rangeMin && rangeMin.addEventListener('input', updateRange);
  rangeMax && rangeMax.addEventListener('input', updateRange);
  updateRange();

  // ── BRAND CHECKBOXES ──
  document.querySelectorAll('.brand-filter-cb').forEach(cb => {
    cb.addEventListener('change', function () {
      const item = this.closest('.brand-filter-item');
      if (this.checked) {
        filters.brands.push(this.value);
        item && item.classList.add('selected');
      } else {
        filters.brands = filters.brands.filter(v => v !== this.value);
        item && item.classList.remove('selected');
      }
    });
  });

  // ── DELIVERY CHIPS ──
  document.querySelectorAll('.delivery-chip').forEach(chip => {
    chip.addEventListener('click', function () {
      const val = this.dataset.value;
      if (filters.delivery === val) {
        filters.delivery = '';
        this.classList.remove('selected');
      } else {
        filters.delivery = val;
        document.querySelectorAll('.delivery-chip').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
      }
    });
  });

  // ── SORT OPTIONS ──
  document.querySelectorAll('.sort-option').forEach(opt => {
    opt.addEventListener('click', function () {
      document.querySelectorAll('.sort-option').forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');
      filters.sort = this.dataset.value;
    });
  });

  // ── CATEGORY CHECKBOXES ──
  document.querySelectorAll('.category-filter-cb').forEach(cb => {
    cb.addEventListener('change', function () {
      const item = this.closest('.category-filter-item');
      if (this.checked) {
        filters.categories.push(this.value);
        item && item.classList.add('selected');
      } else {
        filters.categories = filters.categories.filter(v => v !== this.value);
        item && item.classList.remove('selected');
      }
    });
  });

  // ── BUILD FILTER TAGS ──
  const BRAND_NAMES   = {};
  const CAT_NAMES     = {};
  document.querySelectorAll('.brand-filter-item').forEach(el => {
    const cb = el.querySelector('input');
    const name = el.querySelector('.brand-filter-name');
    if (cb && name) BRAND_NAMES[cb.value] = name.textContent.trim();
  });
  document.querySelectorAll('.category-filter-item').forEach(el => {
    const cb = el.querySelector('input');
    const name = el.querySelector('.cat-name');
    const em = el.querySelector('.cat-emoji');
    if (cb && name) CAT_NAMES[cb.value] = (em ? em.textContent : '') + ' ' + name.textContent.trim();
  });

  function buildTags() {
    if (!filterTagsWrap) return;
    filterTagsWrap.innerHTML = '';
    let hasTags = false;

    function makeTag(label, icon, removeFn) {
      hasTags = true;
      const tag = document.createElement('div');
      tag.className = 'filter-tag';
      tag.innerHTML = `
        <span class="filter-tag-icon">${icon}</span>
        <span>${label}</span>
        <button class="filter-tag-remove" title="Remove">✕</button>
      `;
      tag.querySelector('.filter-tag-remove').addEventListener('click', removeFn);
      filterTagsWrap.appendChild(tag);
    }

    filters.brands.forEach(bid => {
      makeTag(BRAND_NAMES[bid] || 'Brand', '🏪', () => {
        filters.brands = filters.brands.filter(b => b !== bid);
        const cb = document.querySelector(`.brand-filter-cb[value="${bid}"]`);
        if (cb) { cb.checked = false; cb.closest('.brand-filter-item')?.classList.remove('selected'); }
        buildTags(); applyFilters();
      });
    });

    if (filters.delivery) {
      const label = filters.delivery === 'free' ? 'Free Delivery' : 'Paid Delivery';
      makeTag(label, '🚴', () => {
        filters.delivery = '';
        document.querySelectorAll('.delivery-chip').forEach(c => c.classList.remove('selected'));
        buildTags(); applyFilters();
      });
    }

    if (filters.priceMin > 0 || filters.priceMax < 10000) {
      makeTag(`Rs.${filters.priceMin.toLocaleString()} – Rs.${filters.priceMax.toLocaleString()}`, '💰', () => {
        filters.priceMin = 0; filters.priceMax = 10000;
        if (rangeMin) rangeMin.value = 0;
        if (rangeMax) rangeMax.value = 10000;
        updateRange();
        buildTags(); applyFilters();
      });
    }

    if (filters.sort && filters.sort !== 'default') {
      const sortLabels = { price_asc: 'Price: Low → High', price_desc: 'Price: High → Low', newest: 'Newest', discount: 'Most Discount' };
      makeTag(sortLabels[filters.sort] || filters.sort, '↕️', () => {
        filters.sort = 'default';
        document.querySelectorAll('.sort-option').forEach(o => o.classList.remove('selected'));
        buildTags(); applyFilters();
      });
    }

    filters.categories.forEach(cid => {
      makeTag(CAT_NAMES[cid] || 'Category', '', () => {
        filters.categories = filters.categories.filter(c => c !== cid);
        const cb = document.querySelector(`.category-filter-cb[value="${cid}"]`);
        if (cb) { cb.checked = false; cb.closest('.category-filter-item')?.classList.remove('selected'); }
        buildTags(); applyFilters();
      });
    });

    if (clearAllBtn) clearAllBtn.style.display = hasTags ? '' : 'none';
  }

  // ── RESET ALL FILTERS ──
  function resetFilters() {
    filters.brands = [];
    filters.delivery = '';
    filters.priceMin = 0;
    filters.priceMax = 10000;
    filters.sort = 'default';
    filters.categories = [];

    document.querySelectorAll('.brand-filter-cb').forEach(cb => {
      cb.checked = false;
      cb.closest('.brand-filter-item')?.classList.remove('selected');
    });
    document.querySelectorAll('.delivery-chip').forEach(c => c.classList.remove('selected'));
    document.querySelectorAll('.sort-option').forEach(o => o.classList.remove('selected'));
    document.querySelectorAll('.category-filter-cb').forEach(cb => {
      cb.checked = false;
      cb.closest('.category-filter-item')?.classList.remove('selected');
    });
    if (rangeMin) rangeMin.value = 0;
    if (rangeMax) rangeMax.value = 10000;
    updateRange();
    buildTags();
    applyFilters();
  }

  filterResetBtn && filterResetBtn.addEventListener('click', resetFilters);
  clearAllBtn    && clearAllBtn.addEventListener('click', () => { resetFilters(); });

  // ── APPLY FILTERS ──
  function applyFilters(page = 1) {
    currentPage = page;
    closeFilter();
    buildTags();
    fetchProducts();
  }

  filterApplyBtn && filterApplyBtn.addEventListener('click', () => applyFilters(1));

  // ── FETCH PRODUCTS ──
  function fetchProducts(page) {
    if (!productsGrid) return;
    if (page !== undefined) currentPage = page;

    productsGrid.innerHTML = `<div style="grid-column:1/-1;display:flex;justify-content:center;padding:64px">
      <div class="spinner"></div></div>`;

    const params = new URLSearchParams();
    if (filters.brands.length === 1) params.set('brand', filters.brands[0]);
    if (filters.categories.length === 1) params.set('category', filters.categories[0]);
    if (filters.priceMin > 0)    params.set('price_min', filters.priceMin);
    if (filters.priceMax < 10000) params.set('price_max', filters.priceMax);
    if (filters.delivery)        params.set('delivery', filters.delivery);
    if (filters.sort !== 'default') params.set('sort', filters.sort);
    params.set('page', currentPage);
    params.set('per_page', 16);

    // Include URL search param
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('search')) params.set('search', urlParams.get('search'));

    fetch('api/products.php?' + params.toString())
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        renderProducts(data.products, data.total, data.total_pages, data.wishlist || []);
      })
      .catch(() => {
        productsGrid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:48px;color:#999">
          Error loading products. Please try again.</div>`;
      });
  }

  // ── RENDER PRODUCTS ──
  function renderProducts(products, total, totalPages, wishlistIds) {
    if (!productsGrid) return;

    if (resultsInfo) {
      resultsInfo.innerHTML = `Showing <span>${products.length}</span> of <span>${total}</span> results`;
    }

    if (products.length === 0) {
      productsGrid.innerHTML = `
        <div style="grid-column:1/-1" class="empty-state">
          <div class="empty-icon">🍽️</div>
          <h3>No items found</h3>
          <p>Try adjusting your filters</p>
        </div>`;
      return;
    }

    productsGrid.innerHTML = products.map(p => buildCard(p, wishlistIds)).join('');

    // Restore wishlist states
    productsGrid.querySelectorAll('.product-heart[data-product-id]').forEach(btn => {
      if (wishlistIds.includes(parseInt(btn.dataset.productId))) {
        btn.classList.add('wishlisted');
      }
    });

    // Pagination
    const paginWrap = document.querySelector('.pagination-wrap');
    if (paginWrap) {
      if (currentPage < totalPages) {
        paginWrap.innerHTML = `
          <button class="next-page-btn" id="next-page-btn">
            NEXT PAGE
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="9 18 15 12 9 6"/>
            </svg>
          </button>`;
        document.getElementById('next-page-btn')?.addEventListener('click', () => { fetchProducts(currentPage + 1); });
      } else {
        paginWrap.innerHTML = '';
      }
    }
  }

  function buildCard(p, wishlistIds) {
    const wishlisted  = wishlistIds.includes(p.id) ? 'wishlisted' : '';
    const imgSrc      = getProductImage(p.image);
    const discountLbl = p.discount_percent > 0
      ? `<div class="product-label"><span class="discount-label">${p.discount_percent}% OFF</span></div>`
      : (p.is_new ? `<div class="product-label"><span class="new-label">NEW</span></div>` : '');
    const origPrice   = p.original_price
      ? `<span class="price-original">Rs. ${parseInt(p.original_price).toLocaleString()}</span>` : '';

    return `
      <div class="product-card" data-product-id="${p.id}" data-product-name="${escHtml(p.name)}"
           onclick="if(!event.target.closest('.product-heart, .add-to-cart-btn')) window.location='product.php?id=${p.id}'" style="cursor:pointer;">
        <div class="product-card-image-wrap">
          ${discountLbl}
          <button class="product-heart ${wishlisted}" data-product-id="${p.id}" title="Wishlist" aria-label="Add to wishlist">
            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <img src="${imgSrc}" alt="${escHtml(p.name)}" loading="lazy">
          <button class="add-to-cart-btn" title="Add to cart" aria-label="Add to cart">
            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          </button>
        </div>
        <div class="product-card-info">
          <div class="product-card-name">${escHtml(p.name)}</div>
          <div class="price-brand">${escHtml(p.brand_name || '')}</div>
          <div class="product-card-price">
            <span class="price-current">Rs. ${parseInt(p.price).toLocaleString()}</span>
            ${origPrice}
          </div>
        </div>
      </div>`;
  }

  function getProductImage(img) {
    const map = {
      burger: 'images/burger.png',
      pizza:  'images/pizza.png',
    };
    return map[img] || 'images/burger.png';
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  // ── INIT: load initial products ──
  if (productsGrid) {
    // Read URL params on load
    const urlP = new URLSearchParams(window.location.search);
    if (urlP.get('brand'))    { filters.brands = [urlP.get('brand')]; }
    if (urlP.get('category')) { filters.categories = [urlP.get('category')]; }
    fetchProducts();
  }

  // ── EXPOSE GLOBALLY ──
  window.applyFilters   = applyFilters;
  window.fetchProducts  = fetchProducts;

})();
