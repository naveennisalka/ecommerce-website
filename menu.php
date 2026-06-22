<?php
session_start();
require_once 'db/connection.php';
require_once 'db/demo_data.php';

$activePage = 'menu.php';
$wishlist   = $_SESSION['wishlist'] ?? [];

// Count products per category
$catCounts = [];
foreach ($DEMO_PRODUCTS as $p) {
    $catCounts[$p['category_id']] = ($catCounts[$p['category_id']] ?? 0) + 1;
}
$brandColors = ['#E8001C','#FFC72C','#E4002B','#FF8800','#009639','#006491'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="images/logo2.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu — EatLink | Browse All Food Items</title>
  <meta name="description" content="Browse our full menu. Filter by brand, category, delivery option and price range. Burgers, pizza, chicken and more.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/menu.css">
</head>
<body>

<!-- TOAST CONTAINER -->
<div id="toast-container"></div>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- FILTER OVERLAY -->
<div class="filter-overlay" id="filter-overlay"></div>

<!-- FILTER POPUP PANEL -->
<aside class="filter-popup" id="filter-popup" role="dialog" aria-modal="true" aria-label="Filter options">

  <!-- HEADER -->
  <div class="filter-popup-header">
    <h3>
      <svg style="width:18px;height:18px;vertical-align:middle;margin-right:6px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/>
      </svg>
      Filters
    </h3>
    <button class="filter-close-btn" id="filter-close-btn" aria-label="Close filter"><span class="material-symbols-outlined" style="font-size:inherit; vertical-align:middle;">close</span></button>
  </div>

  <!-- BODY -->
  <div class="filter-popup-body">



    <!-- ── DELIVERY OPTION ── -->
    <div class="filter-section">
      <div class="filter-section-title">Delivery Option</div>
      <div class="delivery-chips">
        <div class="delivery-chip" data-value="free">
          <span class="material-symbols-outlined" style="font-size:inherit;">two_wheeler</span>
          <span>Free Delivery</span>
        </div>
        <div class="delivery-chip" data-value="paid">
          <span class="material-symbols-outlined" style="font-size:inherit;">credit_card</span>
          <span>Paid Delivery</span>
        </div>
      </div>
    </div>

    <!-- ── PRICE RANGE ── -->
    <div class="filter-section">
      <div class="filter-section-title">Price Range</div>
      <div class="price-range-wrap">
        <div class="price-range-values">
          <span class="price-val" id="price-val-min">Rs. 0</span>
          <span class="price-val" id="price-val-max">Rs. 10,000</span>
        </div>
        <div class="range-slider-container">
          <div class="range-track"></div>
          <div class="range-fill" id="range-fill" style="left:0%;right:0%"></div>
          <input type="range" id="price-min" min="0" max="10000" value="0" step="100">
          <input type="range" id="price-max" min="0" max="10000" value="10000" step="100">
        </div>
        <div class="price-range-labels">
          <span>Rs. 0</span>
          <span>Rs. 10,000</span>
        </div>
      </div>
    </div>

    <!-- ── SORT BY ── -->
    <div class="filter-section">
      <div class="filter-section-title">Sort By</div>
      <div class="sort-options">
        <div class="sort-option" data-value="default">
          <div class="sort-check"></div>
          <span>Default</span>
        </div>
        <div class="sort-option" data-value="price_asc">
          <div class="sort-check"></div>
          <span>Price: Low → High</span>
        </div>
        <div class="sort-option" data-value="price_desc">
          <div class="sort-check"></div>
          <span>Price: High → Low</span>
        </div>
        <div class="sort-option" data-value="newest">
          <div class="sort-check"></div>
          <span>Newest First</span>
        </div>
        <div class="sort-option" data-value="discount">
          <div class="sort-check"></div>
          <span>Most Discount</span>
        </div>
      </div>
    </div>

    <!-- ── CATEGORY ── -->
    <div class="filter-section">
      <div class="filter-section-title">Category</div>
      <div class="category-filter-list">
        <?php foreach ($DEMO_CATEGORIES as $cat): ?>
        <label class="category-filter-item">
          <input type="checkbox" class="category-filter-cb" value="<?= $cat['id'] ?>" id="cat-<?= $cat['id'] ?>">
          <span class="cat-emoji"><?= $cat['icon'] ?></span>
          <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
          <span class="cat-count"><?= $catCounts[$cat['id']] ?? 0 ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /body -->

  <!-- FOOTER ACTIONS -->
  <div class="filter-popup-footer">
    <button class="filter-reset-btn" id="filter-reset-btn">Reset</button>
    <button class="filter-apply-btn" id="filter-apply-btn">Apply Filters</button>
  </div>

</aside>

<!-- MAIN CONTENT -->
<main>

  <!-- HERO BANNER -->
  <section class="container" style="padding-top:0;">
    <div class="hero-section" style="border-radius:var(--card-radius);overflow:hidden;">
      <div class="hero-slider">
        <div class="hero-slide">
          <img src="images/hero1.png" alt="Pizza Hut Lunch Buffet Glasgow">
          <div class="hero-slide-overlay">
            <div class="hero-slide-text">
              <h2>Pizza Hut Lunch<br>Buffet Glasgow</h2>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FILTER BAR + PRODUCTS -->
  <section class="container section">

    <!-- FILTER BAR -->
    <div class="filter-bar" id="filter-bar">
      <button class="filter-toggle-btn" id="filter-toggle-btn" aria-haspopup="true" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="4" y1="6" x2="20" y2="6"/>
          <line x1="8" y1="12" x2="16" y2="12"/>
          <line x1="11" y1="18" x2="13" y2="18"/>
        </svg>
        Filters
      </button>

      <!-- ACTIVE FILTER TAGS -->
      <div class="filter-tags" id="filter-tags"></div>
      <button class="clear-all-btn" id="clear-all-btn" style="display:none;">Clear All</button>
    </div>

    <!-- RESULTS INFO -->
    <p class="results-info" id="results-info">Loading items...</p>

    <!-- PRODUCTS GRID -->
    <div class="products-grid" id="menu-products-grid">
      <div style="grid-column:1/-1;display:flex;justify-content:center;padding:64px;">
        <div class="spinner"></div>
      </div>
    </div>

    <!-- PAGINATION -->
    <div class="pagination-wrap" id="pagination-wrap"></div>

  </section>

</main>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- SCRIPTS -->
<script src="js/navbar.js"></script>
<script src="js/cart.js"></script>
<script src="js/wishlist.js"></script>
<script src="js/filter.js"></script>

</body>
</html>
