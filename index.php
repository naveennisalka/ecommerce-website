<?php
session_start();
require_once 'db/connection.php';
require_once 'db/demo_data.php';

$activePage = 'index.php';

// Get products for sections
$collectionProducts = array_slice($DEMO_PRODUCTS, 0, 8);
$forYouProducts     = array_slice($DEMO_PRODUCTS, 0, 8);
$wishlist           = $_SESSION['wishlist'] ?? [];

function renderCard($p, $wishlist = []) {
    $wishlisted   = in_array($p['id'], $wishlist) ? 'wishlisted' : '';
    $img          = $p['image'] === 'pizza' ? 'images/pizza.png' : 'images/burger.png';
    $discountHtml = '';
    if ($p['discount_percent'] > 0) {
        $discountHtml = '<div class="product-label"><span class="discount-label">'.$p['discount_percent'].'% OFF</span></div>';
    } elseif ($p['is_new']) {
        $discountHtml = '<div class="product-label"><span class="new-label">NEW</span></div>';
    }
    $origPrice = $p['original_price']
        ? '<span class="price-original">Rs. '.number_format($p['original_price']).'</span>' : '';

    return '
    <div class="product-card" data-product-id="'.$p['id'].'" data-product-name="'.htmlspecialchars($p['name']).'" onclick="if(!event.target.closest(\'.product-heart, .add-to-cart-btn\')) window.location=\'product.php?id='.$p['id'].'\'" style="cursor:pointer;">
      <div class="product-card-image-wrap">
        '.$discountHtml.'
        <button class="product-heart '.$wishlisted.'" data-product-id="'.$p['id'].'" title="Add to wishlist" aria-label="Wishlist">
          <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
        <img src="'.$img.'" alt="'.htmlspecialchars($p['name']).'" loading="lazy">
        <button class="add-to-cart-btn" title="Add to cart" aria-label="Add to cart">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6" stroke="#fff" stroke-width="1.5"/><path d="M16 10a4 4 0 01-8 0" stroke="#fff" stroke-width="1.5" fill="none"/></svg>
        </button>
      </div>
      <div class="product-card-info">
        <div class="product-card-name">'.htmlspecialchars($p['name']).'</div>
        <div class="product-card-price">
          <span class="price-current">Rs. '.number_format($p['price']).'</span>
          '.$origPrice.'
        </div>
      </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="images/logo2.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EatLink — Order Food Online</title>
  <meta name="description" content="Order your favourite food online. Best deals on pizza, burgers, chicken and more. Free delivery available.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/home.css">
</head>
<body>

<!-- TOAST CONTAINER -->
<div id="toast-container"></div>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- MAIN CONTENT -->
<main>

  <!-- ══ HERO SLIDER ══ -->
  <section class="container" style="padding-top: 0;">
    <div class="hero-section">
      <div class="hero-slider" id="hero-slider">

        <!-- SLIDE 1 -->
        <div class="hero-slide">
          <img src="images/hero1.png" alt="Pizza Hut Lunch Buffet Glasgow" loading="eager">
          <div class="hero-slide-overlay">
            <div class="hero-slide-text">
              <h2>Pizza Hut Lunch<br>Buffet Glasgow</h2>
              <p>All-you-can-eat from Rs. 1,200</p>
            </div>
          </div>
        </div>

        <!-- SLIDE 2 -->
        <div class="hero-slide">
          <img src="images/hero2.png" alt="McDonald's Best Burgers" loading="lazy">
          <div class="hero-slide-overlay">
            <div class="hero-slide-text">
              <h2>McDonald's<br>Best Burgers</h2>
              <p>Crispy, juicy and delicious</p>
            </div>
          </div>
        </div>

        <!-- SLIDE 3 -->
        <div class="hero-slide">
          <img src="images/hero3.png" alt="KFC Special Offer" loading="lazy">
          <div class="hero-slide-overlay">
            <div class="hero-slide-text">
              <h2>KFC Special<br>Offer Today</h2>
              <p>Free delivery on orders above Rs. 500</p>
            </div>
          </div>
        </div>

      </div>

      <!-- NAV ARROWS -->
      <button class="hero-prev" id="hero-prev" aria-label="Previous slide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button class="hero-next" id="hero-next" aria-label="Next slide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>

      <!-- DOTS -->
      <div class="hero-dots">
        <button class="hero-dot active" aria-label="Slide 1"></button>
        <button class="hero-dot" aria-label="Slide 2"></button>
        <button class="hero-dot" aria-label="Slide 3"></button>
      </div>
    </div>
  </section>

  <!-- ══ INFO STRIP ══ -->
  <section class="container section-sm">
    <div class="info-strip">
      <div class="info-card">
        <div class="info-card-icon"><span class="material-symbols-outlined" style="font-size:inherit;">two_wheeler</span></div>
        <div class="info-card-text">
          <h4>Fast Delivery</h4>
          <p>Get your order delivered in 30 minutes or less, guaranteed</p>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><span class="material-symbols-outlined" style="font-size:inherit;">support_agent</span></div>
        <div class="info-card-text">
          <h4>24/7 Support</h4>
          <p>Our customer support team is always available to help you</p>
        </div>
      </div>
      <div class="info-card">
        <div class="info-card-icon"><span class="material-symbols-outlined" style="font-size:inherit;">verified</span></div>
        <div class="info-card-text">
          <h4>Quality Food</h4>
          <p>Fresh ingredients sourced from the best local suppliers</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ SHOP BY COLLECTION ══ -->
  <section class="container section">
    <div class="section-header">
      <h2 class="section-title">Shop by Collection</h2>
      <a href="menu.php" class="see-all-link">See All →</a>
    </div>
    <div class="products-row" id="collection-row">
      <?php foreach ($collectionProducts as $p): ?>
        <?= renderCard($p, $wishlist) ?>
      <?php endforeach; ?>
    </div>
  </section>





  <!-- ══ AD BANNER ══ -->
  <section class="container section-sm">
    <div class="ad-banner">
      <img src="images/hero1.png" alt="Pizza Hut Lunch Buffet Glasgow">
      <div class="ad-banner-overlay">
        <div class="ad-banner-text">
          <h3>Pizza Hut Lunch<br>Buffet Glasgow</h3>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ FOR YOU ══ -->
  <section class="container section">
    <div class="section-header">
      <h2 class="section-title">For You</h2>
      <a href="menu.php" class="see-all-link">See All →</a>
    </div>
    <div class="products-grid" id="foryou-grid">
      <?php foreach ($forYouProducts as $p): ?>
        <?= renderCard($p, $wishlist) ?>
      <?php endforeach; ?>
    </div>
    <div class="pagination-wrap">
      <button class="next-page-btn" onclick="window.location='menu.php'">
        NEXT PAGE
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>
  </section>

  <!-- ══ FOOTER AD BANNER ══ -->
  <section class="container section-sm">
    <div class="ad-banner">
      <img src="images/hero2.png" alt="McDonald's Best Burgers">
      <div class="ad-banner-overlay">
        <div class="ad-banner-text">
          <h3>McDonald's Best<br>Burgers Today</h3>
        </div>
      </div>
    </div>
  </section>

</main>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- SCRIPTS -->
<script src="js/navbar.js"></script>
<script src="js/slider.js"></script>
<script src="js/cart.js"></script>
<script src="js/wishlist.js"></script>

</body>
</html>
