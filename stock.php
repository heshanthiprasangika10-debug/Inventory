<?php
require_once 'config.php'; login_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $pid = (int)$_POST['product_id']; $type = $_POST['type'] === 'OUT' ? 'OUT' : 'IN'; $qty = (int)$_POST['quantity'];
        if ($qty < 1) throw new Exception('Quantity must be at least 1.');
        $pdo->beginTransaction();
        $p = $pdo->prepare('SELECT quantity FROM products WHERE id=? FOR UPDATE'); $p->execute([$pid]); $row = $p->fetch();
        if (!$row) throw new Exception('Product not found.');
        if ($type === 'OUT' && $row['quantity'] < $qty) throw new Exception("Only {$row['quantity']} unit(s) in stock.");
        $pdo->prepare('UPDATE products SET quantity = quantity ' . ($type === 'IN' ? '+' : '-') . ' ? WHERE id=?')->execute([$qty, $pid]);
        $pdo->prepare('INSERT INTO stock_movements(product_id,type,quantity,note,user_id) VALUES(?,?,?,?,?)')->execute([$pid, $type, $qty, trim($_POST['note']), $_SESSION['user']['id']]);
        $pdo->commit(); flash("Stock $type recorded.");
    } catch (Exception $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); flash($ex->getMessage(), 'err'); }
    header('Location: stock.php'); exit;
}

$title = 'Stock Movements'; require 'includes/header.php';
$prods = $pdo->query('SELECT id,sku,name,quantity FROM products ORDER BY name')->fetchAll();
$hist = $pdo->query('SELECT m.*, p.name, p.sku, u.name uname FROM stock_movements m JOIN products p ON p.id=m.product_id LEFT JOIN users u ON u.id=m.user_id ORDER BY m.id DESC LIMIT 100')->fetchAll();
?>
<div class="page-head"><div><h1>Stock Movements</h1><p class="muted">Record stock coming in or going out.</p></div></div>
<div class="card"><form method="post" class="form-grid"><?= csrf_field() ?>
  <div><label>Product</label><select name="product_id" required><option value="">Select product</option><?php foreach ($prods as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['sku']) ?> · <?= e($p['name']) ?> (<?= $p['quantity'] ?> in stock)</option><?php endforeach; ?></select></div>
  <div><label>Type</label><select name="type"><option value="IN">Stock IN (purchase / return)</option><option value="OUT">Stock OUT (sale / damage)</option></select></div>
  <div><label>Quantity</label><input type="number" name="quantity" min="1" value="1" required></div>
  <div><label>Note</label><input name="note" placeholder="Invoice no, reason..."></div>
  <div><button class="btn primary">Record movement</button></div>
</form></div>

<div class="card table-wrap"><h3>History (latest 100)</h3><table>
<tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Note</th><th>By</th></tr>
<?php foreach ($hist as $h): ?>
<tr><td><?= date('Y-m-d H:i', strtotime($h['created_at'])) ?></td><td><?= e($h['name']) ?> <code><?= e($h['sku']) ?></code></td>
<td><span class="badge <?= $h['type'] === 'IN' ? 'green' : 'red' ?>"><?= $h['type'] ?></span></td><td><b><?= $h['quantity'] ?></b></td><td><?= e($h['note']) ?></td><td><?= e($h['uname'] ?? '—') ?></td></tr>
<?php endforeach; ?></table></div>
<?php require 'includes/footer.php'; ?>
