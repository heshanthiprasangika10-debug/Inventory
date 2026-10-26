<?php
require_once 'config.php'; login_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if ($_POST['action'] === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $d = [trim($_POST['sku']), trim($_POST['name']), $_POST['category_id'] ?: null, $_POST['supplier_id'] ?: null,
                  (float)$_POST['cost_price'], (float)$_POST['sell_price'], max(0, (int)$_POST['reorder_level'])];
            if ($d[0] === '' || $d[1] === '') throw new Exception('SKU and name are required.');
            if ($id) {
                $pdo->prepare('UPDATE products SET sku=?,name=?,category_id=?,supplier_id=?,cost_price=?,sell_price=?,reorder_level=? WHERE id=?')->execute([...$d, $id]);
                flash('Product updated.');
            } else {
                $qty = max(0, (int)$_POST['quantity']);
                $pdo->prepare('INSERT INTO products(sku,name,category_id,supplier_id,cost_price,sell_price,reorder_level,quantity) VALUES(?,?,?,?,?,?,?,?)')->execute([...$d, $qty]);
                if ($qty > 0) $pdo->prepare("INSERT INTO stock_movements(product_id,type,quantity,note,user_id) VALUES(?,'IN',?,'Opening stock',?)")->execute([$pdo->lastInsertId(), $qty, $_SESSION['user']['id']]);
                flash('Product added.');
            }
        } elseif ($_POST['action'] === 'delete') {
            $pdo->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Product deleted.');
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() == 23000 ? 'That SKU already exists.' : 'Database error.', 'err');
    } catch (Exception $ex) { flash($ex->getMessage(), 'err'); }
    header('Location: products.php'); exit;
}

$title = 'Products'; require 'includes/header.php';
$q = trim($_GET['q'] ?? ''); $cf = (int)($_GET['cat'] ?? 0); $sf = $_GET['status'] ?? '';
$sql = 'SELECT p.*, c.name cat, s.name sup FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN suppliers s ON s.id=p.supplier_id WHERE (p.name LIKE ? OR p.sku LIKE ?)';
$par = ["%$q%", "%$q%"];
if ($cf) { $sql .= ' AND p.category_id=?'; $par[] = $cf; }
if ($sf === 'low') $sql .= ' AND p.quantity<=p.reorder_level';
$st = $pdo->prepare($sql . ' ORDER BY p.name'); $st->execute($par); $products = $st->fetchAll();
$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$sups = $pdo->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
?>
<div class="page-head"><div><h1>Products</h1><p class="muted"><?= count($products) ?> item(s) found</p></div>
<button class="btn primary" onclick="openModal('productModal')">+ Add Product</button></div>

<form class="filters card" method="get">
  <input name="q" value="<?= e($q) ?>" placeholder="🔍 Search name or SKU">
  <select name="cat"><option value="0">All categories</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cf == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <select name="status"><option value="">All stock</option><option value="low" <?= $sf === 'low' ? 'selected' : '' ?>>Low / out of stock</option></select>
  <button class="btn">Filter</button>
</form>

<div class="card table-wrap"><table>
<tr><th>SKU</th><th>Product</th><th>Category</th><th>Supplier</th><th>Cost</th><th>Price</th><th>Qty</th><th>Status</th><th></th></tr>
<?php foreach ($products as $p): ?>
<tr><td><code><?= e($p['sku']) ?></code></td><td><b><?= e($p['name']) ?></b></td><td><?= e($p['cat'] ?? '—') ?></td><td><?= e($p['sup'] ?? '—') ?></td>
<td><?= money($p['cost_price']) ?></td><td><?= money($p['sell_price']) ?></td><td><b><?= $p['quantity'] ?></b></td><td><?= stock_badge($p['quantity'], $p['reorder_level']) ?></td>
<td class="actions">
  <button class="btn sm" data-row='<?= e(json_encode($p)) ?>' onclick="editRow(this,'productModal')">Edit</button>
  <form method="post" onsubmit="return confirm('Delete this product and its history?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm danger">Delete</button></form>
</td></tr>
<?php endforeach; if (!$products): ?><tr><td colspan="9" class="muted">No products found.</td></tr><?php endif; ?>
</table></div>

<div class="modal" id="productModal"><div class="modal-box">
  <div class="modal-head"><h3>Product</h3><span class="close" onclick="closeModal('productModal')">✕</span></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id">
    <div class="form-grid">
      <div><label>SKU</label><input name="sku" required></div>
      <div><label>Product name</label><input name="name" required></div>
      <div><label>Category</label><select name="category_id"><option value="">— None —</option><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      <div><label>Supplier</label><select name="supplier_id"><option value="">— None —</option><?php foreach ($sups as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
      <div><label>Cost price</label><input type="number" step="0.01" min="0" name="cost_price" value="0"></div>
      <div><label>Selling price</label><input type="number" step="0.01" min="0" name="sell_price" value="0"></div>
      <div><label>Opening quantity <small>(new only)</small></label><input type="number" min="0" name="quantity" value="0"></div>
      <div><label>Reorder level</label><input type="number" min="0" name="reorder_level" value="5"></div>
    </div>
    <button class="btn primary block">Save product</button>
  </form></div></div>
<?php require 'includes/footer.php'; ?>
