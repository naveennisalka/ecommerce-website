<?php
require_once '../db/connection.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header('Location: ../login.php'); exit;
}
$user = $_SESSION['user'];

// Load data
$orders = $wishlistItems = $cartItems = [];
$cartTotal = 0;
if ($USE_DB) {
    $oStmt = $pdo->prepare(
        "SELECT o.*,s.name AS shop_name FROM orders o JOIN shops s ON o.shop_id=s.id
         WHERE o.user_id=? ORDER BY o.created_at DESC"
    );
    $oStmt->execute([$user['id']]);
    $orders = $oStmt->fetchAll();
    foreach ($orders as &$ord) {
        $iStmt = $pdo->prepare("SELECT oi.*,p.name,p.image FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $iStmt->execute([$ord['id']]);
        $ord['items'] = $iStmt->fetchAll();
        $hStmt = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id=? ORDER BY created_at ASC");
        $hStmt->execute([$ord['id']]);
        $ord['history'] = $hStmt->fetchAll();
    }
    $wStmt = $pdo->prepare("SELECT w.*,p.name,p.price,p.image,p.discount_percent,p.is_new FROM wishlist w JOIN products p ON w.product_id=p.id WHERE w.user_id=?");
    $wStmt->execute([$user['id']]);
    $wishlistItems = $wStmt->fetchAll();
    $cStmt = $pdo->prepare("SELECT c.*,p.name,p.price,p.image FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
    $cStmt->execute([$user['id']]);
    $cartItems = $cStmt->fetchAll();
    $cartTotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
}

$statusSteps = ['pending','confirmed','preparing','picked_up','delivered'];
$stepLabels  = ['Placed','Confirmed','Preparing','Picked Up','Delivered'];
function stepIndex(string $status, array $steps): int { return (int)array_search($status, $steps); }

// Notifications
$notifs = []; $unread = 0;
if ($USE_DB) {
    $nStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
    $nStmt->execute([$user['id']]);
    $notifs = $nStmt->fetchAll();
    $unread = count(array_filter($notifs, fn($n) => !$n['is_read']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="../images/logo2.png">
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account — EatLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/home.css">
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
      <span class="sidebar-user-role">Customer</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-nav-section">
      <div class="sidebar-nav-label">Account</div>
      <a class="sidebar-nav-item active" data-tab="orders" href="#" onclick="switchTab('orders');return false;">
        <span class="sidebar-nav-icon"><span class="material-symbols-outlined">inventory_2</span></span> My Orders
        <?php if (count($orders)): ?><span class="sidebar-badge"><?= count($orders) ?></span><?php endif; ?>
      </a>
      <a class="sidebar-nav-item" data-tab="wishlist" href="#" onclick="switchTab('wishlist');return false;">
        <span class="sidebar-nav-icon"><span class="material-symbols-outlined">favorite</span></span> Wishlist
        <?php if (count($wishlistItems)): ?><span class="sidebar-badge"><?= count($wishlistItems) ?></span><?php endif; ?>
      </a>
      <a class="sidebar-nav-item" data-tab="cart" href="#" onclick="switchTab('cart');return false;">
        <span class="sidebar-nav-icon"><span class="material-symbols-outlined">shopping_cart</span></span> Cart
        <?php if (count($cartItems)): ?><span class="sidebar-badge"><?= count($cartItems) ?></span><?php endif; ?>
      </a>
      <a class="sidebar-nav-item" data-tab="notifications" href="#" onclick="switchTab('notifications');return false;">
        <span class="sidebar-nav-icon"><span class="material-symbols-outlined">notifications</span></span> Notifications
        <?php if ($unread): ?><span class="sidebar-badge" id="notif-sidebar-badge"><?= $unread ?></span><?php endif; ?>
      </a>
    </div>
  </nav>
  <div class="sidebar-bottom">
    <button class="sidebar-logout logout-btn"><span class="material-symbols-outlined">logout</span> Logout</button>
  </div>
</aside>

<!-- MAIN -->
<div class="dashboard-main">
  <header class="dashboard-topbar">
    <button id="sidebar-toggle" style="background:none;border:none;font-size:1.4rem;cursor:pointer;display:none;" aria-label="Menu">☰</button>
    <h1 class="topbar-title" id="page-title">My Orders</h1>
    <div class="topbar-actions">
      <a href="../menu.php" class="btn-primary" style="font-size:.8rem;padding:8px 16px;text-decoration:none;">+ Order Food</a>
    </div>
  </header>

  <div class="dashboard-content">

  <!-- ══ ORDERS TAB ══ -->
  <div id="tab-orders" class="tab-panel">
    <?php if (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-icon"><span class="material-symbols-outlined">inventory_2</span></div>
      <h3>No orders yet</h3>
      <p>Start ordering delicious food from our menu!</p>
      <a href="../menu.php" class="btn-primary" style="margin-top:16px;display:inline-flex;">Browse Menu</a>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $order):
        $curStep = stepIndex($order['status'], $statusSteps);
    ?>
    <div class="order-track-card">
      <div class="order-track-header">
        <div>
          <div class="order-track-id">Order <span>#<?= $order['id'] ?></span></div>
          <div style="font-size:.82rem;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($order['shop_name']) ?> · <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
          <?php if (!in_array($order['status'], ['delivered','cancelled']) && !empty($order['delivery_pin'])): ?>
          <div style="margin-top:8px;display:inline-block;background:#FFF0E5;color:var(--primary);padding:4px 10px;border-radius:6px;font-size:.8rem;font-weight:700;border:1px solid rgba(255,107,0,0.2);">
            Delivery PIN: <span style="font-size:1rem;letter-spacing:1px;"><?= htmlspecialchars($order['delivery_pin']) ?></span>
          </div>
          <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
          <?php if ($order['status'] === 'pending'): ?>
          <button onclick="cancelOrder(<?= $order['id'] ?>)" style="background:#FFF0F0;color:#E8001C;border:1px solid #FADADD;border-radius:6px;padding:4px 8px;font-size:.75rem;font-weight:600;cursor:pointer;"><span class="material-symbols-outlined" style="font-size:inherit; vertical-align:middle;">close</span> Cancel</button>
          <?php endif; ?>
          <span class="status-pill <?= $order['status'] ?>"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span>
          <span style="font-size:.875rem;font-weight:700;color:var(--primary);">Rs. <?= number_format($order['total_amount'],2) ?></span>
        </div>
      </div>
      <!-- STEPPER -->
      <div class="order-stepper">
        <?php foreach ($statusSteps as $si => $step):
            $done   = $si < $curStep;
            $active = $si === $curStep;
        ?>
        <div class="step-item <?= $done?'done':'' ?> <?= $active?'active':'' ?>">
          <div class="step-dot"><?= $done ? '<span class="material-symbols-outlined" style="font-size:inherit; vertical-align:middle;">check</span>' : ($si+1) ?></div>
          <div class="step-label"><?= $stepLabels[$si] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- ITEMS -->
      <div style="margin-top:16px;border-top:1px solid var(--border);padding-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach ($order['items'] as $item): ?>
        <div style="display:flex;align-items:center;gap:6px;background:var(--bg);padding:6px 10px;border-radius:8px;font-size:.8rem;">
          <img src="../<?= ($item['image']??'burger')==='pizza'?'images/pizza.png':'images/burger.png' ?>" style="width:24px;height:24px;object-fit:contain;">
          <?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($order['status']==='delivered'): ?>
      <div style="margin-top:12px;">
        <a href="../product.php?id=<?= $order['items'][0]['product_id']??1 ?>#reviews" class="btn-outline" style="font-size:.8rem;padding:6px 14px;"><span class="material-symbols-outlined" style="font-size:inherit; vertical-align:middle;">star</span> Write Review</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ══ WISHLIST TAB ══ -->
  <div id="tab-wishlist" class="tab-panel" style="display:none;">
    <?php if (empty($wishlistItems)): ?>
    <div class="empty-state"><div class="empty-icon"><span class="material-symbols-outlined">favorite</span></div><h3>Wishlist is empty</h3><p>Heart items you love to save them here.</p></div>
    <?php else: ?>
    <div class="products-grid">
      <?php foreach ($wishlistItems as $wi):
          $img = ($wi['image']??'burger')==='pizza'?'../images/pizza.png':'../images/burger.png';
          $disc = $wi['discount_percent']>0 ? '<div class="product-label"><span class="discount-label">'.$wi['discount_percent'].'% OFF</span></div>' : ($wi['is_new']?'<div class="product-label"><span class="new-label">NEW</span></div>':'');
      ?>
      <div class="product-card" data-product-id="<?= $wi['product_id'] ?>" data-product-name="<?= htmlspecialchars($wi['name']) ?>" onclick="if(!event.target.closest('.product-heart, .add-to-cart-btn')) window.location='../product.php?id=<?= $wi['product_id'] ?>'">
        <div class="product-card-image-wrap">
          <?= $disc ?>
          <button class="product-heart wishlisted" data-product-id="<?= $wi['product_id'] ?>">
            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <img src="<?= $img ?>" alt="<?= htmlspecialchars($wi['name']) ?>">
          <button class="add-to-cart-btn">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6" stroke="#fff" stroke-width="1.5"/><path d="M16 10a4 4 0 01-8 0" stroke="#fff" stroke-width="1.5" fill="none"/></svg>
          </button>
        </div>
        <div class="product-card-info">
          <div class="product-card-name"><?= htmlspecialchars($wi['name']) ?></div>
          <div class="product-card-price"><span class="price-current">Rs. <?= number_format($wi['price']) ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ CART TAB ══ -->
  <div id="tab-cart" class="tab-panel" style="display:none;">
    <?php if (empty($cartItems)): ?>
    <div class="empty-state"><div class="empty-icon"><span class="material-symbols-outlined">shopping_cart</span></div><h3>Cart is empty</h3><p>Add items from the menu to get started.</p></div>
    <?php else: ?>
    <div class="data-table-wrap">
      <div class="data-table-head"><h3>Cart Items</h3></div>
      <table class="data-table">
        <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($cartItems as $ci):
              $img = ($ci['image']??'burger')==='pizza'?'../images/pizza.png':'../images/burger.png';
          ?>
          <tr>
            <td style="display:flex;align-items:center;gap:10px;">
              <img src="<?= $img ?>" class="product-thumb-sm" alt="">
              <?= htmlspecialchars($ci['name']) ?>
            </td>
            <td>Rs. <?= number_format($ci['price'],2) ?></td>
            <td><?= $ci['quantity'] ?></td>
            <td style="font-weight:700;color:var(--primary);">Rs. <?= number_format($ci['price']*$ci['quantity'],2) ?></td>
            <td><button onclick="removeFromCart(<?= $ci['product_id'] ?>, this)" style="color:#E8001C;background:none;border:none;cursor:pointer;font-size:1.1rem;"><span class="material-symbols-outlined" style="font-size:inherit; vertical-align:middle;">close</span></button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--border);">
        <span style="font-weight:700;">Total: <span id="cart-grand-total" style="color:var(--primary);font-size:1.1rem;">Rs. <?= number_format($cartTotal,2) ?></span></span>
        <button class="btn-primary" onclick="checkout()">Proceed to Checkout →</button>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ NOTIFICATIONS TAB ══ -->
  <div id="tab-notifications" class="tab-panel" style="display:none;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h2 style="font-size:1rem;font-weight:700;">All Notifications</h2>
      <?php if ($unread): ?>
      <button id="mark-all-read" class="btn-outline" style="font-size:.8rem;padding:6px 14px;">Mark all read</button>
      <?php endif; ?>
    </div>
    <?php if (empty($notifs)): ?>
    <div class="empty-state"><div class="empty-icon"><span class="material-symbols-outlined">notifications</span></div><h3>No notifications</h3><p>You're all caught up!</p></div>
    <?php else: ?>
    <div class="notif-list">
      <?php foreach ($notifs as $notif): ?>
      <div class="notif-item <?= $notif['is_read']?'':'unread' ?>" data-id="<?= $notif['id'] ?>">
        <div class="notif-icon <?= $notif['type'] ?>">
          <?= $notif['type']==='order'?'<span class="material-symbols-outlined">inventory_2</span>':($notif['type']==='delivery'?'<span class="material-symbols-outlined">two_wheeler</span>':'<span class="material-symbols-outlined">notifications</span>') ?>
        </div>
        <div class="notif-body">
          <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
          <div class="notif-msg"><?= htmlspecialchars($notif['message']) ?></div>
          <div class="notif-time" style="margin-top:4px;font-size:.7rem;color:var(--text-muted);"><?= date('d M, H:i', strtotime($notif['created_at'])) ?></div>
        </div>
        <?php if (!$notif['is_read']): ?><div class="notif-dot-unread"></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  </div><!-- /dashboard-content -->
</div><!-- /dashboard-main -->
</div><!-- /dashboard-wrapper -->

<script src="../js/dashboard.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/cart.js"></script>
<script src="../js/wishlist.js"></script>
<script>
// Tab switching
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.style.display='none');
  document.getElementById('tab-'+name).style.display='';
  document.querySelectorAll('.sidebar-nav-item').forEach(a => a.classList.remove('active'));
  document.querySelector(`[data-tab="${name}"]`)?.classList.add('active');
  const titles = {orders:'My Orders',wishlist:'Wishlist',cart:'My Cart',notifications:'Notifications'};
  document.getElementById('page-title').textContent = titles[name]||'';
}
// Init based on URL hash or default to orders
const initialTab = window.location.hash.substring(1);
if (initialTab && document.getElementById('tab-' + initialTab)) {
  switchTab(initialTab);
} else {
  switchTab('orders');
}

// Listen for hash changes so back/forward buttons work
window.addEventListener('hashchange', () => {
  const hash = window.location.hash.substring(1);
  if (hash && document.getElementById('tab-' + hash)) {
    switchTab(hash);
  }
});

// Cart helpers
async function removeFromCart(pid, btn) {
  const fd = new FormData(); 
  fd.append('action', 'remove'); 
  fd.append('product_id', pid);
  
  const r = await fetch('../api/cart.php', { method: 'POST', body: fd });
  const d = await r.json();
  
  if (d.success) {
    showToast('Removed from cart', 'info');
    
    // Update navbar badge if it exists
    const badge = document.getElementById('cart-badge');
    if (badge) {
      badge.textContent = d.count;
      badge.style.display = d.count > 0 ? '' : 'none';
    }
    
    // 1. Remove the row from the table
    const row = btn.closest('tr');
    const subtotalText = row.children[3].textContent; // Get the text of the Subtotal column
    
    // Extract numbers properly from "Rs. 1,500.00" by removing "Rs. " and commas
    const subtotal = parseFloat(subtotalText.replace(/Rs\.?\s*/i, '').replace(/,/g, ''));
    row.remove();
    
    // 2. Update the grand total
    const totalEl = document.getElementById('cart-grand-total');
    if (totalEl) {
      let currentTotal = parseFloat(totalEl.textContent.replace(/Rs\.?\s*/i, '').replace(/,/g, ''));
      let newTotal = Math.max(0, currentTotal - subtotal);
      
      // Format number with commas and 2 decimals
      totalEl.textContent = 'Rs. ' + newTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // 3. Show empty state if cart is now empty
    const tbody = document.querySelector('#tab-cart tbody');
    if (tbody && tbody.children.length === 0) {
      document.getElementById('tab-cart').innerHTML = '<div class="empty-state"><div class="empty-icon"><span class="material-symbols-outlined">shopping_cart</span></div><h3>Cart is empty</h3><p>Add items from the menu to get started.</p></div>';
    }
  }
}
async function checkout() {
  const addr = prompt('Enter your delivery address:','');
  if (!addr) return;
  // Get cart items
  const r = await fetch('../api/cart.php');
  const d = await r.json();
  if (!d.items?.length) { showToast('Cart is empty','error'); return; }
  const items = d.items.map(i=>({product_id:i.product_id,quantity:i.quantity}));
  const fd=new FormData();
  fd.append('action','place');
  fd.append('delivery_address',addr);
  fd.append('items',JSON.stringify(items));
  const or=await fetch('../api/orders.php',{method:'POST',body:fd});
  const od=await or.json();
  if(od.success){
    // Explicitly clear the cart upon checkout
    const clrFd = new FormData();
    clrFd.append('action', 'clear');
    await fetch('../api/cart.php', { method: 'POST', body: clrFd });

    showToast('Order #'+od.order_id+' placed!','success');
    setTimeout(()=>location.reload(),1500);
  } else {
    showToast(od.message||'Order failed','error');
  }
}

async function cancelOrder(orderId) {
  if (!confirm('Are you sure you want to cancel Order #' + orderId + '?')) return;
  const fd = new FormData();
  fd.append('action', 'update_status');
  fd.append('order_id', orderId);
  fd.append('status', 'cancelled');
  fd.append('note', 'Cancelled by customer');
  const r = await fetch('../api/orders.php', { method: 'POST', body: fd });
  const d = await r.json();
  if (d.success) {
    showToast('Order cancelled.', 'success');
    setTimeout(() => location.reload(), 1000);
  } else {
    showToast(d.message || 'Failed to cancel', 'error');
  }
}
</script>
</body>
</html>
