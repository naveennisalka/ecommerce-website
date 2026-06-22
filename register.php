<?php
require_once 'db/connection.php';
if (!empty($_SESSION['user'])) {
    $map = ['customer'=>'dashboard/user.php','shop_owner'=>'dashboard/shop_owner.php','delivery_man'=>'dashboard/delivery.php'];
    header('Location: '.($map[$_SESSION['user']['role']] ?? 'index.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="images/logo2.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — EatLink</title>
  <meta name="description" content="Create your EatLink account as a Customer, Shop Owner, or Delivery Man.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/auth.css">
  <style>body{padding-top:0;}</style>
</head>
<body class="auth-page">

<a href="index.php" class="back-to-home">← Back to Home</a>

<div class="auth-card" style="max-width:520px;">

  <!-- LOGO -->
  <a href="index.php" class="auth-logo" style="display:flex;justify-content:center;margin-bottom:20px;">
    <img src="images/Logo.png" alt="EatLink Logo" style="max-height: 40px; max-width: 100%;">
  </a>

  <h1 class="auth-heading">Create Account</h1>
  <p class="auth-subheading">Join EatLink — choose your role to get started</p>

  <!-- ALERT -->
  <div class="auth-alert" id="auth-alert"></div>

  <!-- ROLE TABS -->
  <div class="role-tabs" role="tablist">
    <button class="role-tab active" data-role="customer" type="button">
      <span class="role-icon"><span class="material-symbols-outlined">person</span></span>
      <span class="role-label">Customer</span>
    </button>
    <button class="role-tab" data-role="shop_owner" type="button">
      <span class="role-icon"><span class="material-symbols-outlined">storefront</span></span>
      <span class="role-label">Shop Owner</span>
    </button>
    <button class="role-tab" data-role="delivery_man" type="button">
      <span class="role-icon"><span class="material-symbols-outlined">two_wheeler</span></span>
      <span class="role-label">Delivery Man</span>
    </button>
  </div>

  <!-- FORM -->
  <form class="auth-form" id="auth-form" data-action="register" novalidate>
    <input type="hidden" name="role" id="role-input" value="customer">

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input type="text" class="form-input" id="name" name="name" placeholder="Your full name" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="phone">Phone Number</label>
        <input type="tel" class="form-input" id="phone" name="phone" placeholder="07X XXXXXXX">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="email">Email Address</label>
      <input type="email" class="form-input" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
    </div>

    <div class="form-group">
      <label class="form-label" for="address">Address</label>
      <input type="text" class="form-input" id="address" name="address" placeholder="Your delivery address">
    </div>

    <!-- SHOP NAME (only for shop owners) -->
    <div class="form-group" id="shop-name-group" style="display:none;">
      <label class="form-label" for="shop_name">Shop / Restaurant Name</label>
      <input type="text" class="form-input" id="shop_name" name="shop_name" placeholder="e.g. Pizza Palace">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <div class="input-wrap">
        <input type="password" class="form-input" id="password" name="password"
               placeholder="Min. 6 characters" required autocomplete="new-password">
        <button type="button" class="toggle-pass" aria-label="Toggle password">👁️</button>
      </div>
    </div>

    <button type="submit" class="auth-submit" id="auth-submit">
      Create Account →
    </button>
  </form>

  <p class="auth-switch" style="margin-top:20px;">
    Already have an account? <a href="login.php">Sign in</a>
  </p>

  <?php if (!$USE_DB): ?>
  <div style="margin-top:16px;padding:12px 14px;background:#FFF0F0;border-radius:12px;border:1px dashed #E8001C;">
    <p style="font-size:.75rem;color:#C0392B;">⚠️ Database not connected. Registration requires MySQL. Please set up <code>eatlink_db</code> using <code>db/schema.sql</code>.</p>
  </div>
  <?php endif; ?>

</div>

<script src="js/auth.js"></script>
</body>
</html>
