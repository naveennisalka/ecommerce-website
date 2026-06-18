/* ============================================================
   DASHBOARD — Tabs, Order management, Modals
   ============================================================ */

(function () {
  'use strict';

  // ── SIDEBAR TOGGLE (mobile) ──
  const sidebar   = document.getElementById('dashboard-sidebar');
  const toggleBtn = document.getElementById('sidebar-toggle');
  toggleBtn && toggleBtn.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
      sidebar.classList.remove('open');
    }
  });

  // ── TAB SWITCHING ──
  const tabBtns    = document.querySelectorAll('[data-tab]');
  const tabPanels  = document.querySelectorAll('[data-panel]');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      tabBtns.forEach(b  => b.classList.remove('active'));
      tabPanels.forEach(p => p.style.display = 'none');
      btn.classList.add('active');
      const panel = document.querySelector(`[data-panel="${target}"]`);
      if (panel) panel.style.display = '';
    });
  });
  // Activate first tab
  if (tabBtns[0]) tabBtns[0].click();

  // ── LOGOUT ──
  document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('Are you sure you want to logout?')) return;
      const fd = new FormData();
      fd.append('action', 'logout');
      await fetch('../api/auth.php', { method: 'POST', body: fd });
      window.location.href = '../login.php';
    });
  });

  // ── ORDER STATUS UPDATE (shop owner / delivery man) ──
  window.updateOrderStatus = async function (orderId, status, note = '') {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('order_id', orderId);
    fd.append('status', status);
    fd.append('note', note);
    const res  = await fetch('../api/orders.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message, 'error');
    }
  };

  // ── ASSIGN DELIVERY MODAL ──
  const modal = document.getElementById('assign-modal');

  window.openAssignModal = function (orderId) {
    if (!modal) return;
    document.getElementById('assign-order-id').value = orderId;
    modal.classList.add('open');
    loadDeliveryMen();
  };
  window.closeAssignModal = function () {
    modal?.classList.remove('open');
  };
  modal && modal.querySelector('.modal-overlay-bg')?.addEventListener('click', closeAssignModal);

  let selectedDM = null;
  function loadDeliveryMen() {
    const list = document.getElementById('dm-list');
    if (!list) return;
    list.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:16px;">Loading...</p>';
    fetch('../api/shop.php?action=delivery_men')
      .then(r => r.json())
      .then(data => {
        if (!data.delivery_men?.length) {
          list.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:16px;">No delivery men available.</p>';
          return;
        }
        list.innerHTML = data.delivery_men.map(dm => `
          <div class="delivery-man-option" data-id="${dm.id}" onclick="selectDM(this,${dm.id})">
            <div class="dm-avatar">${dm.name.charAt(0)}</div>
            <div>
              <div class="dm-name">${dm.name}</div>
              <div class="dm-phone">📞 ${dm.phone}</div>
            </div>
          </div>`).join('');
      });
  }
  window.selectDM = function (el, id) {
    document.querySelectorAll('.delivery-man-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedDM = id;
  };
  window.confirmAssign = async function () {
    const orderId = document.getElementById('assign-order-id')?.value;
    if (!selectedDM) { showToast('Please select a delivery man.', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'assign_delivery');
    fd.append('order_id', orderId);
    fd.append('delivery_man_id', selectedDM);
    const res  = await fetch('../api/orders.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      closeAssignModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message, 'error');
    }
  };

  // ── TOAST ──
  window.showToast = function (msg, type = 'info') {
    let c = document.getElementById('toast-container');
    if (!c) { c = document.createElement('div'); c.id = 'toast-container'; document.body.appendChild(c); }
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    const icons = { success:'✅', error:'❌', info:'ℹ️' };
    t.innerHTML = `<span>${icons[type]||'🔔'}</span><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.animation='toastOut .35s ease forwards'; setTimeout(()=>t.remove(),350); }, 2800);
  };

  // ── DELETE PRODUCT ──
  window.deleteProduct = async function (pid) {
    if (!confirm('Remove this product?')) return;
    const fd = new FormData();
    fd.append('action','delete_product');
    fd.append('product_id', pid);
    const res  = await fetch('../api/shop.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.success) { showToast(data.message,'success'); setTimeout(()=>location.reload(),800); }
    else showToast(data.message,'error');
  };

  // ── SWIPE TO DELIVER & PIN MODAL ──
  const swipeContainers = document.querySelectorAll('.swipe-container');
  swipeContainers.forEach(container => {
    const thumb = container.querySelector('.swipe-thumb');
    let isDragging = false;
    let startX = 0;
    
    function startDrag(e) {
      isDragging = true;
      startX = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
      thumb.style.transition = 'none';
    }
    
    function drag(e) {
      if (!isDragging) return;
      const x = e.type.includes('mouse') ? e.pageX : e.touches[0].pageX;
      let walk = x - startX;
      if (walk < 0) walk = 0;
      const maxWalk = container.offsetWidth - thumb.offsetWidth - 8; // 8px padding
      if (walk > maxWalk) walk = maxWalk;
      thumb.style.transform = `translateX(${walk}px)`;
      
      if (walk >= maxWalk) {
        isDragging = false;
        const orderId = container.dataset.orderId;
        window.openPinModal(orderId);
        thumb.style.transform = 'translateX(0px)';
      }
    }
    
    function stopDrag() {
      if (!isDragging) return;
      isDragging = false;
      thumb.style.transition = 'transform 0.3s ease';
      thumb.style.transform = 'translateX(0px)';
    }
    
    thumb.addEventListener('mousedown', startDrag);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', stopDrag);
    
    thumb.addEventListener('touchstart', startDrag, {passive: true});
    document.addEventListener('touchmove', drag, {passive: true});
    document.addEventListener('touchend', stopDrag);
  });

  const pinModal = document.getElementById('pin-modal');
  window.openPinModal = function(orderId) {
    if (!pinModal) return;
    document.getElementById('pin-order-id').value = orderId;
    document.getElementById('pin-input').value = '';
    pinModal.classList.add('open');
    setTimeout(() => document.getElementById('pin-input').focus(), 100);
  };
  window.closePinModal = function() {
    pinModal?.classList.remove('open');
  };

  window.confirmDeliveryPin = async function() {
    const orderId = document.getElementById('pin-order-id').value;
    const pin = document.getElementById('pin-input').value.trim();
    if (pin.length !== 4) {
      window.showToast('Please enter a 4-digit PIN.', 'error');
      return;
    }
    
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('order_id', orderId);
    fd.append('status', 'delivered');
    fd.append('note', 'Successfully delivered');
    fd.append('pin', pin);
    
    const res = await fetch('../api/orders.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      window.showToast('Delivery Confirmed!', 'success');
      window.closePinModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      window.showToast(data.message, 'error');
      document.getElementById('pin-input').value = '';
      document.getElementById('pin-input').focus();
    }
  };

})();
