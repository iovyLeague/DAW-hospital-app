<?php
// lib/helpers.php
function redirect(string $path) {
    if (str_starts_with($path, 'http')) {
        header("Location: $path"); exit;
    }
    $url = rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
    header("Location: $url"); exit;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function now(): string { return date('Y-m-d H:i:s'); }

function futureMinutes(int $m): string { return date('Y-m-d H:i:s', time() + $m*60); }

function ensure_post() { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); } }

function role_badge(string $role): string {
    $map = ['admin'=>'#d9534f','doctor'=>'#0275d8','patient'=>'#5cb85c'];
    $c = $map[$role] ?? '#777';
    return '<span class="badge" style="background:' . $c . ';color:#fff;padding:2px 6px;border-radius:6px;">' . h($role) . '</span>';
}

// crude flash using session
function flash_set(string $msg, string $type='info'){ $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }
function flash_get(){ if(isset($_SESSION['flash'])){$f=$_SESSION['flash']; unset($_SESSION['flash']); return $f;} return null; }
