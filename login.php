<?php
require 'config.php';
$first = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() == 0; // first run => create admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email']); $pw = $_POST['password'];
    if ($first) {
        $name = trim($_POST['name']);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) {
            flash('Valid name, email and a 6+ character password are required.', 'err');
        } else {
            $pdo->prepare('INSERT INTO users(name,email,password) VALUES(?,?,?)')->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            flash('Admin account created. Please sign in.'); header('Location: login.php'); exit;
        }
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE email=?'); $st->execute([$email]); $u = $st->fetch();
        if ($u && password_verify($pw, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name']];
            header('Location: dashboard.php'); exit;
        }
        flash('Invalid email or password.', 'err');
    }
}
$f = flash();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · StockPro</title><link rel="stylesheet" href="css/style.css"></head>
<body class="login-page">
<div class="login-card">
  <div class="logo">📦 StockPro</div>
  <p class="muted"><?= $first ? 'Welcome! Create your admin account to get started.' : 'Sign in to manage your inventory.' ?></p>
  <?php if ($f): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <?php if ($first): ?><label>Full name</label><input name="name" required><?php endif; ?>
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <button class="btn primary block"><?= $first ? 'Create admin account' : 'Sign in' ?></button>
  </form>
</div></body></html>
