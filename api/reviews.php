<?php
require_once '../db/connection.php';
require_once '../db/demo_data.php';

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

switch ($action) {

    /* ── SUBMIT REVIEW ───────────────────────────────────── */
    case 'submit':
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['role'] !== 'customer') {
            jsonResponse(['success'=>false,'message'=>'Login as customer to review.']);
        }
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            jsonResponse(['success'=>false,'message'=>'Rating must be between 1 and 5.']);
        }
        if (!$USE_DB) jsonResponse(['success'=>false,'message'=>'DB required.']);

        // Prevent duplicate review for same user+product
        $chk = $pdo->prepare("SELECT id FROM reviews WHERE user_id=? AND product_id=?");
        $chk->execute([$user['id'],$productId]);
        if ($chk->fetch()) {
            jsonResponse(['success'=>false,'message'=>'You already reviewed this product.']);
        }

        $stmt = $pdo->prepare("INSERT INTO reviews (user_id,product_id,order_id,rating,comment) VALUES (?,?,?,?,?)");
        $stmt->execute([$user['id'],$productId,$orderId ?: null,$rating,$comment]);
        jsonResponse(['success'=>true,'message'=>'Review submitted!']);
        break;

    /* ── GET REVIEWS ─────────────────────────────────────── */
    default:
        if ($USE_DB && $productId) {
            $stmt = $pdo->prepare(
                "SELECT r.*,u.name AS reviewer_name
                 FROM reviews r JOIN users u ON r.user_id=u.id
                 WHERE r.product_id=? ORDER BY r.created_at DESC"
            );
            $stmt->execute([$productId]);
            $reviews = $stmt->fetchAll();

            $avgQ = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id=?");
            $avgQ->execute([$productId]);
            $ratingData = $avgQ->fetch();
        } else {
            // Demo reviews
            $demoReviews = [
                ['id'=>1,'reviewer_name'=>'Kumara C','rating'=>4,'comment'=>'Bring the steakhouse experience home with our ultra-juicy beef patties. Crafted from premium, 100% all-natural beef with the perfect fat-to-lean ratio, these patties are amazing.','created_at'=>'2026-06-10 14:22:00'],
                ['id'=>2,'reviewer_name'=>'Kumara C','rating'=>4,'comment'=>'Bring the steakhouse experience home with our ultra-juicy beef patties. Crafted from premium, 100% all-natural beef with the perfect fat-to-lean ratio, these patties are great.','created_at'=>'2026-06-12 10:15:00'],
            ];
            $reviews    = $demoReviews;
            $ratingData = ['avg_rating'=>4.6,'total'=>59];
        }

        jsonResponse([
            'success'    => true,
            'reviews'    => $reviews,
            'avg_rating' => round((float)($ratingData['avg_rating'] ?? 0), 1),
            'total'      => (int)($ratingData['total'] ?? 0),
        ]);
        break;
}
