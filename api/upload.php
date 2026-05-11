<?php
require __DIR__ . '/_bootstrap.php';

if (!in_array(user()['role'], ['admin','manager'], true)) json_response(['error' => 'forbidden'], 403);

$type = $_GET['type'] ?? 'products';
$allowedTypes = ['products', 'tenant'];
if (!in_array($type, $allowedTypes, true)) json_response(['error' => 'invalid type'], 400);

if (empty($_FILES['file'])) json_response(['error' => 'nessun file'], 400);
$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) json_response(['error' => 'upload fallito (code ' . $f['error'] . ')'], 400);

$mime = function_exists('mime_content_type') ? mime_content_type($f['tmp_name']) : ($f['type'] ?? '');
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!isset($allowed[$mime])) json_response(['error' => 'formato non valido (jpg, png, webp, gif)'], 400);
if ($f['size'] > 5 * 1024 * 1024) json_response(['error' => 'file troppo grande (max 5MB)'], 400);

$dir = BASE_PATH . '/assets/uploads/' . $type;
if (!is_dir($dir)) @mkdir($dir, 0775, true);
if (!is_writable($dir)) json_response(['error' => 'cartella non scrivibile: ' . $dir], 500);

$name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
$dest = $dir . '/' . $name;
if (!move_uploaded_file($f['tmp_name'], $dest)) json_response(['error' => 'salvataggio fallito'], 500);

@chmod($dest, 0644);
json_response(['ok' => true, 'url' => '/assets/uploads/' . $type . '/' . $name]);
