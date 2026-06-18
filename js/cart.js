/* =========================================
   CART — Add/remove items via AJAX
   ========================================= */

(function () {
  'use strict';

  // ── SHOW TOAST ──
  window.showToast = function(msg, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icons = { success: '✅', error: '❌', info: '🛒' };
    toast.innerHTML = `<span>${icons[type] || '🔔'}</span><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'toastOut 0.35s ease forwards';
      setTimeout(() => toast.remove(), 350);
    }, 2800);
  };

  // ── ADD TO CART ──
  window.addToCart = function (productId, productName, btn) {
    if (btn) {
      btn.disabled = true;
      btn.style.transform = 'scale(0.9)';
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('api/cart.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          window.updateCartCount && window.updateCartCount(data.count);
          showToast(`${productName} added to cart!`, 'success');
        } else {
          showToast('Could not add to cart.', 'error');
        }
      })
      .catch(() => showToast('Network error.', 'error'))
      .finally(() => {
        if (btn) {
          btn.disabled = false;
          btn.style.transform = '';
        }
      });
  };

  // ── DELEGATE: catch all add-to-cart button clicks ──
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.add-to-cart-btn');
    if (!btn) return;
    e.stopPropagation();
    const card = btn.closest('.product-card');
    const pid  = card?.dataset.productId;
    const name = card?.dataset.productName || 'Item';
    if (pid) window.addToCart(pid, name, btn);
  });

})();
