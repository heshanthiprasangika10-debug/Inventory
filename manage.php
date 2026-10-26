<?php
require_once 'config.php'; login_required();
$types = [
  'categories' => ['Categories', ['name' => 'Name']],
  'suppliers'  => ['Suppliers',  ['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email']],
];
$type = $_GET['type'] ?? 'categories'; if (!isset($types[$type])) $type = 'categories';
[$label, $fields] = $types[$type];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if ($_POST['action'] === 'save') {
            $id = (int)($_POST['id'] ?? 0); $vals = [];
            foreach ($fields as $k => $_) $vals[] = trim($_POST[$k] ?? '');
            if ($vals[0] === '') throw new Exception('Name is required.');
            if ($id) { $set = implode(',', array_map(fn($k) => "$k=?", array_keys($fields))); $pdo->prepare("UPDATE $type SET $set WHERE id=?")->execute([...$vals, $id]); }
            else { $cols = implode(',', array_keys($fields)); $ph = implode(',', array_fill(0, count($fields), '?')); $pdo->prepare("INSERT INTO $type($cols) VALUES($ph)")->execute($vals); }
            flash('Saved.');
        } else { $pdo->prepare("DELETE FROM $type WHERE id=?")->execute([(int)$_POST['id']]); flash('Deleted.'); }
    } catch (PDOException $ex) { flash($ex->getCode() == 23000 ? 'Duplicate name.' : 'Database error.', 'err'); }
      catch (Exception $ex) { flash($ex->getMessage(), 'err'); }
    header("Location: manage.php?type=$type"); exit;
}

$title = $label; require 'includes/header.php';
$rows = $pdo->query("SELECT * FROM $type ORDER BY name")->fetchAll();
?>
<div class="page-head"><div><h1><?= $label ?></h1><p class="muted"><?= count($rows) ?> record(s)</p></div>
<button class="btn primary" onclick="openModal('mModal')">+ Add</button></div>
<div class="card table-wrap"><table>
<tr><?php foreach ($fields as $l): ?><th><?= $l ?></th><?php endforeach; ?><th></th></tr>
<?php foreach ($rows as $r): ?><tr>
<?php foreach ($fields as $k => $_): ?><td><?= e($r[$k] ?: '—') ?></td><?php endforeach; ?>
<td class="actions"><button class="btn sm" data-row='<?= e(json_encode($r)) ?>' onclick="editRow(this,'mModal')">Edit</button>
<form method="post" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Delete</button></form></td></tr>
<?php endforeach; ?></table></div>

<div class="modal" id="mModal"><div class="modal-box">
  <div class="modal-head"><h3><?= $label ?></h3><span class="close" onclick="closeModal('mModal')">✕</span></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id">
    <?php foreach ($fields as $k => $l): ?><label><?= $l ?></label><input name="<?= $k ?>" <?= $k === 'name' ? 'required' : '' ?>><?php endforeach; ?>
    <button class="btn primary block">Save</button></form></div></div>
<?php require 'includes/footer.php'; ?>
