<?php
// ── SESSION (safe start — only if not already active) ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── DATABASE CONFIG ──
define('DB_HOST', 'localhost');
define('DB_NAME', 'eatlink_db');
define('DB_USER', 'root');
define('DB_PASS', '');

$pdo    = null;
$USE_DB = false;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $USE_DB = true;
} catch (PDOException $e) {
    $USE_DB = false;
}

// ── HELPERS ──────────────────────────────────────────────

/**
 * Send a JSON response and exit.
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Require the user to be logged in (any role).
 */
function requireAuth(): array {
    if (empty($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
    return $_SESSION['user'];
}

/**
 * Require a specific role.
 */
function requireRole(string ...$roles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $roles)) {
        jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
    }
    return $user;
}

/**
 * Create a notification for a user.
 */
function createNotification(PDO $pdo, int $userId, string $title, string $message, string $type = 'order', ?int $orderId = null): void {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, title, message, type, order_id) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $title, $message, $type, $orderId]);
    } catch (PDOException $e) {
        // Silently fail — notifications should not break main flow
    }
}
