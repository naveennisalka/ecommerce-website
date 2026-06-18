<?php
session_start();
require_once 'db/connection.php';
require_once 'db/demo_data.php';

$productId  = (int)($_GET['id'] ?? 1);
$activePage = 'menu.php';
$wishlist   = $_SESSION['wishlist'] ?? [];

// Fetch product
if ($USE_DB) {
    $stmt = $pdo->prepare(
        "SELECT p.*,c.name AS cat_name,b.name AS brand_name,s.name AS shop_name,s.address AS shop_address
         FROM products p
         LEFT JOIN categories c ON p.category_id=c.id
         LEFT JOIN brands b ON p.brand_id=b.id
         LEFT JOIN shops s ON p.shop_id=s.id
         WHERE p.id=? AND p.is_active=1"
    );
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    // Images
    $imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order ASC");
    $imgStmt->execute([$productId]);
    $images = $imgStmt->fetchAll();
    if (empty($images)) {
        $defImg = ($product['image'] ?? 'burger') === 'pizza' ? 'images/pizza.png' : 'images/burger.png';
        $images = [['image_path'=>$defImg,'is_primary'=>1],['image_path'=>$defImg,'is_primary'=>0],['image_path'=>$defImg,'is_primary'=>0]];
    }

    // Related products
    $relStmt = $pdo->prepare("SELECT * FROM products WHERE category_id=? AND id!=? AND is_active=1 LIMIT 4");
    $relStmt->execute([$product['category_id'] ?? 1, $productId]);
    $related = $relStmt->fetchAll();
} else {
    // Demo data
    $product = null;
    foreach ($DEMO_PRODUCTS as $p) { if ($p['id'] === $productId) { $product = $p; break; } }
    if (!$product) $product = $DEMO_PRODUCTS[4]; // Default to Juicy Beef
    $product['cat_name']    = 'Burgers';
    $product['brand_name']  = 'Burger King';
    $product['shop_name']   = 'Demo Shop';
    $product['description'] = 'Bring the steakhouse experience home with our ultra-juicy beef patties. Crafted from premium, 100% all-natural beef with the perfect fat-to-lean ratio, these patties are engineered to stay incredibly tender and burst with savory flavor. A lifelong upgrade. Just sear, build your masterpiece, and bite into pure satisfaction.';
    $imgFile = $product['image'] === 'pizza' ? 'images/pizza.png' : 'images/burger.png';
    $images  = [['image_path'=>$imgFile,'is_primary'=>1],['image_path'=>$imgFile],['image_path'=>$imgFile],['image_path'=>'images/pizza.png']];
    $related = array_slice($DEMO_PRODUCTS, 0, 4);
}

if (!$product) { header('Location: menu.php'); exit; }

$primaryImg = '';
foreach ($images as $img) { if ($img['is_primary']) { $primaryImg = $img['image_path']; break; } }
if (!$primaryImg && $images) $primaryImg = $images[0]['image_path'];

$isWishlisted = in_array($productId, $wishlist);

function renderStarsHtml(float $r): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($r)) $html .= '<span class="star filled">★</span>';
        elseif ($i - 0.5 <= $r) $html .= '<span class="star half">★</span>';
        else $html .= '<span class="star">☆</span>';
    }
    return $html;
}

function renderCard($p, $wishlist=[]): string {
    $wishlisted = in_array($p['id'],$wishlist) ? 'wishlisted' : '';
    $img = ($p['image']??'burger')==='pizza' ? 'images/pizza.png' : 'images/burger.png';
    $disc = '';
    if (($p['discount_percent']??0) > 0) $disc = '<div class="product-label"><span class="discount-label">'.$p['discount_percent'].'% OFF</span></div>';
    elseif (!empty($p['is_new']))         $disc = '<div class="product-label"><span class="new-label">NEW</span></div>';
    $orig = !empty($p['original_price']) ? '<span class="price-original">Rs. '.number_format($p['original_price']).'</span>' : '';
    return '<div class="product-card" data-product-id="'.$p['id'].'" data-product-name="'.htmlspecialchars($p['name']).'" onclick="window.location=\'product.php?id='.$p['id'].'\'">
      <div class="product-card-image-wrap">
        '.$disc.'
        <button class="product-heart '.$wishlisted.'" data-product-id="'.$p['id'].'" title="Wishlist" aria-label="Wishlist" onclick="event.stopPropagation()">
          <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
        <img src="'.$img.'" alt="'.htmlspecialchars($p['name']).'" loading="lazy">
        <button class="add-to-cart-btn" title="Add to cart" aria-label="Add to cart" onclick="event.stopPropagation()">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6" stroke="#fff" stroke-width="1.5"/><path d="M16 10a4 4 0 01-8 0" stroke="#fff" stroke-width="1.5" fill="none"/></svg>
        </button>
      </div>
      <div class="product-card-info">
        <div class="product-card-name">'.htmlspecialchars($p['name']).'</div>
        <div class="product-card-price">
          <span class="price-current">Rs. '.number_format($p['price']).'</span>'.$orig.'
        </div>
      </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['name']) ?> — EatLink</title>
  <meta name="description" content="<?= htmlspecialchars(substr($product['description'] ?? '', 0, 160)) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/product.css">
</head>
<body>

<div id="toast-container"></div>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<main class="container" style="padding-top:16px;">

  <!-- BREADCRUMB -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="index.php">Home</a>
    <span class="sep">›</span>
    <a href="menu.php">Menu</a>
    <?php if (!empty($product['cat_name'])): ?>
    <span class="sep">›</span>
    <a href="menu.php?category=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['cat_name']) ?></a>
    <?php endif; ?>
    <span class="sep">›</span>
    <span class="current"><?= htmlspecialchars($product['name']) ?></span>
  </nav>

  <!-- HIDDEN INPUTS for JS -->
  <input type="hidden" id="product-id"       value="<?= $productId ?>">
  <input type="hidden" id="product-name-val" value="<?= htmlspecialchars($product['name']) ?>">

  <!-- ══ PRODUCT LAYOUT ══ -->
  <div class="product-layout">

    <!-- IMAGE GALLERY -->
    <div class="product-gallery">
      <div class="gallery-main">
        <img id="gallery-main-img" src="<?= htmlspecialchars($primaryImg) ?>"
             alt="<?= htmlspecialchars($product['name']) ?>"
             style="transition:opacity .2s;">
      </div>
      <div class="gallery-thumbs">
        <?php foreach (array_slice($images, 0, 4) as $img): ?>
        <div class="gallery-thumb">
          <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="Product image">
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- PRODUCT INFO -->
    <div class="product-info">
      <div class="product-info-top">
        <h1 class="product-info-name"><?= htmlspecialchars($product['name']) ?></h1>
        <button class="product-wishlist-btn <?= $isWishlisted ? 'wishlisted' : '' ?>"
                id="product-wishlist-btn" aria-label="Add to wishlist">
          <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
      </div>

      <!-- PRICE -->
      <div class="product-price-row">
        <span class="product-price-current">Rs. <?= number_format($product['price'], 2) ?></span>
        <?php if (!empty($product['original_price'])): ?>
        <span class="product-price-original">/ Rs. <?= number_format($product['original_price'], 2) ?></span>
        <?php endif; ?>
        <?php if (($product['discount_percent'] ?? 0) > 0): ?>
        <span class="product-discount-pill"><?= $product['discount_percent'] ?>%</span>
        <?php elseif (!empty($product['is_new'])): ?>
        <span class="product-new-pill">NEW</span>
        <?php endif; ?>
      </div>

      <!-- DELIVERY -->
      <div class="product-delivery-badge">
        <?= ($product['delivery_type']??'free')==='free' ? '🛵 Free Delivery' : '💳 Paid Delivery' ?>
      </div>

      <!-- DESCRIPTION -->
      <p class="product-description"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

      <div class="product-divider"></div>

      <!-- QUANTITY -->
      <div class="product-qty-wrap">
        <div class="product-qty-label">Quantity</div>
        <div class="product-qty">
          <button class="qty-btn" id="qty-minus" aria-label="Decrease quantity">−</button>
          <div class="qty-display" id="qty-display">01</div>
          <button class="qty-btn" id="qty-plus" aria-label="Increase quantity">+</button>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="product-actions">
        <button class="btn-buy-now" id="product-buy-now">⚡ Buy Now</button>
        <button class="btn-add-cart-lg" id="product-add-cart">🛒 Add to Cart</button>
      </div>

      <!-- SHOP INFO -->
      <?php if (!empty($product['shop_name'])): ?>
      <div style="margin-top:20px;padding:14px;background:var(--primary-ultra);border-radius:12px;font-size:.82rem;color:var(--text-muted);">
        🏪 <strong style="color:var(--text-dark);"><?= htmlspecialchars($product['shop_name']) ?></strong>
        <?php if (!empty($product['brand_name'])): ?>
        &nbsp;·&nbsp; <?= htmlspecialchars($product['brand_name']) ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ RATINGS & REVIEWS ══ -->
  <section class="reviews-section">
    <h2 class="section-title">Ratings &amp; Reviews</h2>

    <div class="rating-summary">
      <div>
        <div class="rating-big" id="avg-rating">—</div>
        <span class="rating-out-of">/5</span>
      </div>
      <div class="rating-details">
        <div class="rating-stars-row" id="stars-row">
          <span class="star">☆</span><span class="star">☆</span><span class="star">☆</span>
          <span class="star">☆</span><span class="star">☆</span>
        </div>
        <div class="rating-count" id="total-ratings">Loading...</div>
      </div>
    </div>

    <!-- REVIEWS LIST -->
    <div class="reviews-list" id="reviews-list">
      <div style="display:flex;justify-content:center;padding:32px;"><div class="spinner"></div></div>
    </div>

    <!-- WRITE REVIEW -->
    <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'customer'): ?>
    <div class="write-review-section">
      <h4>Write a Review</h4>
      <div class="star-picker" id="star-picker">
        <span class="star-pick" data-val="1">★</span>
        <span class="star-pick" data-val="2">★</span>
        <span class="star-pick" data-val="3">★</span>
        <span class="star-pick" data-val="4">★</span>
        <span class="star-pick" data-val="5">★</span>
      </div>
      <form id="review-form">
        <textarea class="review-textarea" name="comment" placeholder="Share your experience with this product..."></textarea>
        <button type="submit" class="btn-primary">Submit Review</button>
      </form>
    </div>
    <?php elseif (empty($_SESSION['user'])): ?>
    <div style="margin-top:16px;padding:14px;background:var(--primary-ultra);border-radius:12px;text-align:center;">
      <p style="font-size:.875rem;color:var(--text-muted);">
        <a href="login.php" style="color:var(--primary);font-weight:600;">Sign in</a> to write a review.
      </p>
    </div>
    <?php endif; ?>
  </section>

  <!-- ══ YOU MAY ALSO LIKE ══ -->
  <?php if (!empty($related)): ?>
  <section class="section">
    <div class="section-header">
      <h2 class="section-title">You may also like</h2>
      <a href="menu.php" class="see-all-link">See All →</a>
    </div>
    <div class="products-grid">
      <?php foreach ($related as $rp): ?>
        <?= renderCard($rp, $wishlist) ?>
      <?php endforeach; ?>
    </div>
    <div class="pagination-wrap" style="margin-top:32px;">
      <button class="next-page-btn" onclick="window.location='menu.php'">
        SEE MORE
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>
  </section>
  <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>

<script src="js/navbar.js"></script>
<script src="js/cart.js"></script>
<script src="js/wishlist.js"></script>
<script src="js/product.js"></script>
</body>
</html>
