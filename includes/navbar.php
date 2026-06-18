<?php
// REUSABLE NAVBAR COMPONENT (NEW DESIGN)
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$loggedUser = $_SESSION['user'] ?? null;

$cartCount = array_sum($_SESSION['cart'] ?? []);
if ($loggedUser && !empty($pdo)) {
    try {
        $cq = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id=?");
        $cq->execute([$loggedUser['id']]);
        $cartCount = (int)$cq->fetchColumn();
    } catch (Exception $e) {}
}

$dashMap = ['customer'=>'dashboard/user.php','shop_owner'=>'dashboard/shop_owner.php','delivery_man'=>'dashboard/delivery.php'];
$dashLink = $loggedUser ? ($dashMap[$loggedUser['role']] ?? '#') : 'login.php';
$profileInitial = $loggedUser ? strtoupper(substr($loggedUser['name'],0,1)) : 'U';

// Determine base path to root for images and links when navbar is included in subfolders
$basePath = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../' : '';

?>
<nav class="navbar-wrapper-new" id="main-navbar" aria-label="Main navigation">
  
  <!-- LEFT: LOGO -->
  <a href="<?= $basePath ?>index.php" class="nav-logo-new" aria-label="Home">
    <img src="<?= $basePath ?>images/burger.png" alt="EatLink Logo">
  </a>

  <!-- MENU PILL -->
  <a href="<?= $basePath ?>menu.php" class="nav-menu-btn-new <?= $activePage==='menu.php'?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="currentColor">
      <path d="M18.5 3H5.5C4.67 3 4 3.67 4 4.5v15c0 .83.67 1.5 1.5 1.5h13c.83 0 1.5-.67 1.5-1.5v-15c0-.83-.67-1.5-1.5-1.5zm-1 15h-11V5h11v13z"/>
      <path d="M8 7h8v1.5H8zm0 3h8v1.5H8zm0 3h5v1.5H8z"/>
    </svg>
    Menu
  </a>

  <!-- CENTER: SEARCH BAR -->
  <div class="nav-search-container">
    <input type="text" class="nav-search-input-new" id="nav-search-input" placeholder="Search Foods..." autocomplete="off">
    <button class="nav-search-submit-new" id="nav-search-submit" aria-label="Search">
      Search
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
    </button>
  </div>

  <!-- RIGHT: ICONS (Cart, Wishlist, Profile) -->
  <div class="nav-icons-container">
    
    <!-- CART -->
    <a href="<?= $basePath ?>cart.php" class="nav-icon-circle" title="View Cart">
      <svg viewBox="0 0 24 24" fill="currentColor">
         <!-- standard cart icon -->
         <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
      </svg>
      <span class="nav-badge" id="cart-badge" style="<?= $cartCount>0?'':'display:none;' ?>"></span>
    </a>

    <!-- WISHLIST -->
    <a href="<?= $basePath ?><?= $loggedUser?'dashboard/user.php#wishlist':'login.php' ?>" class="nav-icon-circle" title="Wishlist">
      <svg viewBox="0 0 24 24" fill="currentColor">
         <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
      </svg>
    </a>

    <!-- PROFILE -->
    <a href="<?= $basePath ?><?= $dashLink ?>" class="nav-icon-circle profile-icon" title="My Account">
      <?= $profileInitial ?>
    </a>

  </div>

</nav>
