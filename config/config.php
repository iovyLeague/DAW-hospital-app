<?php
// config/config.php

// Polyfills (safe for PHP 7/8)
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// Load .env into $_ENV (KEY=VALUE per line)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // strip inline comments like " ...  # note"
        $line = preg_replace('/\s+#.*$/', '', $line);
        $line = preg_replace('/\s+;.*$/', '', $line);
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        $_ENV[$k] = $v;
    }
}

// Helpers
function env($key, $default = null) {
    return array_key_exists($key, $_ENV) ? $_ENV[$key] : $default;
}
function envflag($key, $default = false) {
    $v = env($key, null);
    if ($v === null) return $default;
    $v = strtolower(trim($v));
    return in_array($v, ['1','true','yes','on'], true);
}

// SITE_URL
$base = env('SITE_URL', '');
if ($base === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $scheme . '://' . $host;
}
if (!preg_match('~^https?://~i', $base)) $base = 'https://' . ltrim($base, '/');
define('SITE_URL', rtrim($base, '/'));

// App mode flags
define('APP_OFFLINE', envflag('APP_OFFLINE', false));     // 1 = log to /storage instead of sending email
define('DEV_MODE',    envflag('DEV_MODE', false));

// Storage path (for offline logs)
define('STORAGE_PATH', realpath(__DIR__ . '/..') . '/storage');
if (!is_dir(STORAGE_PATH)) {
    @mkdir(STORAGE_PATH, 0777, true);
}

function recaptcha_verify(string $token): bool {
    $secret = $_ENV['RECAPTCHA_SECRET'] ?? '';
    if ($secret === '') return true; // if not configured, skip (dev-friendly)

    // Try file_get_contents first
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $opts = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $data, 'timeout' => 5]];
    $result = @file_get_contents($url, false, stream_context_create($opts));

    // Fallback to cURL if allow_url_fopen is off
    if ($result === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    if ($result === false) return false;
    $json = json_decode($result, true);
    return isset($json['success']) && $json['success'] === true;
}
