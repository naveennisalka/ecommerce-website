/* ============================================================
   NOTIFICATIONS — Poll for unread count, render notification bell
   ============================================================ */

(function () {
  'use strict';

  const POLL_INTERVAL = 30000; // 30 seconds

  function updateBadge(count) {
    document.querySelectorAll('.notif-badge-count').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? '' : 'none';
    });
    document.querySelectorAll('.notif-dot').forEach(el => {
      el.style.display = count > 0 ? '' : 'none';
    });
    // Sidebar badge
    const sb = document.getElementById('notif-sidebar-badge');
    if (sb) { sb.textContent = count; sb.style.display = count > 0 ? '' : 'none'; }
  }

  function pollUnread() {
    fetch('api/notifications.php?action=unread_count')
      .then(r => r.json())
      .then(d => { if (d.success) updateBadge(d.count); })
      .catch(() => {});
  }

  // Initial poll
  pollUnread();
  setInterval(pollUnread, POLL_INTERVAL);

  // ── MARK ALL READ ──
  document.getElementById('mark-all-read')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'mark_read');
    await fetch('api/notifications.php', { method: 'POST', body: fd });
    updateBadge(0);
    document.querySelectorAll('.notif-item.unread').forEach(n => n.classList.remove('unread'));
  });

  // ── MARK SINGLE READ ──
  document.querySelectorAll('.notif-item[data-id]').forEach(item => {
    item.addEventListener('click', () => {
      if (!item.classList.contains('unread')) return;
      const fd = new FormData();
      fd.append('action', 'mark_read');
      fd.append('id', item.dataset.id);
      fetch('api/notifications.php', { method: 'POST', body: fd });
      item.classList.remove('unread');
    });
  });

})();
