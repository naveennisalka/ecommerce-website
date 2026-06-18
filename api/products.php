<?php
// session is handled by connection.php
require_once '../db/connection.php';
require_once '../db/demo_data.php';

// Prevent any stray output before JSON
header('Content-Type: application/json; charset=utf-8');

// Parse query params
$brandId    = isset($_GET['brand'])     ? (int)$_GET['brand']     : 0;
$categoryId = isset($_GET['category'])  ? (int)$_GET['category']  : 0;
$priceMin   = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$priceMax   = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 999999;
$sort       = $_GET['sort']     ?? 'default';
$delivery   = $_GET['delivery'] ?? '';
$search     = trim($_GET['search'] ?? '');
$page       = max(1, (int)($_GET['page']     ?? 1));
$perPage    = max(1, (int)($_GET['per_page'] ?? 16));

// ── SOURCE: DB or Demo ──
if ($USE_DB) {
    // Build SQL
    $where  = ['p.is_active = 1'];
    $params = [];

    if ($brandId    > 0) { $where[] = 'p.brand_id = ?';    $params[] = $brandId; }
    if ($categoryId > 0) { $where[] = 'p.category_id = ?'; $params[] = $categoryId; }
    if ($priceMin   > 0) { $where[] = 'p.price >= ?';      $params[] = $priceMin; }
    if ($priceMax < 999999) { $where[] = 'p.price <= ?';   $params[] = $priceMax; }
    if ($delivery === 'free') { $where[] = "p.delivery_type = 'free'"; }
    if ($delivery === 'paid') { $where[] = "p.delivery_type = 'paid'"; }
    if ($search !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$search%"; }

    $orderBy = match($sort) {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'newest'     => 'p.created_at DESC',
        'discount'   => 'p.discount_percent DESC',
        default      => 'p.id ASC',
    };

    $whereSQL = implode(' AND ', $where);

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch page
    $offset = ($page - 1) * $perPage;
    $stmt   = $pdo->prepare(
        "SELECT p.*, b.name AS brand_name, c.name AS category_name
         FROM products p
         LEFT JOIN brands b    ON p.brand_id    = b.id
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE $whereSQL
         ORDER BY $orderBy
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Brands & categories for filter
    $brands     = $pdo->query("SELECT * FROM brands ORDER BY name")->fetchAll();
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

} else {
    // ── DEMO DATA fallback ──
    $products = $DEMO_PRODUCTS;

    if ($brandId    > 0) { $products = array_filter($products, fn($p) => $p['brand_id']    === $brandId); }
    if ($categoryId > 0) { $products = array_filter($products, fn($p) => $p['category_id'] === $categoryId); }
    if ($priceMin   > 0) { $products = array_filter($products, fn($p) => $p['price'] >= $priceMin); }
    if ($priceMax < 999999) { $products = array_filter($products, fn($p) => $p['price'] <= $priceMax); }
    if ($delivery === 'free') { $products = array_filter($products, fn($p) => $p['delivery_type'] === 'free'); }
    if ($delivery === 'paid') { $products = array_filter($products, fn($p) => $p['delivery_type'] === 'paid'); }
    if ($search !== '') { $products = array_filter($products, fn($p) => stripos($p['name'], $search) !== false); }

    $products = array_values($products);

    usort($products, function ($a, $b) use ($sort) {
        return match($sort) {
            'price_asc'  => $a['price']            - $b['price'],
            'price_desc' => $b['price']            - $a['price'],
            'newest'     => $b['id']               - $a['id'],
            'discount'   => $b['discount_percent'] - $a['discount_percent'],
            default      => $a['id']               - $b['id'],
        };
    });

    $total = count($products);
    $products = array_slice($products, ($page - 1) * $perPage, $perPage);

    // Attach names
    $brandMap = array_column($DEMO_BRANDS, null, 'id');
    $catMap   = array_column($DEMO_CATEGORIES, null, 'id');
    foreach ($products as &$p) {
        $p['brand_name']    = $brandMap[$p['brand_id']]['name']    ?? '';
        $p['category_name'] = $catMap[$p['category_id']]['name']   ?? '';
    }
    unset($p);

    $brands     = $DEMO_BRANDS;
    $categories = $DEMO_CATEGORIES;
}

$wishlist   = $_SESSION['wishlist'] ?? [];
$totalPages = $total > 0 ? (int)ceil($total / $perPage) : 0;

echo json_encode([
    'success'     => true,
    'products'    => array_values($products),
    'total'       => $total,
    'page'        => $page,
    'per_page'    => $perPage,
    'total_pages' => $totalPages,
    'brands'      => array_values($brands),
    'categories'  => array_values($categories),
    'wishlist'    => $wishlist,
], JSON_UNESCAPED_UNICODE);
