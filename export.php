<?php
require 'config.php'; login_required();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventory_' . date('Ymd') . '.csv"');
$out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['SKU', 'Name', 'Category', 'Supplier', 'Cost', 'Price', 'Quantity', 'Reorder Level', 'Stock Value']);
foreach ($pdo->query('SELECT p.*, c.name cat, s.name sup FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.name') as $p)
    fputcsv($out, [$p['sku'], $p['name'], $p['cat'], $p['sup'], $p['cost_price'], $p['sell_price'], $p['quantity'], $p['reorder_level'], $p['quantity'] * $p['cost_price']]);
