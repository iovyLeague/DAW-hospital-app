<?php
require_once __DIR__ . '/lib/auth.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!$token || !$email) { flash_set('Invalid verification link', 'error'); redirect('index.php'); }

$stmt = $pdo->prepare("SELECT id,verification_expires FROM users WHERE email=? AND verification_token=? AND is_active=0");
$stmt->execute([$email,$token]);
$user = $stmt->fetch();

if (!$user) { flash_set('Invalid or already used token', 'error'); redirect('index.php'); }

if (strtotime($user['verification_expires']) < time()) {
    flash_set('Verification link expired', 'error'); redirect('index.php');
}

$upd = $pdo->prepare("UPDATE users SET is_active=1,email_verified_at=?,verification_token=NULL,verification_expires=NULL WHERE id=?");
$upd->execute([date('Y-m-d H:i:s'), $user['id']]);

flash_set('Email verified! You can log in now.');
redirect('login.php');
