<?php
require_once '../db/connection.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';
$user   = $_SESSION['user'] ?? null;
if (!$user) jsonResponse(['success'=>false,'notifications'=>[],'unread'=>0]);

switch ($action) {
    case 'get':
        if (!$USE_DB) { jsonResponse(['success'=>true,'notifications'=>[],'unread'=>0]); }
        $stmt = $pdo->prepare(
            "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30"
        );
        $stmt->execute([$user['id']]);
        $notifs = $stmt->fetchAll();
        $unread = count(array_filter($notifs, fn($n) => !$n['is_read']));
        jsonResponse(['success'=>true,'notifications'=>$notifs,'unread'=>$unread]);
        break;

    case 'mark_read':
        if (!$USE_DB) jsonResponse(['success'=>true]);
        $nid = (int)($_POST['id'] ?? 0);
        if ($nid) {
            $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$nid,$user['id']]);
        } else {
            $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
        }
        jsonResponse(['success'=>true]);
        break;

    case 'unread_count':
        if (!$USE_DB) jsonResponse(['success'=>true,'count'=>0]);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->execute([$user['id']]);
        jsonResponse(['success'=>true,'count'=>(int)$stmt->fetch()['c']]);
        break;
}
