/* =========================================
   NAVBAR — Search expand, cart counter
   ========================================= */

(function () {
  'use strict';

  const searchInput = document.getElementById('nav-search-input');
  const submitBtn   = document.getElementById('nav-search-submit');

  // ── SEARCH SUBMIT ──
  if (submitBtn) {
    submitBtn.addEventListener('click', () => {
      const q = searchInput ? searchInput.value.trim() : '';
      if (q) {
        window.location.href = `menu.php?search=${encodeURIComponent(q)}`;
      }
    });
  }
  if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const q = searchInput.value.trim();
        if (q) window.location.href = `menu.php?search=${encodeURIComponent(q)}`;
      }
    });
  }

  // ── CART COUNT UPDATE ──
  window.updateCartCount = function (count) {
    const cartBadge = document.getElementById('cart-badge');
    if (cartBadge) {
      cartBadge.style.display = count > 0 ? 'block' : 'none';
      
      // Add a little pop animation
      if (count > 0) {
        cartBadge.style.transform = 'scale(1.5)';
        setTimeout(() => cartBadge.style.transform = 'scale(1)', 200);
      }
    }
  };

  // ── LOAD CART COUNT ON INIT ──
  fetch('api/cart.php')
    .then(r => r.json())
    .then(data => {
      if (data.success) window.updateCartCount(data.count);
    })
    .catch(() => {});

})();
