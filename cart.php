<?php
require_once 'db/connection.php';

// Safe session check (though connection.php handles it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (empty($_SESSION['user'])) {
    // Not logged in? Redirect to login page
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Redirect to appropriate dashboard cart tab based on role
if ($user['role'] === 'customer') {
    header('Location: dashboard/user.php#cart');
} else {
    // Other roles don't have a cart, redirect to their dashboard
    $map = ['shop_owner' => 'dashboard/shop_owner.php', 'delivery_man' => 'dashboard/delivery.php'];
    header('Location: ' . ($map[$user['role']] ?? 'index.php'));
}
exit;
