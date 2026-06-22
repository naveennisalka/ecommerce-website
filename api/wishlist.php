<?php
require_once '../db/connection.php';
require_once '../db/demo_data.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : '';
$user = $_SESSION['user'] ?? null;

switch ($action) {
    case 'toggle':
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid > 0) {
            $key = array_search($pid, $_SESSION['wishlist']);
            if ($key !== false) {
                array_splice($_SESSION['wishlist'], $key, 1);
                $wishlisted = false;
                if ($USE_DB && $user) {
                    $pdo->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?")->execute([$user['id'], $pid]);
                }
            } else {
                $_SESSION['wishlist'][] = $pid;
                $wishlisted = true;
                if ($USE_DB && $user) {
                    $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user['id'], $pid]);
                }
            }
            echo json_encode(['success' => true, 'wishlisted' => $wishlisted, 'count' => count($_SESSION['wishlist'])]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    default:
        // GET - return wishlist
        if ($USE_DB && $user) {
            $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id=?");
            $stmt->execute([$user['id']]);
            $dbWishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $_SESSION['wishlist'] = array_unique(array_merge($_SESSION['wishlist'], $dbWishlist));
        }

        $wishlistItems = [];
        foreach ($_SESSION['wishlist'] as $pid) {
            foreach ($DEMO_PRODUCTS as $p) {
                if ($p['id'] == $pid) { $wishlistItems[] = $p; break; }
            }
        }
        echo json_encode(['success' => true, 'items' => $wishlistItems, 'ids' => $_SESSION['wishlist']]);
        break;
}
