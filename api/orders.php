<?php
require_once '../db/connection.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = $_SESSION['user'] ?? null;

switch ($action) {

    /* ── PLACE ORDER ─────────────────────────────────────── */
    case 'place':
        if (!$user || $user['role'] !== 'customer') {
            jsonResponse(['success'=>false,'message'=>'Login as customer to place orders.']);
        }
        $address = trim($_POST['delivery_address'] ?? '');
        $notes   = trim($_POST['notes'] ?? '');
        $items   = json_decode($_POST['items'] ?? '[]', true);

        if (!$address || empty($items)) {
            jsonResponse(['success'=>false,'message'=>'Address and items are required.']);
        }
        if (!$USE_DB) {
            jsonResponse(['success'=>false,'message'=>'Database required to place orders.']);
        }

        // Verify products & calculate total
        $total  = 0;
        $shopId = null;
        $verified = [];
        foreach ($items as $item) {
            $pid = (int)$item['product_id'];
            $qty = max(1,(int)$item['quantity']);
            $p = $pdo->prepare("SELECT id,shop_id,name,price,stock FROM products WHERE id=? AND is_active=1");
            $p->execute([$pid]);
            $prod = $p->fetch();
            if (!$prod) continue;

            // Check if requested quantity exceeds available stock
            if ($qty > $prod['stock']) {
                $left = $prod['stock'] > 0 ? "Only {$prod['stock']} left" : "Out of stock";
                jsonResponse(['success'=>false,'message'=>"Sorry, '{$prod['name']}' is out of stock ({$left})."]);
            }

            if (!$shopId) $shopId = $prod['shop_id'];
            $total += $prod['price'] * $qty;
            $verified[] = array_merge($prod, ['quantity'=>$qty]);
        }
        if (!$shopId || empty($verified)) {
            jsonResponse(['success'=>false,'message'=>'No valid products found.']);
        }

        $pdo->beginTransaction();
        try {
            // Generate 4-digit PIN
            $deliveryPin = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            // Insert order
            $ord = $pdo->prepare(
                "INSERT INTO orders (user_id,shop_id,status,total_amount,delivery_address,customer_phone,notes,delivery_pin)
                 VALUES (?,?,'pending',?,?,?,?,?)"
            );
            $ord->execute([$user['id'],$shopId,$total,$address,$user['phone']??'',$notes,$deliveryPin]);
            $orderId = $pdo->lastInsertId();

            // Insert items
            $ii = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)");
            foreach ($verified as $v) {
                $ii->execute([$orderId,$v['id'],$v['quantity'],$v['price'],$v['price']*$v['quantity']]);
            }

            // Status history
            $hist = $pdo->prepare("INSERT INTO order_status_history (order_id,status,note,changed_by) VALUES (?,'pending','Order placed',?)");
            $hist->execute([$orderId,$user['id']]);

            // Notify shop owner
            $ownerQ = $pdo->prepare("SELECT owner_id FROM shops WHERE id=?");
            $ownerQ->execute([$shopId]);
            $owner = $ownerQ->fetch();
            if ($owner) {
                createNotification($pdo,$owner['owner_id'],
                    "🛒 New Order #$orderId",
                    "A new order was placed by {$user['name']} for Rs. ".number_format($total,2),
                    'order', $orderId);
            }

            $pdo->commit();
            jsonResponse(['success'=>true,'order_id'=>$orderId,'message'=>'Order placed successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['success'=>false,'message'=>'Order failed: '.$e->getMessage()]);
        }
        break;

    /* ── UPDATE STATUS (shop owner / delivery man / customer cancel) ───────── */
    case 'update_status':
        if (!$user) {
            jsonResponse(['success'=>false,'message'=>'Forbidden']);
        }
        $orderId   = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $note      = trim($_POST['note'] ?? '');
        $validStatuses = ['confirmed','preparing','picked_up','on_the_way','delivered','cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            jsonResponse(['success'=>false,'message'=>'Invalid status.']);
        }
        if (!$USE_DB) jsonResponse(['success'=>false,'message'=>'DB required.']);

        $ord = $pdo->prepare("SELECT * FROM orders WHERE id=?");
        $ord->execute([$orderId]);
        $order = $ord->fetch();
        if (!$order) jsonResponse(['success'=>false,'message'=>'Order not found.']);

        // Role based constraints
        if ($user['role'] === 'customer') {
            if ($order['user_id'] != $user['id'] || $order['status'] !== 'pending' || $newStatus !== 'cancelled') {
                jsonResponse(['success'=>false,'message'=>'You can only cancel your own pending orders.']);
            }
        } elseif (!in_array($user['role'], ['shop_owner','delivery_man'])) {
            jsonResponse(['success'=>false,'message'=>'Forbidden']);
        }

        // Verify PIN if marking as delivered
        if ($newStatus === 'delivered') {
            $submittedPin = trim($_POST['pin'] ?? '');
            if (empty($order['delivery_pin']) || $submittedPin !== $order['delivery_pin']) {
                jsonResponse(['success'=>false,'message'=>'Invalid Customer PIN.']);
            }
        }

        if ($newStatus === 'confirmed' && $order['status'] === 'pending') {
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id=?");
            $itemsStmt->execute([$orderId]);
            foreach ($itemsStmt->fetchAll() as $item) {
                $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id=?")->execute([$item['quantity'], $item['product_id']]);
            }
        }

        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$newStatus,$orderId]);
        $pdo->prepare("UPDATE delivery_assignments SET status=? WHERE order_id=?")->execute([$newStatus,$orderId]);
        $pdo->prepare("INSERT INTO order_status_history (order_id,status,note,changed_by) VALUES (?,?,?,?)")
            ->execute([$orderId,$newStatus,$note,$user['id']]);

        // Notify customer
        $msgs = [
            'confirmed'  => "✅ Your order #{$orderId} has been confirmed by the shop!",
            'preparing'  => "👨‍🍳 Your order #{$orderId} is being prepared.",
            'picked_up'  => "📦 Your order #{$orderId} has been picked up by the delivery rider.",
            'on_the_way' => "🛵 Your order #{$orderId} is on the way to you!",
            'delivered'  => "🎉 Your order #{$orderId} has been delivered. Enjoy your meal!",
            'cancelled'  => "❌ Your order #{$orderId} was cancelled.",
        ];
        $title = ucfirst(str_replace('_',' ',$newStatus));
        createNotification($pdo,$order['user_id'],"Order $title",$msgs[$newStatus]??$note,'order',$orderId);

        // If delivered, also notify shop owner
        if ($newStatus === 'delivered' && $user['role']==='delivery_man') {
            $ownerQ = $pdo->prepare("SELECT owner_id FROM shops WHERE id=?");
            $ownerQ->execute([$order['shop_id']]);
            $owner = $ownerQ->fetch();
            if ($owner) createNotification($pdo,$owner['owner_id'],"✅ Order #{$orderId} Delivered","Order #$orderId was successfully delivered.",'order',$orderId);
        }

        jsonResponse(['success'=>true,'message'=>"Status updated to $newStatus"]);
        break;

    /* ── ASSIGN DELIVERY MAN (shop owner) ───────────────── */
    case 'assign_delivery':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        $orderId      = (int)($_POST['order_id'] ?? 0);
        $deliveryManId= (int)($_POST['delivery_man_id'] ?? 0);
        if (!$USE_DB) jsonResponse(['success'=>false,'message'=>'DB required.']);

        $ord = $pdo->prepare("SELECT o.*,s.address AS shop_address,s.name AS shop_name FROM orders o JOIN shops s ON o.shop_id=s.id WHERE o.id=?");
        $ord->execute([$orderId]);
        $order = $ord->fetch();
        if (!$order) jsonResponse(['success'=>false,'message'=>'Order not found.']);

        $pdo->prepare("UPDATE orders SET delivery_man_id=?,status='confirmed' WHERE id=?")->execute([$deliveryManId,$orderId]);

        // Reduce stock if this is the first time confirming/assigning
        if ($order['status'] === 'pending') {
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id=?");
            $itemsStmt->execute([$orderId]);
            foreach ($itemsStmt->fetchAll() as $item) {
                $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id=?")->execute([$item['quantity'], $item['product_id']]);
            }
        }

        // Create delivery assignment
        $da = $pdo->prepare(
            "INSERT INTO delivery_assignments (order_id,delivery_man_id,pickup_address,drop_address,customer_name,customer_phone,package_price,return_address)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE delivery_man_id=?,pickup_address=?,drop_address=?"
        );
        $da->execute([
            $orderId,$deliveryManId,
            $order['shop_address'],$order['delivery_address'],
            $order['customer_phone'] ? 'Customer' : '',
            $order['customer_phone'],$order['total_amount'],
            $order['shop_address'],
            $deliveryManId,$order['shop_address'],$order['delivery_address'],
        ]);

        // Get customer name
        $custQ = $pdo->prepare("SELECT name FROM users WHERE id=?");
        $custQ->execute([$order['user_id']]);
        $cust = $custQ->fetch();

        // Update assignment with customer name
        $pdo->prepare("UPDATE delivery_assignments SET customer_name=? WHERE order_id=?")
            ->execute([$cust['name']??'Customer',$orderId]);

        // Notify delivery man
        createNotification($pdo,$deliveryManId,
            "📦 New Delivery Assignment",
            "You have been assigned to deliver order #{$orderId} from {$order['shop_name']}. Pickup: {$order['shop_address']}",
            'delivery',$orderId);

        // Notify customer
        createNotification($pdo,$order['user_id'],
            "✅ Order #{$orderId} Confirmed",
            "Your order has been confirmed and a delivery rider has been assigned!",
            'order',$orderId);

        // Add to history
        $pdo->prepare("INSERT INTO order_status_history (order_id,status,note,changed_by) VALUES (?,'confirmed','Delivery man assigned',?)")
            ->execute([$orderId,$user['id']]);

        jsonResponse(['success'=>true,'message'=>'Delivery man assigned successfully.']);
        break;

    /* ── GET MY ORDERS (customer) ────────────────────────── */
    case 'my_orders':
        if (!$user) jsonResponse(['success'=>false,'message'=>'Login required.']);
        if (!$USE_DB) jsonResponse(['success'=>true,'orders'=>[]]);
        $stmt = $pdo->prepare(
            "SELECT o.*,s.name AS shop_name FROM orders o
             JOIN shops s ON o.shop_id=s.id
             WHERE o.user_id=? ORDER BY o.created_at DESC"
        );
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $items = $pdo->prepare("SELECT oi.*,p.name,p.image FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
            $items->execute([$order['id']]);
            $order['items'] = $items->fetchAll();
            $hist = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id=? ORDER BY created_at ASC");
            $hist->execute([$order['id']]);
            $order['history'] = $hist->fetchAll();
        }
        jsonResponse(['success'=>true,'orders'=>$orders]);
        break;

    /* ── GET SHOP ORDERS (shop owner) ────────────────────── */
    case 'shop_orders':
        if (!$user || $user['role']!=='shop_owner') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) jsonResponse(['success'=>true,'orders'=>[]]);
        $shop = $pdo->prepare("SELECT id FROM shops WHERE owner_id=? LIMIT 1");
        $shop->execute([$user['id']]);
        $shopRow = $shop->fetch();
        if (!$shopRow) jsonResponse(['success'=>true,'orders'=>[]]);
        $stmt = $pdo->prepare(
            "SELECT o.*,u.name AS customer_name,u.phone AS customer_phone,
                    dm.name AS delivery_man_name
             FROM orders o
             JOIN users u ON o.user_id=u.id
             LEFT JOIN users dm ON o.delivery_man_id=dm.id
             WHERE o.shop_id=? ORDER BY o.created_at DESC"
        );
        $stmt->execute([$shopRow['id']]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $items = $pdo->prepare("SELECT oi.*,p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
            $items->execute([$order['id']]);
            $order['items'] = $items->fetchAll();
        }
        jsonResponse(['success'=>true,'orders'=>$orders]);
        break;

    /* ── GET DELIVERY ORDERS (delivery man) ─────────────── */
    case 'delivery_orders':
        if (!$user || $user['role']!=='delivery_man') jsonResponse(['success'=>false,'message'=>'Forbidden']);
        if (!$USE_DB) jsonResponse(['success'=>true,'orders'=>[]]);
        $stmt = $pdo->prepare(
            "SELECT o.*, da.pickup_address, da.drop_address, da.assigned_at,
                    u.name AS customer_name,u.phone AS cust_phone,s.name AS shop_name
             FROM orders o
             JOIN delivery_assignments da ON o.id=da.order_id
             JOIN users u ON o.user_id=u.id
             JOIN shops s ON o.shop_id=s.id
             WHERE o.delivery_man_id=? ORDER BY da.assigned_at DESC"
        );
        $stmt->execute([$user['id']]);
        jsonResponse(['success'=>true,'orders'=>$stmt->fetchAll()]);
        break;
}
