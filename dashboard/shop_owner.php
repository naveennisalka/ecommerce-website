<?php
require_once '../db/connection.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'shop_owner') {
    header('Location: ../login.php'); exit;
}
$user = $_SESSION['user'];

$shop = null; $products = []; $orders = []; $notifs = []; $unread = 0;
$totalRevenue = 0; $pendingCount = 0; $deliveredCount = 0;

if ($USE_DB) {
    $sh = $pdo->prepare("SELECT * FROM shops WHERE owner_id=? LIMIT 1");
    $sh->execute([$user['id']]);
    $shop = $sh->fetch();

    if ($shop) {
        $pr = $pdo->prepare("SELECT p.*,c.name AS cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.shop_id=? AND p.is_active=1 ORDER BY p.created_at DESC");
        $pr->execute([$shop['id']]);
        $products = $pr->fetchAll();

        $or = $pdo->prepare("SELECT o.*,u.name AS customer_name,u.phone AS customer_phone,dm.name AS dm_name FROM orders o JOIN users u ON o.user_id=u.id LEFT JOIN users dm ON o.delivery_man_id=dm.id WHERE o.shop_id=? ORDER BY o.created_at DESC");
        $or->execute([$shop['id']]);
        $orders = $or->fetchAll();
        foreach ($orders as &$ord) {
            $it = $pdo->prepare("SELECT oi.*,p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
            $it->execute([$ord['id']]);
            $ord['items'] = $it->fetchAll();
        }

        $totalRevenue  = array_sum(array_column(array_filter($orders,fn($o)=>$o['status']==='delivered'),'total_amount'));
        $pendingCount  = count(array_filter($orders,fn($o)=>$o['status']==='pending'));
        $deliveredCount= count(array_filter($orders,fn($o)=>$o['status']==='delivered'));
    }
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
  <title>Shop Dashboard — EatLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body">
<div id="toast-container"></div>

<!-- ASSIGN DELIVERY MODAL -->
<div class="modal-overlay" id="assign-modal">
  <div class="modal-overlay-bg" style="position:absolute;inset:0;" onclick="closeAssignModal()"></div>
  <div class="modal-box" style="position:relative;z-index:1;">
    <div class="modal-header">
      <h3>🛵 Assign Delivery Man</h3>
      <button class="modal-close" onclick="closeAssignModal()">✕</button>
    </div>
    <input type="hidden" id="assign-order-id">
    <div id="dm-list"><p style="text-align:center;padding:16px;color:var(--text-muted);">Loading...</p></div>
    <div style="margin-top:16px;display:flex;gap:10px;">
      <button onclick="closeAssignModal()" class="filter-reset-btn" style="flex:1;">Cancel</button>
      <button onclick="confirmAssign()" class="filter-apply-btn" style="flex:2;">Confirm Assignment</button>
    </div>
  </div>
</div>

<div class="dashboard-wrapper">
<!-- SIDEBAR -->
<aside class="dashboard-sidebar" id="dashboard-sidebar">
  <a href="../index.php" class="sidebar-logo" style="display:flex;align-items:center;padding:16px 24px;">
    <img src="../images/Logo.png" alt="EatLink Logo" style="max-height: 40px; max-width: 100%;">
  </a>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    <div>
      <div class="sidebar-user-name"><?= htmlspecialchars($shop['name'] ?? $user['name']) ?></div>
      <span class="sidebar-user-role">Shop Owner</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-nav-section">
      <div class="sidebar-nav-label">Dashboard</div>
      <a class="sidebar-nav-item" data-tab="orders" href="#" onclick="switchTab('orders');return false;">
        <span class="sidebar-nav-icon">📦</span> Incoming Orders
        <?php if ($pendingCount): ?><span class="sidebar-badge"><?= $pendingCount ?></span><?php endif; ?>
      </a>
      <a class="sidebar-nav-item" data-tab="products" href="#" onclick="switchTab('products');return false;">
        <span class="sidebar-nav-icon">🍽️</span> My Products
        <?php if (count($products)): ?><span class="sidebar-badge"><?= count($products) ?></span><?php endif; ?>
      </a>
      <a href="add_product.php" class="sidebar-nav-item">
        <span class="sidebar-nav-icon">➕</span> Add Product
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
    <h1 class="topbar-title" id="page-title">Incoming Orders</h1>
  </header>
  <div class="dashboard-content">

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon orange">📦</div><span class="stat-trend"><?= count($orders) ?> total</span></div>
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon green">✅</div></div>
        <div class="stat-value"><?= $deliveredCount ?></div>
        <div class="stat-label">Delivered</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon blue">🍽️</div></div>
        <div class="stat-value"><?= count($products) ?></div>
        <div class="stat-label">Products Listed</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-top"><div class="stat-icon orange">💰</div></div>
        <div class="stat-value">Rs.<?= number_format($totalRevenue) ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
    </div>

    <!-- ══ ORDERS TAB ══ -->
    <div id="tab-orders" class="tab-panel">
      <div class="data-table-wrap">
        <?php 
        $activeShopOrders = array_filter($orders, fn($o) => !in_array($o['status'], ['delivered','cancelled']));
        $completedShopOrders = array_filter($orders, fn($o) => in_array($o['status'], ['delivered','cancelled']));
        ?>
        
        <div class="data-table-head"><h3>Active Orders</h3></div>
        <?php if (empty($activeShopOrders)): ?>
        <div class="empty-state"><div class="empty-icon">📭</div><h3>No active orders</h3><p>Incoming orders will appear here.</p></div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Delivery Man</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($activeShopOrders as $ord): ?>
          <tr>
            <td><strong>#<?= $ord['id'] ?></strong></td>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($ord['customer_name']) ?></div>
              <div style="font-size:.75rem;color:var(--text-muted);">📞 <?= htmlspecialchars($ord['customer_phone']??'') ?></div>
              <div style="font-size:.72rem;color:var(--text-muted);max-width:160px;"><?= htmlspecialchars($ord['delivery_address']??'') ?></div>
            </td>
            <td>
              <?php foreach ($ord['items'] as $item): ?>
              <div style="font-size:.78rem;"><?= htmlspecialchars($item['name']) ?> ×<?= $item['quantity'] ?></div>
              <?php endforeach; ?>
            </td>
            <td style="font-weight:700;color:var(--primary);">Rs.<?= number_format($ord['total_amount'],2) ?></td>
            <td><span class="status-pill <?= $ord['status'] ?>"><?= ucfirst(str_replace('_',' ',$ord['status'])) ?></span></td>
            <td><?= $ord['dm_name'] ? htmlspecialchars($ord['dm_name']) : '<span style="color:var(--text-muted);font-size:.78rem;">Not assigned</span>' ?></td>
            <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d M, H:i',strtotime($ord['created_at'])) ?></td>
            <td>
              <div style="display:flex;flex-direction:column;gap:4px;">
              <?php if ($ord['status']==='pending'): ?>
                <button onclick="updateOrderStatus(<?= $ord['id'] ?>,'confirmed','Shop confirmed the order')" class="btn-primary" style="font-size:.72rem;padding:5px 10px;">✅ Confirm</button>
              <?php endif; ?>
              <?php if (in_array($ord['status'],['confirmed','preparing']) && !$ord['delivery_man_id']): ?>
                <button onclick="openAssignModal(<?= $ord['id'] ?>)" class="btn-outline" style="font-size:.72rem;padding:5px 10px;">🛵 Assign Rider</button>
              <?php endif; ?>
              <?php if ($ord['status']==='confirmed'): ?>
                <button onclick="updateOrderStatus(<?= $ord['id'] ?>,'preparing','Order is being prepared')" class="btn-primary" style="font-size:.72rem;padding:5px 10px;">👨‍🍳 Preparing</button>
              <?php endif; ?>
              <?php if ($ord['status']==='pending'): ?>
                <button onclick="updateOrderStatus(<?= $ord['id'] ?>,'cancelled','Cancelled by shop')" style="font-size:.72rem;padding:5px 10px;background:#FFF0F0;color:#E8001C;border:1px solid #FADADD;border-radius:6px;cursor:pointer;">✕ Cancel</button>
              <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>

        <div class="data-table-head" style="margin-top:40px;"><h3>Completed Orders</h3></div>
        <?php if (empty($completedShopOrders)): ?>
        <div class="empty-state"><div class="empty-icon">✅</div><h3>No completed orders</h3><p>Delivered and cancelled orders will appear here.</p></div>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Delivery Man</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($completedShopOrders as $ord): ?>
          <tr>
            <td><strong>#<?= $ord['id'] ?></strong></td>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($ord['customer_name']) ?></div>
              <div style="font-size:.75rem;color:var(--text-muted);">📞 <?= htmlspecialchars($ord['customer_phone']??'') ?></div>
            </td>
            <td>
              <?php foreach ($ord['items'] as $item): ?>
              <div style="font-size:.78rem;"><?= htmlspecialchars($item['name']) ?> ×<?= $item['quantity'] ?></div>
              <?php endforeach; ?>
            </td>
            <td style="font-weight:700;color:var(--primary);">Rs.<?= number_format($ord['total_amount'],2) ?></td>
            <td><span class="status-pill <?= $ord['status'] ?>"><?= ucfirst(str_replace('_',' ',$ord['status'])) ?></span></td>
            <td><?= $ord['dm_name'] ? htmlspecialchars($ord['dm_name']) : '<span style="color:var(--text-muted);font-size:.78rem;">-</span>' ?></td>
            <td style="font-size:.78rem;color:var(--text-muted);"><?= date('d M, H:i',strtotime($ord['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- ══ PRODUCTS TAB ══ -->
    <div id="tab-products" class="tab-panel" style="display:none;">
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <a href="add_product.php" class="btn-primary">+ Add New Product</a>
      </div>
      <?php if (empty($products)): ?>
      <div class="empty-state"><div class="empty-icon">🍽️</div><h3>No products listed</h3><p>Add your first product to start receiving orders.</p><a href="add_product.php" class="btn-primary" style="margin-top:16px;display:inline-flex;">+ Add Product</a></div>
      <?php else: ?>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Discount</th><th>Delivery</th><th>Stock</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($products as $prod):
              $img = ($prod['image']??'burger')==='pizza'?'../images/pizza.png':'../images/burger.png';
          ?>
          <tr>
            <td style="display:flex;align-items:center;gap:10px;">
              <img src="<?= $img ?>" class="product-thumb-sm" alt="">
              <div>
                <div style="font-weight:600;"><?= htmlspecialchars($prod['name']) ?></div>
                <?php if ($prod['is_new']): ?><span class="new-label" style="font-size:.65rem;">NEW</span><?php endif; ?>
              </div>
            </td>
            <td><?= htmlspecialchars($prod['cat_name']??'—') ?></td>
            <td style="font-weight:700;color:var(--primary);">Rs.<?= number_format($prod['price'],2) ?></td>
            <td><?= $prod['discount_percent']>0 ? '<span class="discount-label">'.$prod['discount_percent'].'% OFF</span>' : '—' ?></td>
            <td><?= $prod['delivery_type']==='free'?'🟢 Free':'💳 Paid' ?></td>
            <td><?= $prod['stock'] ?></td>
            <td>
              <a href="add_product.php?edit=<?= $prod['id'] ?>" class="btn-outline" style="font-size:.72rem;padding:4px 10px;margin-right:4px;">✏️ Edit</a>
              <button onclick="deleteProduct(<?= $prod['id'] ?>)" style="font-size:.72rem;padding:4px 10px;background:#FFF0F0;color:#E8001C;border:1px solid #FADADD;border-radius:6px;cursor:pointer;">🗑️</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ NOTIFICATIONS TAB ══ -->
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
          <div class="notif-icon <?= $n['type'] ?>"><?= $n['type']==='order'?'📦':'🔔' ?></div>
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

<script src="../js/dashboard.js"></script>
<script src="../js/notifications.js"></script>
<script>
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p=>p.style.display='none');
  document.getElementById('tab-'+name).style.display='';
  document.querySelectorAll('.sidebar-nav-item[data-tab]').forEach(a=>a.classList.remove('active'));
  document.querySelector(`[data-tab="${name}"]`)?.classList.add('active');
  const titles={orders:'Incoming Orders',products:'My Products',notifications:'Notifications'};
  document.getElementById('page-title').textContent=titles[name]||'';
}
switchTab('orders');
</script>
</body>
</html>
