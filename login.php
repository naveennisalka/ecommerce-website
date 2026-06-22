<?php
require_once 'db/connection.php';
// Redirect if already logged in
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
  <title>Login — EatLink</title>
  <meta name="description" content="Login to your EatLink account. Customer, Shop Owner, or Delivery Man.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/auth.css">
  <style>body{padding-top:0;}</style>
</head>
<body class="auth-page">

<a href="index.php" class="back-to-home">← Back to Home</a>

<div class="auth-card">

  <!-- LOGO -->
  <a href="index.php" class="auth-logo" style="display:flex;justify-content:center;margin-bottom:20px;">
    <img src="images/Logo.png" alt="EatLink Logo" style="max-height: 40px; max-width: 100%;">
  </a>

  <h1 class="auth-heading">Welcome back!</h1>
  <p class="auth-subheading">Sign in to continue to your account</p>

  <!-- ALERT -->
  <div class="auth-alert" id="auth-alert"></div>

  <!-- ROLE TABS -->
  <div class="role-tabs" role="tablist" aria-label="Select your role">
    <button class="role-tab active" data-role="customer" type="button" role="tab">
      <span class="role-icon"><span class="material-symbols-outlined">person</span></span>
      <span class="role-label">Customer</span>
    </button>
    <button class="role-tab" data-role="shop_owner" type="button" role="tab">
      <span class="role-icon"><span class="material-symbols-outlined">storefront</span></span>
      <span class="role-label">Shop Owner</span>
    </button>
    <button class="role-tab" data-role="delivery_man" type="button" role="tab">
      <span class="role-icon"><span class="material-symbols-outlined">two_wheeler</span></span>
      <span class="role-label">Delivery Man</span>
    </button>
  </div>

  <!-- FORM -->
  <form class="auth-form" id="auth-form" data-action="login" novalidate>
    <input type="hidden" name="role" id="role-input" value="customer">

    <div class="form-group">
      <label class="form-label" for="email">Email Address</label>
      <input type="email" class="form-input" id="email" name="email"
             placeholder="you@example.com" required autocomplete="email">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <div class="input-wrap">
        <input type="password" class="form-input" id="password" name="password"
               placeholder="Your password" required autocomplete="current-password">
        <button type="button" class="toggle-pass" aria-label="Toggle password">👁️</button>
      </div>
    </div>

    <button type="submit" class="auth-submit" id="auth-submit">
      Sign In →
    </button>
  </form>

  <p class="auth-switch" style="margin-top:20px;">
    Don't have an account? <a href="register.php">Create one</a>
  </p>

  <!-- DEMO CREDENTIALS -->
  <?php if (!$USE_DB): ?>
  <div style="margin-top:20px;padding:14px;background:#FFF8F0;border-radius:12px;border:1px dashed #FF6B00;">
    <p style="font-size:.75rem;font-weight:700;color:#FF6B00;margin-bottom:8px;">⚠️ Demo Mode (No DB) — Try:</p>
    <p style="font-size:.72rem;color:#555;line-height:1.6;">
      Customer: <b>kumara@example.com</b> / password123<br>
      Shop Owner: <b>suresh@pizzahut.lk</b> / password123<br>
      Delivery: <b>amal@delivery.lk</b> / password123
    </p>
  </div>
  <?php endif; ?>

</div>

<script src="js/auth.js"></script>
</body>
</html>
