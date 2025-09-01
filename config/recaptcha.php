<?php
// config/recaptcha.php
function verify_recaptcha(string $token): bool {
    $secret = $_ENV['RECAPTCHA_SECRET'] ?? '';
    if (!$secret) {
        // No secret configured — treat as passed for local dev
        return true;
    }
    $data = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    $opts = ['http' => [
        'method' => 'POST',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => $data,
        'timeout' => 5
    ]];
    $context = stream_context_create($opts);
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if (!$resp) return false;
    $json = json_decode($resp, true);
    return !empty($json['success']);
}
