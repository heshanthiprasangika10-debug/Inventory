<?php
require_once __DIR__ . '/../config.php'; login_required();
$f = flash(); $cur = basename($_SERVER['SCRIPT_NAME']);
$nav = [
  'dashboard.php' => ['📊', 'Dashboard'], 'products.php' => ['📦', 'Products'], 'stock.php' => ['🔄', 'Stock Movements'],
  'manage.php?type=categories' => ['🏷️', 'Categories'], 'manage.php?type=suppliers' => ['🚚', 'Suppliers'],
];
$curKey = $cur === 'manage.php' ? 'manage.php?type=' . ($_GET['type'] ?? 'categories') : $cur;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'StockPro') ?> · StockPro</title><link rel="stylesheet" href="css/style.css"></head><body>
<aside class="sidebar">
  <div class="logo">📦 StockPro</div>
  <?php foreach ($nav as $href => [$icon, $label]): ?>
    <a href="<?= $href ?>" class="<?= $curKey === $href ? 'active' : '' ?>"><span><?= $icon ?></span><?= $label ?></a>
  <?php endforeach; ?>
  <a href="export.php"><span>⬇️</span>Export CSV</a>
  <div class="user"><div class="avatar"><?= e(strtoupper(substr($_SESSION['user']['name'], 0, 1))) ?></div>
    <div><?= e($_SESSION['user']['name']) ?><br><a href="logout.php">Logout</a></div></div>
</aside>
<main class="content">
<?php if ($f): ?><div class="alert <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif; ?>
