<?php
require_once '../db/connection.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ── REGISTER ─────────────────────────────────────────── */
    case 'register':
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $role     = trim($_POST['role']     ?? 'customer');
        $address  = trim($_POST['address']  ?? '');
        $shopName = trim($_POST['shop_name'] ?? '');

        if (!$name || !$email || !$password) {
            jsonResponse(['success' => false, 'message' => 'Name, email and password are required.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
        }
        if (strlen($password) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        }
        if (!in_array($role, ['customer','shop_owner','delivery_man'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid role.']);
        }

        if ($USE_DB) {
            // Check email exists
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Email already registered.']);
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (name,email,password,phone,role,address) VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$name, $email, $hash, $phone, $role, $address]);
            $userId = $pdo->lastInsertId();

            // Create shop for shop_owner
            if ($role === 'shop_owner' && $shopName) {
                $s = $pdo->prepare("INSERT INTO shops (owner_id,name,address,phone) VALUES (?,?,?,?)");
                $s->execute([$userId, $shopName, $address, $phone]);
            }

            $_SESSION['user'] = [
                'id' => $userId, 'name' => $name,
                'email' => $email, 'role' => $role, 'phone' => $phone,
            ];
            jsonResponse(['success' => true, 'role' => $role, 'name' => $name]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Database not connected. Please set up MySQL.']);
        }
        break;

    /* ── LOGIN ────────────────────────────────────────────── */
    case 'login':
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = trim($_POST['role']     ?? 'customer');

        if (!$email || !$password) {
            jsonResponse(['success' => false, 'message' => 'Email and password are required.']);
        }

        if ($USE_DB) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
            }
            if ($user['role'] !== $role) {
                jsonResponse(['success' => false, 'message' => "This account is registered as '{$user['role']}', not '$role'."]);
            }

            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'phone' => $user['phone'],
            ];
            jsonResponse(['success' => true, 'role' => $user['role'], 'name' => $user['name']]);
        } else {
            // Demo login (no DB)
            $demoUsers = [
                ['email'=>'kumara@example.com',  'password'=>'password123','role'=>'customer',     'name'=>'Kumara Chathuranga','id'=>1,'phone'=>'0771234567'],
                ['email'=>'suresh@pizzahut.lk',  'password'=>'password123','role'=>'shop_owner',   'name'=>'Suresh Pizza Hut',  'id'=>3,'phone'=>'0112345678'],
                ['email'=>'amal@delivery.lk',    'password'=>'password123','role'=>'delivery_man', 'name'=>'Amal Delivery',     'id'=>5,'phone'=>'0756781234'],
            ];
            foreach ($demoUsers as $u) {
                if ($u['email'] === $email && $u['password'] === $password && $u['role'] === $role) {
                    $_SESSION['user'] = $u;
                    jsonResponse(['success' => true, 'role' => $u['role'], 'name' => $u['name']]);
                }
            }
            jsonResponse(['success' => false, 'message' => 'Invalid credentials or role.']);
        }
        break;

    /* ── LOGOUT ───────────────────────────────────────────── */
    case 'logout':
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    /* ── CHECK SESSION ────────────────────────────────────── */
    case 'me':
        if (!empty($_SESSION['user'])) {
            jsonResponse(['success' => true, 'user' => $_SESSION['user']]);
        }
        jsonResponse(['success' => false, 'user' => null]);
        break;
}
