<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=inventory_db;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n) { return 'Rs. ' . number_format((float)$n, 2); }
function login_required() { if (empty($_SESSION['user'])) { header('Location: login.php'); exit; } }
function flash($msg = null, $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf_field() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request'); }
}
function stock_badge($qty, $reorder) {
    if ($qty <= 0) return '<span class="badge red">Out of stock</span>';
    if ($qty <= $reorder) return '<span class="badge amber">Low stock</span>';
    return '<span class="badge green">In stock</span>';
}
