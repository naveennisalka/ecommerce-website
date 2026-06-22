<?php
require_once '../db/connection.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'delivery_man') {
    header('Location: ../login.php'); exit;
}
$user = $_SESSION['user'];
$assignments = []; $notifs = []; $unread = 0;
$pendingCount = $completedCount = 0;

if ($USE_DB) {
    $stmt = $pdo->prepare(
        "SELECT o.*, da.pickup_address, da.drop_address, da.assigned_at,
                u.name AS customer_name,u.phone AS cust_phone,
                s.name AS shop_name,s.address AS shop_address,s.phone AS shop_phone
         FROM orders o
         JOIN delivery_assignments da ON o.id=da.order_id
         JOIN users u ON o.user_id=u.id
         JOIN shops s ON o.shop_id=s.id
         WHERE o.delivery_man_id=? ORDER BY da.assigned_at DESC"
    );
    $stmt->execute([$user['id']]);
    $assignments = $stmt->fetchAll();
    foreach ($assignments as &$a) {
        $it = $pdo->prepare("SELECT oi.*,p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $it->execute([$a['id']]);
        $a['items'] = $it->fetchAll();
    }
    $pendingCount   = count(array_filter($assignments,fn($a)=>in_array($a['status'],['confirmed','preparing','assigned','accepted','picked_up','on_the_way'])));
    $completedCount = count(array_filter($assignments,fn($a)=>$a['status']==='delivered'));

    $ns = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
    $ns->execute([$user['id']]);
    $notifs = $ns->fetchAll();
    $unread = count(array_filter($notifs,fn($n)=>!$n['is_read']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delivery Dashboard — EatLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body">
<div id="toast-container"></div>

<div class="dashboard-wrapper">
<!-- SIDEBAR -->
<aside class="dashboard-sidebar" id="dashboard-sidebar">
  <a href="../index.php" class="sidebar-logo" style="display:flex;align-items:center;padding:16px 24px;">
    <img src="../images/Logo.png" alt="EatLink Logo" style="max-height: 40px; max-width: 100%;">
  </a>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    <div>
      <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <span class="sidebar-user-role">Delivery Man</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-nav-section">
      <a class="sidebar-nav-item" data-tab="active" href="#" onclick="switchTab('active');return false;">
        <span class="sidebar-nav-icon">🛵</span> Active Deliveries
        <?php if ($pendingCount): ?><span class="sidebar-badge"><?= $pendingCount ?></span><?php endif; ?>
      </a>
      <a class="sidebar-nav-item" data-tab="history" href="#" onclick="switchTab('history');return false;">
        <span class="sidebar-nav-icon">📋</span> Delivery History
      </a>
      <a class="sidebar-nav-item" data-tab="notifications" href="#" onclick="switchTab('notifications');return false;">
        <span class="sidebar-nav-icon">🔔</span> Notifications
        <?php if ($unread): ?><span class="sidebar-badge" id="notif-sidebar-badge"><?= $unread ?></span><?php endif; ?>
      </a>
    </div>
  </nav>
  <div class="sidebar-bottom">
    <button class="sidebar-logout logout-btn">🚪 Logout</button>
  </div>
</aside>

<!-- MAIN -->
<div class="dashboard-main">
  <header class="dashboard-topbar">
    <h1 class="topbar-title" id="page-title">Active Deliveries</h1>
  </header>
  <div class="dashboard-content">

    <!-- STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon orange">🛵</div></div>
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Active Deliveries</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon green">✅</div></div>
        <div class="stat-value"><?= $completedCount ?></div>
        <div class="stat-label">Completed</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon blue">📦</div></div>
        <div class="stat-value"><?= count($assignments) ?></div>
        <div class="stat-label">Total Assignments</div>
      </div>
    </div>

    <!-- ══ ACTIVE DELIVERIES ══ -->
    <div id="tab-active" class="tab-panel">
      <?php $activeOrders = array_filter($assignments,fn($a)=>in_array($a['da_status']??$a['status'],['confirmed','preparing','assigned','accepted','picked_up','on_the_way']));
      if (empty($activeOrders)): ?>
      <div class="empty-state"><div class="empty-icon">🛵</div><h3>No active deliveries</h3><p>You're all done! New assignments will appear here.</p></div>
      <?php else: ?>
      <?php foreach ($activeOrders as $a):
          $daStatus = $a['da_status'] ?? $a['status'];
      ?>
      <div class="delivery-card">
        <div class="delivery-card-header">
          <h4>Order #<?= $a['id'] ?> — <?= htmlspecialchars($a['shop_name']) ?></h4>
          <span style="background:rgba(255,255,255,.2);padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:700;">
            <?= ucfirst(str_replace('_',' ',$daStatus)) ?>
          </span>
        </div>
        <div class="delivery-card-body">
          <div class="delivery-info-grid">
            <div class="delivery-info-item">
              <div class="d-label">📍 Pickup Location</div>
              <div class="d-value"><?= htmlspecialchars($a['pickup_address'] ?? $a['shop_address']) ?></div>
              <?php if (!empty($a['shop_phone'])): ?>
              <div style="font-size:.78rem;color:var(--primary);margin-top:4px;">📞 <?= htmlspecialchars($a['shop_phone']) ?></div>
              <?php endif; ?>
            </div>
            <div class="delivery-info-item">
              <div class="d-label">🏠 Drop Location</div>
              <div class="d-value"><?= htmlspecialchars($a['drop_address'] ?? $a['delivery_address']) ?></div>
            </div>
            <div class="delivery-info-item">
              <div class="d-label">👤 Customer</div>
              <div class="d-value"><?= htmlspecialchars($a['customer_name']) ?></div>
              <div style="font-size:.78rem;color:var(--primary);margin-top:4px;">
                <a href="tel:<?= htmlspecialchars($a['cust_phone']??$a['customer_phone']??'') ?>" style="color:var(--primary);text-decoration:none;">
                  📞 <?= htmlspecialchars($a['cust_phone']??$a['customer_phone']??'—') ?>
                </a>
              </div>
            </div>
            <div class="delivery-info-item">
              <div class="d-label">💰 Package Value</div>
              <div class="d-value highlight">Rs. <?= number_format($a['package_price']??$a['total_amount'],2) ?></div>
            </div>
            <?php if (!empty($a['return_address'])): ?>
            <div class="delivery-info-item">
              <div class="d-label">↩️ Return Address</div>
              <div class="d-value"><?= htmlspecialchars($a['return_address']) ?></div>
            </div>
            <?php endif; ?>
            <div class="delivery-info-item">
              <div class="d-label">📦 Items</div>
              <div style="font-size:.8rem;color:var(--text-medium);">
                <?php foreach ($a['items']??[] as $item): ?>
                <?= htmlspecialchars($item['name']) ?> ×<?= $item['quantity'] ?><br>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- ACTION BUTTONS -->
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
            <?php if (in_array($daStatus, ['confirmed', 'preparing', 'assigned'])): ?>
            <button onclick="updateOrderStatus(<?= $a['id'] ?>,'picked_up','Package picked up by delivery man')" class="btn-primary" style="flex:1;">📦 Mark Picked Up</button>
            <?php elseif ($daStatus==='picked_up'): ?>
            <button onclick="openPinModal(<?= $a['id'] ?>)" class="btn-primary" style="flex:1;">✅ Confirm Order</button>
            <?php endif; ?>
            <a href="tel:<?= htmlspecialchars($a['cust_phone']??'') ?>" class="btn-outline" style="flex:0;white-space:nowrap;">📞 Call Customer</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ══ HISTORY ══ -->
    <div id="tab-history" class="tab-panel" style="display:none;">
      <?php $done = array_filter($assignments,fn($a)=>in_array($a['da_status']??$a['status'],['delivered','returned']));
      if (empty($done)): ?>
      <div class="empty-state"><div class="empty-icon">📋</div><h3>No completed deliveries yet</h3></div>
      <?php else: ?>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>Order</th><th>Customer</th><th>Pickup</th><th>Drop</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($done as $a): ?>
          <tr>
            <td><strong>#<?= $a['id'] ?></strong><br><span style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($a['shop_name']) ?></span></td>
            <td><?= htmlspecialchars($a['customer_name']) ?><br><span style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($a['cust_phone']??'') ?></span></td>
            <td style="font-size:.78rem;"><?= htmlspecialchars(substr($a['pickup_address']??$a['shop_address'],0,40)) ?>…</td>
            <td style="font-size:.78rem;"><?= htmlspecialchars(substr($a['drop_address']??$a['delivery_address'],0,40)) ?>…</td>
            <td style="font-weight:700;color:var(--primary);">Rs.<?= number_format($a['package_price']??$a['total_amount'],2) ?></td>
            <td><span class="status-pill delivered">Delivered</span></td>
            <td style="font-size:.75rem;color:var(--text-muted);"><?= date('d M Y',strtotime($a['assigned_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ NOTIFICATIONS ══ -->
    <div id="tab-notifications" class="tab-panel" style="display:none;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h2 style="font-size:1rem;font-weight:700;">Notifications</h2>
        <?php if ($unread): ?><button id="mark-all-read" class="btn-outline" style="font-size:.8rem;padding:6px 14px;">Mark all read</button><?php endif; ?>
      </div>
      <?php if (empty($notifs)): ?>
      <div class="empty-state"><div class="empty-icon">🔔</div><h3>No notifications</h3></div>
      <?php else: ?>
      <div class="notif-list">
        <?php foreach ($notifs as $n): ?>
        <div class="notif-item <?= $n['is_read']?'':'unread' ?>" data-id="<?= $n['id'] ?>">
          <div class="notif-icon delivery">🛵</div>
          <div class="notif-body">
            <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px;"><?= date('d M, H:i',strtotime($n['created_at'])) ?></div>
          </div>
          <?php if (!$n['is_read']): ?><div class="notif-dot-unread"></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>

<!-- PIN ENTRY MODAL -->
<div id="pin-modal" class="modal-overlay">
  <div class="modal-box" style="max-width:350px;text-align:center;">
    <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:8px;">Enter Customer PIN</h2>
    <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:16px;">Ask the customer for their 4-digit delivery PIN to confirm this order.</p>
    <input type="hidden" id="pin-order-id">
    <div style="display:flex;justify-content:center;gap:10px;margin-bottom:20px;">
      <input type="text" id="pin-input" maxlength="4" placeholder="----" style="width:120px;font-size:2rem;text-align:center;letter-spacing:8px;border:2px solid var(--border);border-radius:10px;outline:none;padding:10px;">
    </div>
    <div style="display:flex;gap:10px;">
      <button class="btn-outline" style="flex:1;" onclick="closePinModal()">Cancel</button>
      <button class="btn-primary" style="flex:1;" onclick="confirmDeliveryPin()">Confirm</button>
    </div>
  </div>
</div>

<script src="../js/dashboard.js"></script>
<script src="../js/notifications.js"></script>
<script>
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p=>p.style.display='none');
  document.getElementById('tab-'+name).style.display='';
  document.querySelectorAll('.sidebar-nav-item[data-tab]').forEach(a=>a.classList.remove('active'));
  document.querySelector(`[data-tab="${name}"]`)?.classList.add('active');
  const titles={active:'Active Deliveries',history:'Delivery History',notifications:'Notifications'};
  document.getElementById('page-title').textContent=titles[name]||'';
}
switchTab('active');
</script>
</body>
</html>
