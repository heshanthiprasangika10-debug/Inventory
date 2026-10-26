<?php
$title = 'Dashboard'; require 'includes/header.php';
$v = fn($sql) => $pdo->query($sql)->fetchColumn();
$totalProducts = $v('SELECT COUNT(*) FROM products');
$stockValue    = $v('SELECT COALESCE(SUM(quantity*cost_price),0) FROM products');
$lowCount      = $v('SELECT COUNT(*) FROM products WHERE quantity>0 AND quantity<=reorder_level');
$outCount      = $v('SELECT COUNT(*) FROM products WHERE quantity=0');

$cat = $pdo->query("SELECT COALESCE(c.name,'Uncategorized') n, SUM(p.quantity*p.cost_price) v FROM products p LEFT JOIN categories c ON c.id=p.category_id GROUP BY n")->fetchAll();

$days = []; for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = ['IN' => 0, 'OUT' => 0];
foreach ($pdo->query("SELECT DATE(created_at) d, type, SUM(quantity) q FROM stock_movements WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY d, type") as $r)
    if (isset($days[$r['d']])) $days[$r['d']][$r['type']] = (int)$r['q'];

$alerts = $pdo->query('SELECT * FROM products WHERE quantity<=reorder_level ORDER BY quantity LIMIT 6')->fetchAll();
$recent = $pdo->query('SELECT m.*, p.name FROM stock_movements m JOIN products p ON p.id=m.product_id ORDER BY m.id DESC LIMIT 6')->fetchAll();
?>
<div class="page-head"><div><h1>Dashboard</h1><p class="muted">Overview of your inventory today.</p></div>
<a class="btn primary" href="stock.php">+ Record Stock Movement</a></div>

<div class="stats">
  <div class="stat"><div class="icon blue">📦</div><div><span>Total Products</span><b><?= $totalProducts ?></b></div></div>
  <div class="stat"><div class="icon green">💰</div><div><span>Stock Value (cost)</span><b><?= money($stockValue) ?></b></div></div>
  <div class="stat"><div class="icon amber">⚠️</div><div><span>Low Stock</span><b><?= $lowCount ?></b></div></div>
  <div class="stat"><div class="icon red">⛔</div><div><span>Out of Stock</span><b><?= $outCount ?></b></div></div>
</div>

<div class="grid2">
  <div class="card"><h3>Stock Movements · Last 7 Days</h3><canvas id="moveChart" height="150"></canvas></div>
  <div class="card"><h3>Stock Value by Category</h3><canvas id="catChart" height="150"></canvas></div>
</div>

<div class="grid2">
  <div class="card"><h3>Reorder Alerts</h3>
    <table><tr><th>Product</th><th>Qty</th><th>Status</th></tr>
    <?php foreach ($alerts as $a): ?><tr><td><?= e($a['name']) ?></td><td><?= $a['quantity'] ?> / <?= $a['reorder_level'] ?></td><td><?= stock_badge($a['quantity'], $a['reorder_level']) ?></td></tr>
    <?php endforeach; if (!$alerts): ?><tr><td colspan="3" class="muted">All stock levels are healthy 🎉</td></tr><?php endif; ?></table></div>
  <div class="card"><h3>Recent Activity</h3>
    <table><tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th></tr>
    <?php foreach ($recent as $r): ?><tr><td><?= e($r['name']) ?></td><td><span class="badge <?= $r['type'] === 'IN' ? 'green' : 'red' ?>"><?= $r['type'] ?></span></td><td><?= $r['quantity'] ?></td><td><?= date('M d', strtotime($r['created_at'])) ?></td></tr>
    <?php endforeach; if (!$recent): ?><tr><td colspan="4" class="muted">No activity yet.</td></tr><?php endif; ?></table></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const days = <?= json_encode(array_map(fn($d) => date('M d', strtotime($d)), array_keys($days))) ?>;
new Chart(document.getElementById('moveChart'), { type: 'bar', data: { labels: days, datasets: [
  { label: 'Stock IN',  data: <?= json_encode(array_column($days, 'IN')) ?>,  backgroundColor: '#22c55e', borderRadius: 6 },
  { label: 'Stock OUT', data: <?= json_encode(array_column($days, 'OUT')) ?>, backgroundColor: '#ef4444', borderRadius: 6 }]},
  options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } } });
new Chart(document.getElementById('catChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_column($cat, 'n')) ?>,
  datasets: [{ data: <?= json_encode(array_map('floatval', array_column($cat, 'v'))) ?>, backgroundColor: ['#6366f1','#22c55e','#f59e0b','#ef4444','#06b6d4','#a855f7'] }]},
  options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' } });
</script>
<?php require 'includes/footer.php'; ?>
