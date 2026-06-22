/* =========================================
   WISHLIST — Heart toggle via AJAX
   ========================================= */

(function () {
  'use strict';

  const apiBase = window.location.pathname.includes('/dashboard/') ? '../api/' : 'api/';

  // Track locally in a Set
  window._wishlistIds = new Set();

  // ── TOGGLE WISHLIST ──
  window.toggleWishlist = function (productId, btn) {
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('product_id', productId);

    fetch(apiBase + 'wishlist.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (data.wishlisted) {
            btn.classList.add('wishlisted');
            window._wishlistIds.add(String(productId));
            window.showToast && showToast('Added to wishlist ❤️', 'info');
          } else {
            btn.classList.remove('wishlisted');
            window._wishlistIds.delete(String(productId));
            window.showToast && showToast('Removed from wishlist', 'info');
          }
        }
      })
      .catch(() => {});
  };

  // ── DELEGATE: catch all heart button clicks ──
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.product-heart');
    if (!btn) return;
    e.stopPropagation();
    const card = btn.closest('.product-card');
    const pid  = card?.dataset.productId;
    if (pid) window.toggleWishlist(pid, btn);
  });

  // ── LOAD INITIAL WISHLIST STATE ──
  fetch(apiBase + 'wishlist.php')
    .then(r => r.json())
    .then(data => {
      if (data.success && data.ids) {
        window._wishlistIds = new Set(data.ids.map(String));
        // Mark any currently rendered hearts
        document.querySelectorAll('.product-heart[data-product-id]').forEach(btn => {
          if (window._wishlistIds.has(String(btn.dataset.productId))) {
            btn.classList.add('wishlisted');
          }
        });
      }
    })
    .catch(() => {});

})();
