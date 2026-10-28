<?php
require_once __DIR__ . '/../config/config.php';

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
    json_response(['ok' => true, 'results' => []]);
}

$like = '%' . $q . '%';
$stmt = db()->prepare(
    'SELECT p.name, p.slug, p.price, p.sale_price, p.image, c.name AS category
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.active = 1 AND (p.name LIKE ? OR p.grape LIKE ? OR p.region LIKE ?)
     ORDER BY p.featured DESC, p.name ASC LIMIT 6'
);
$stmt->execute([$like, $like, $like]);

$results = [];
foreach ($stmt->fetchAll() as $row) {
    $results[] = [
        'name'     => $row['name'],
        'url'      => url('product.php?slug=' . urlencode($row['slug'])),
        'category' => $row['category'],
        'price'    => money(product_price($row)),
        'image'    => product_image($row['image']),
    ];
}

json_response(['ok' => true, 'results' => $results]);
