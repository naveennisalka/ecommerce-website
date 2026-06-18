<?php
require_once '../db/connection.php';
require_once '../db/demo_data.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : '';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$user = $_SESSION['user'] ?? null;

switch ($action) {
    case 'add':
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($pid > 0) {
            // Update session
            if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid] += $qty;
            else $_SESSION['cart'][$pid] = $qty;

            // Update DB if logged in
            if ($USE_DB && $user) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->execute([$user['id'], $pid, $qty, $qty]);
            }
            
            $count = getCartCount($USE_DB, $pdo, $user);
            echo json_encode(['success' => true, 'count' => $count, 'message' => 'Added to cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
        }
        break;

    case 'remove':
        $pid = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$pid])) unset($_SESSION['cart'][$pid]);
        if ($USE_DB && $user) {
            $pdo->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$user['id'], $pid]);
        }
        echo json_encode(['success' => true, 'count' => getCartCount($USE_DB, $pdo, $user)]);
        break;

    case 'update':
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$pid]);
            if ($USE_DB && $user) $pdo->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$user['id'], $pid]);
        } else {
            $_SESSION['cart'][$pid] = $qty;
            if ($USE_DB && $user) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = ?");
                $stmt->execute([$user['id'], $pid, $qty, $qty]);
            }
        }
        echo json_encode(['success' => true, 'count' => getCartCount($USE_DB, $pdo, $user)]);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        if ($USE_DB && $user) {
            $pdo->prepare("DELETE FROM cart WHERE user_id=?")->execute([$user['id']]);
        }
        echo json_encode(['success' => true, 'count' => 0]);
        break;

    default:
        // GET - return cart items
        $cartItems = [];
        $total = 0;

        if ($USE_DB && $user) {
            // Get from DB
            $cStmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
            $cStmt->execute([$user['id']]);
            $rows = $cStmt->fetchAll();
            foreach ($rows as $row) {
                $cartItems[] = [
                    'product_id' => $row['product_id'],
                    'quantity'   => $row['quantity'],
                    'name'       => $row['name'],
                    'price'      => $row['price'],
                    'image'      => $row['image'],
                    'subtotal'   => $row['price'] * $row['quantity'],
                ];
                $total += $row['price'] * $row['quantity'];
            }
        } else {
            // Get from Session
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $product = null;
                if ($USE_DB) {
                    $pStmt = $pdo->prepare("SELECT name, price, image FROM products WHERE id=?");
                    $pStmt->execute([$pid]);
                    $product = $pStmt->fetch();
                } else {
                    foreach ($DEMO_PRODUCTS as $p) {
                        if ($p['id'] == $pid) { $product = $p; break; }
                    }
                }
                if ($product) {
                    $cartItems[] = [
                        'product_id' => $pid,
                        'quantity'   => $qty,
                        'name'       => $product['name'],
                        'price'      => $product['price'],
                        'image'      => $product['image'],
                        'subtotal'   => $product['price'] * $qty,
                    ];
                    $total += $product['price'] * $qty;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'items' => $cartItems,
            'count' => getCartCount($USE_DB, $pdo, $user),
            'total' => $total
        ]);
        break;
}

function getCartCount($USE_DB, $pdo, $user) {
    if ($USE_DB && $user) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id=?");
        $stmt->execute([$user['id']]);
        return (int)$stmt->fetchColumn();
    }
    return array_sum($_SESSION['cart'] ?? []);
}
