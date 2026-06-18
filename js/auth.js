/* ============================================================
   AUTH — Login & Register page JS
   ============================================================ */

(function () {
  'use strict';

  // ── ROLE TABS ──
  const roleTabs = document.querySelectorAll('.role-tab');
  const roleInput = document.getElementById('role-input');

  roleTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      roleTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      if (roleInput) roleInput.value = tab.dataset.role;
      // Show/hide shop name field
      const shopField = document.getElementById('shop-name-group');
      if (shopField) {
        shopField.style.display = tab.dataset.role === 'shop_owner' ? '' : 'none';
      }
    });
  });

  // ── PASSWORD TOGGLE ──
  document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      if (input.type === 'password') { input.type = 'text'; btn.textContent = '🙈'; }
      else { input.type = 'password'; btn.textContent = '👁️'; }
    });
  });

  // ── FORM SUBMIT ──
  const form    = document.getElementById('auth-form');
  const alert   = document.getElementById('auth-alert');
  const submitBtn = document.getElementById('auth-submit');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const action = form.dataset.action; // 'login' or 'register'
      fd.append('action', action);

      setLoading(true);
      hideAlert();

      try {
        const res  = await fetch('api/auth.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          showAlert(`Welcome, ${data.name}! Redirecting...`, 'success');
          setTimeout(() => {
            const redirectMap = {
              customer:     'dashboard/user.php',
              shop_owner:   'dashboard/shop_owner.php',
              delivery_man: 'dashboard/delivery.php',
            };
            window.location.href = redirectMap[data.role] || 'index.php';
          }, 900);
        } else {
          showAlert(data.message || 'Something went wrong.', 'error');
        }
      } catch {
        showAlert('Network error. Please try again.', 'error');
      } finally {
        setLoading(false);
      }
    });
  }

  function setLoading(on) {
    if (!submitBtn) return;
    submitBtn.disabled = on;
    submitBtn.innerHTML = on
      ? '<div class="btn-spinner"></div> Please wait...'
      : submitBtn.dataset.text;
  }
  function showAlert(msg, type) {
    if (!alert) return;
    alert.textContent = msg;
    alert.className = `auth-alert ${type}`;
  }
  function hideAlert() {
    if (alert) alert.className = 'auth-alert';
  }

  // Store original button text
  if (submitBtn) submitBtn.dataset.text = submitBtn.innerHTML;

})();
