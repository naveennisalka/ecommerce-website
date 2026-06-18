<?php
require_once '../db/connection.php';
require_once '../db/demo_data.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = $_SESSION['user'] ?? null;

switch ($action) {

    /* ── GET SHOP PRODUCTS ───────────────────────────────── */
    case 'my_products':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) {
            jsonResponse(['success'=>true,'products'=>array_slice($DEMO_PRODUCTS,0,8)]);
        }
        $shop = $pdo->prepare("SELECT id FROM shops WHERE owner_id=? LIMIT 1");
        $shop->execute([$user['id']]);
        $shopRow = $shop->fetch();
        if (!$shopRow) jsonResponse(['success'=>true,'products'=>[]]);

        $stmt = $pdo->prepare("SELECT p.*,c.name AS cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.shop_id=? ORDER BY p.created_at DESC");
        $stmt->execute([$shopRow['id']]);
        jsonResponse(['success'=>true,'products'=>$stmt->fetchAll()]);
        break;

    /* ── ADD PRODUCT ─────────────────────────────────────── */
    case 'add_product':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) jsonResponse(['success'=>false,'message'=>'DB required.']);

        $shop = $pdo->prepare("SELECT id FROM shops WHERE owner_id=? LIMIT 1");
        $shop->execute([$user['id']]);
        $shopRow = $shop->fetch();
        if (!$shopRow) jsonResponse(['success'=>false,'message'=>'No shop found. Please create a shop.']);

        $name      = trim($_POST['name']      ?? '');
        $desc      = trim($_POST['description']?? '');
        $price     = (float)($_POST['price']  ?? 0);
        $origPrice = (float)($_POST['original_price'] ?? 0);
        $discount  = (int)($_POST['discount_percent'] ?? 0);
        $catId     = (int)($_POST['category_id'] ?? 0);
        $brandId   = (int)($_POST['brand_id'] ?? 0);
        $delivery  = $_POST['delivery_type'] ?? 'free';
        $stock     = (int)($_POST['stock'] ?? 100);
        $isNew     = isset($_POST['is_new']) ? 1 : 0;

        if (!$name || !$price) jsonResponse(['success'=>false,'message'=>'Name and price required.']);

        // Handle image upload
        $imageName = 'burger';
        if (!empty($_FILES['image']['tmp_name'])) {
            $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext,$allowed)) jsonResponse(['success'=>false,'message'=>'Invalid image type.']);
            $filename = 'product_'.time().'_'.rand(100,999).'.'.$ext;
            $dest = '../images/'.$filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $imageName = $filename;
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO products (shop_id,category_id,brand_id,name,description,price,original_price,discount_percent,is_new,delivery_type,stock,image)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $shopRow['id'], $catId ?: null, $brandId ?: null,
            $name,$desc,$price,$origPrice ?: null,$discount,$isNew,$delivery,$stock,$imageName
        ]);
        $pid = $pdo->lastInsertId();
        jsonResponse(['success'=>true,'product_id'=>$pid,'message'=>'Product added successfully!']);
        break;

    /* ── UPDATE PRODUCT ──────────────────────────────────── */
    case 'update_product':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) jsonResponse(['success'=>false,'message'=>'DB required.']);
        $pid = (int)($_POST['product_id'] ?? 0);
        $pdo->prepare("UPDATE products SET name=?,description=?,price=?,original_price=?,discount_percent=?,category_id=?,brand_id=?,delivery_type=?,stock=?,is_new=? WHERE id=?")
            ->execute([
                $_POST['name'],$_POST['description'],$_POST['price'],
                $_POST['original_price'] ?: null,$_POST['discount_percent'],
                $_POST['category_id'] ?: null,$_POST['brand_id'] ?: null,
                $_POST['delivery_type'],$_POST['stock'],isset($_POST['is_new'])?1:0,$pid
            ]);
        jsonResponse(['success'=>true,'message'=>'Product updated.']);
        break;

    /* ── DELETE PRODUCT ──────────────────────────────────── */
    case 'delete_product':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) jsonResponse(['success'=>false,'message'=>'DB required.']);
        $pid = (int)($_POST['product_id'] ?? 0);
        $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$pid]);
        jsonResponse(['success'=>true,'message'=>'Product removed.']);
        break;

    /* ── GET DELIVERY MEN (for assignment) ───────────────── */
    case 'delivery_men':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) {
            jsonResponse(['success'=>true,'delivery_men'=>[
                ['id'=>5,'name'=>'Amal Delivery','phone'=>'0756781234'],
                ['id'=>6,'name'=>'Saman Rider',  'phone'=>'0751239876'],
            ]]);
        }
        $stmt = $pdo->prepare("SELECT id,name,phone FROM users WHERE role='delivery_man' AND is_active=1 ORDER BY name");
        $stmt->execute();
        jsonResponse(['success'=>true,'delivery_men'=>$stmt->fetchAll()]);
        break;

    /* ── GET SHOP INFO ───────────────────────────────────── */
    case 'my_shop':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) jsonResponse(['success'=>true,'shop'=>['id'=>1,'name'=>'Demo Shop','address'=>'Colombo']]);
        $shop = $pdo->prepare("SELECT * FROM shops WHERE owner_id=? LIMIT 1");
        $shop->execute([$user['id']]);
        jsonResponse(['success'=>true,'shop'=>$shop->fetch()]);
        break;
}
