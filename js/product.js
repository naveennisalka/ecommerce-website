/* ============================================================
   PRODUCT DETAIL — Gallery, Qty, Reviews, Add to Cart
   ============================================================ */

(function () {
  'use strict';

  // ── IMAGE GALLERY ──
  const mainImg  = document.getElementById('gallery-main-img');
  const thumbs   = document.querySelectorAll('.gallery-thumb');

  thumbs.forEach((thumb, i) => {
    thumb.addEventListener('click', () => {
      const src = thumb.querySelector('img')?.src;
      if (mainImg && src) {
        mainImg.style.opacity = '0';
        setTimeout(() => { mainImg.src = src; mainImg.style.opacity = '1'; }, 200);
      }
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });
  // Set first thumb active
  thumbs[0]?.classList.add('active');

  // ── QUANTITY SELECTOR ──
  const qtyDisplay = document.getElementById('qty-display');
  const qtyMinus   = document.getElementById('qty-minus');
  const qtyPlus    = document.getElementById('qty-plus');
  let qty = 1;

  qtyMinus && qtyMinus.addEventListener('click', () => {
    if (qty > 1) { qty--; updateQty(); }
  });
  qtyPlus && qtyPlus.addEventListener('click', () => {
    qty++;
    updateQty();
  });
  function updateQty() {
    if (qtyDisplay) qtyDisplay.textContent = String(qty).padStart(2, '0');
  }

  // ── ADD TO CART ──
  const addCartBtn = document.getElementById('product-add-cart');
  const buyNowBtn  = document.getElementById('product-buy-now');
  const productId  = document.getElementById('product-id')?.value;
  const productName= document.getElementById('product-name-val')?.value || 'Item';

  addCartBtn && addCartBtn.addEventListener('click', () => {
    if (!productId) return;
    addCartBtn.disabled = true;
    addCartBtn.innerHTML = '<div class="btn-spinner" style="border-color:rgba(0,0,0,.2);border-top-color:#333"></div> Adding...';
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('product_id', productId);
    fd.append('quantity', qty);
    fetch('api/cart.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          window.updateCartCount && window.updateCartCount(d.count);
          window.showToast && window.showToast(`${productName} added to cart!`, 'success');
        }
      })
      .finally(() => {
        addCartBtn.disabled = false;
        addCartBtn.innerHTML = '<span class="material-symbols-outlined">shopping_cart</span> Add to Cart';
      });
  });

  buyNowBtn && buyNowBtn.addEventListener('click', () => {
    if (!productId) return;
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('product_id', productId);
    fd.append('quantity', qty);
    fetch('api/cart.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(() => { window.location.href = 'cart.php'; });
  });

  // ── WISHLIST ──
  const wishBtn = document.getElementById('product-wishlist-btn');
  wishBtn && wishBtn.addEventListener('click', () => {
    if (!productId) return;
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('product_id', productId);
    fetch('api/wishlist.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          wishBtn.classList.toggle('wishlisted', d.wishlisted);
          window.showToast && window.showToast(d.wishlisted ? 'Added to wishlist <span class="material-symbols-outlined">favorite</span>' : 'Removed from wishlist', 'info');
        }
      });
  });

  // ── LOAD REVIEWS ──
  const reviewsContainer = document.getElementById('reviews-list');
  const avgRatingEl      = document.getElementById('avg-rating');
  const totalRatingEl    = document.getElementById('total-ratings');
  const starsRowEl       = document.getElementById('stars-row');

  function renderStars(rating, size = '1.1rem') {
    let html = '';
    for (let i = 1; i <= 5; i++) {
      if (i <= Math.floor(rating))     html += `<span class="star filled" style="font-size:${size}">★</span>`;
      else if (i - 0.5 <= rating)     html += `<span class="star half"   style="font-size:${size}">★</span>`;
      else                            html += `<span class="star"        style="font-size:${size}">☆</span>`;
    }
    return html;
  }

  function timeAgo(dateStr) {
    const d = new Date(dateStr);
    const diff = (Date.now() - d) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff/60)+'m ago';
    if (diff < 86400) return Math.floor(diff/3600)+'h ago';
    return Math.floor(diff/86400)+'d ago';
  }

  if (reviewsContainer && productId) {
    fetch(`api/reviews.php?product_id=${productId}`)
      .then(r => r.json())
      .then(data => {
        if (avgRatingEl) avgRatingEl.textContent = data.avg_rating.toFixed(1);
        if (totalRatingEl) totalRatingEl.textContent = data.total + ' Ratings';
        if (starsRowEl) starsRowEl.innerHTML = renderStars(data.avg_rating, '1.4rem');

        if (data.reviews.length === 0) {
          reviewsContainer.innerHTML = '<p style="color:var(--text-muted);font-size:.875rem;padding:16px 0;">No reviews yet. Be the first!</p>';
          return;
        }
        reviewsContainer.innerHTML = data.reviews.map(r => `
          <div class="review-card">
            <div class="review-card-header">
              <div class="reviewer-info">
                <div class="reviewer-avatar">${r.reviewer_name?.charAt(0) || '?'}</div>
                <div>
                  <div class="reviewer-name">${escHtml(r.reviewer_name || 'Anonymous')}</div>
                  <div class="reviewer-date">${timeAgo(r.created_at)}</div>
                </div>
              </div>
              <div class="review-rating">${renderStars(r.rating,'0.9rem')}</div>
            </div>
            <p class="review-text">${escHtml(r.comment || '')}</p>
          </div>`).join('');
      });
  }

  // ── SUBMIT REVIEW ──
  const starPicker  = document.getElementById('star-picker');
  const reviewForm  = document.getElementById('review-form');
  let selectedRating = 0;

  starPicker && starPicker.querySelectorAll('.star-pick').forEach((s, i) => {
    s.addEventListener('click', () => {
      selectedRating = i + 1;
      starPicker.querySelectorAll('.star-pick').forEach((sp, j) => {
        sp.classList.toggle('selected', j < selectedRating);
      });
    });
  });

  reviewForm && reviewForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (selectedRating === 0) { alert('Please select a rating.'); return; }
    const fd = new FormData(reviewForm);
    fd.append('action', 'submit');
    fd.append('product_id', productId);
    fd.append('rating', selectedRating);
    const res  = await fetch('api/reviews.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      window.showToast && window.showToast('Review submitted! Thank you.', 'success');
      reviewForm.reset();
      selectedRating = 0;
      starPicker?.querySelectorAll('.star-pick').forEach(s => s.classList.remove('selected'));
      // Reload reviews
      setTimeout(() => location.reload(), 1200);
    } else {
      window.showToast && window.showToast(data.message || 'Could not submit review.', 'error');
    }
  });

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

})();
