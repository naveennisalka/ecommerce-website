<?php
require_once '../db/connection.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'shop_owner') {
    header('Location: ../login.php'); exit;
}
$user = $_SESSION['user'];
$editProduct = null;
$editId = (int)($_GET['edit'] ?? 0);

if ($USE_DB && $editId) {
    $ep = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $ep->execute([$editId]);
    $editProduct = $ep->fetch();
}

// Load categories & brands
$categories = $brands = [];
if ($USE_DB) {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $brands     = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
} else {
    $categories = [['id'=>1,'name'=>'Burgers'],['id'=>2,'name'=>'Pizza'],['id'=>3,'name'=>'Chicken'],['id'=>4,'name'=>'Sides'],['id'=>5,'name'=>'Drinks']];
    $brands     = [['id'=>1,'name'=>'Pizza Hut'],['id'=>2,'name'=>"McDonald's"],['id'=>3,'name'=>'KFC'],['id'=>4,'name'=>'Burger King']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $editProduct ? 'Edit Product' : 'Add Product' ?> — EatLink</title>
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
  <a href="../index.php" class="sidebar-logo">
    <div class="sidebar-logo-icon">🍔</div>
    <div class="sidebar-logo-text">Eat<span>Link</span></div>
  </a>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    <div>
      <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <span class="sidebar-user-role">Shop Owner</span>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a class="sidebar-nav-item" href="shop_owner.php"><span class="sidebar-nav-icon">📊</span> Dashboard</a>
    <a class="sidebar-nav-item active" href="add_product.php"><span class="sidebar-nav-icon">➕</span> <?= $editProduct?'Edit':'Add' ?> Product</a>
  </nav>
  <div class="sidebar-bottom">
    <button class="sidebar-logout logout-btn">🚪 Logout</button>
  </div>
</aside>

<!-- MAIN -->
<div class="dashboard-main">
  <header class="dashboard-topbar">
    <h1 class="topbar-title"><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h1>
    <a href="shop_owner.php" class="btn-outline" style="font-size:.85rem;padding:8px 16px;text-decoration:none;">← Back to Dashboard</a>
  </header>
  <div class="dashboard-content">
    <div id="auth-alert" class="auth-alert"></div>

    <form id="product-form" enctype="multipart/form-data">
      <?php if ($editProduct): ?>
      <input type="hidden" name="action" value="update_product">
      <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
      <?php else: ?>
      <input type="hidden" name="action" value="add_product">
      <?php endif; ?>

      <!-- BASIC INFO -->
      <div class="form-card">
        <h3>📋 Basic Information</h3>
        <div class="form-grid">
          <div class="dash-form-group form-full">
            <label class="dash-form-label">Product Name *</label>
            <input type="text" name="name" class="dash-form-input" placeholder="e.g. Juicy Beef Burger" required value="<?= htmlspecialchars($editProduct['name']??'') ?>">
          </div>
          <div class="dash-form-group form-full">
            <label class="dash-form-label">Description</label>
            <textarea name="description" class="dash-form-textarea" placeholder="Describe the product..."><?= htmlspecialchars($editProduct['description']??'') ?></textarea>
          </div>
          <div class="dash-form-group">
            <label class="dash-form-label">Category</label>
            <select name="category_id" class="dash-form-select">
              <option value="">— Select Category —</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($editProduct['category_id']??'')==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="dash-form-group">
            <label class="dash-form-label">Brand</label>
            <select name="brand_id" class="dash-form-select">
              <option value="">— Select Brand —</option>
              <?php foreach ($brands as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ($editProduct['brand_id']??'')==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- PRICING -->
      <div class="form-card">
        <h3>💰 Pricing</h3>
        <div class="form-grid">
          <div class="dash-form-group">
            <label class="dash-form-label">Selling Price (Rs.) *</label>
            <input type="number" name="price" class="dash-form-input" step="0.01" min="0" placeholder="0.00" required value="<?= $editProduct['price']??'' ?>">
          </div>
          <div class="dash-form-group">
            <label class="dash-form-label">Original Price (Rs.)</label>
            <input type="number" name="original_price" class="dash-form-input" step="0.01" min="0" placeholder="Leave blank if no discount" value="<?= $editProduct['original_price']??'' ?>">
          </div>
          <div class="dash-form-group">
            <label class="dash-form-label">Discount %</label>
            <input type="number" name="discount_percent" class="dash-form-input" min="0" max="100" placeholder="0" value="<?= $editProduct['discount_percent']??0 ?>">
          </div>
          <div class="dash-form-group">
            <label class="dash-form-label">Stock Quantity</label>
            <input type="number" name="stock" class="dash-form-input" min="0" placeholder="100" value="<?= $editProduct['stock']??100 ?>">
          </div>
        </div>
      </div>

      <!-- OPTIONS -->
      <div class="form-card">
        <h3>⚙️ Options</h3>
        <div class="form-grid">
          <div class="dash-form-group">
            <label class="dash-form-label">Delivery Type</label>
            <select name="delivery_type" class="dash-form-select">
              <option value="free"  <?= ($editProduct['delivery_type']??'free')==='free' ?'selected':'' ?>>🟢 Free Delivery</option>
              <option value="paid"  <?= ($editProduct['delivery_type']??'')==='paid' ?'selected':'' ?>>💳 Paid Delivery</option>
            </select>
          </div>
          <div class="dash-form-group" style="justify-content:flex-end;align-items:flex-start;padding-top:28px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:.875rem;">
              <input type="checkbox" name="is_new" <?= !empty($editProduct['is_new'])?'checked':'' ?> style="width:18px;height:18px;accent-color:var(--primary);">
              <span>Mark as <strong>NEW</strong> item</span>
            </label>
          </div>
        </div>
      </div>

      <!-- IMAGE -->
      <?php if (!$editProduct): ?>
      <div class="form-card">
        <h3>🖼️ Product Image</h3>
        <div class="upload-area" onclick="document.getElementById('image-input').click()">
          <div class="upload-icon">📷</div>
          <p><span>Click to upload</span> or drag and drop</p>
          <p style="font-size:.75rem;margin-top:4px;">PNG, JPG, WEBP (max 5MB)</p>
        </div>
        <input type="file" id="image-input" name="image" accept="image/*" style="display:none;" onchange="previewImg(this)">
        <img id="img-preview" src="" style="display:none;max-height:160px;margin-top:12px;border-radius:12px;object-fit:contain;">
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:12px;margin-top:8px;">
        <button type="submit" class="auth-submit" id="submit-btn" style="max-width:260px;">
          <?= $editProduct ? '💾 Save Changes' : '+ Add Product' ?>
        </button>
        <a href="shop_owner.php" class="btn-outline" style="padding:14px 24px;text-decoration:none;">Cancel</a>
      </div>
    </form>
  </div>
</div>
</div>

<script src="../js/dashboard.js"></script>
<script>
document.getElementById('product-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="btn-spinner"></div> Saving...';
  const fd = new FormData(this);
  const res = await fetch('../api/shop.php', { method:'POST', body:fd });
  const d   = await res.json();
  const alert = document.getElementById('auth-alert');
  if (d.success) {
    alert.className = 'auth-alert success';
    alert.textContent = '✅ '+d.message;
    setTimeout(() => window.location.href='shop_owner.php', 1200);
  } else {
    alert.className = 'auth-alert error';
    alert.textContent = '❌ '+d.message;
    btn.disabled = false;
    btn.innerHTML = '<?= $editProduct ? '💾 Save Changes' : '+ Add Product' ?>';
  }
  alert.style.display='block';
  alert.scrollIntoView({behavior:'smooth',block:'center'});
});

function previewImg(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('img-preview');
    prev.src = e.target.result;
    prev.style.display = 'block';
  };
  reader.readAsDataURL(file);
}
</script>
</body>
</html>
