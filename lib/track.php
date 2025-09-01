<?php

require_once __DIR__ . '/../config/db.php';

try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
    $path = substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), 0, 250);
    $stmt = $pdo->prepare("INSERT INTO stats (ip, ua, path) VALUES (?,?,?)");
    $stmt->execute([$ip, $ua, $path]);
} catch (Throwable $e) {
    echo "". $e->getMessage() ."";
}
