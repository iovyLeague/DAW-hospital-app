<?php
// lib/auth.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Inactivity timeout: 30 minutes
$now = time();
if (!empty($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    session_start();
    flash_set('Session expired due to inactivity. Please log in again.', 'error');
}
$_SESSION['last_activity'] = $now;


// Load current user if logged in
$current_user = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id,name,email,role,is_active FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}

function require_login() {
    global $current_user;
    if (!$current_user) redirect('login.php');
}

function require_role(string $role) {
    global $current_user;
    if (!$current_user || $current_user['role'] !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function require_any_role(array $roles) {
    global $current_user;
    if (!$current_user || !in_array($current_user['role'], $roles)) {
        http_response_code(403);
        exit('Forbidden');
    }
}
