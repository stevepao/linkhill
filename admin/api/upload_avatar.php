<?php
declare(strict_types=1);
use function App\{pdo, require_user, json_response};
require __DIR__ . '/../../inc/db.php';
require __DIR__ . '/../../inc/auth.php';
require __DIR__ . '/../../inc/csrf.php';
require __DIR__ . '/../../inc/helpers.php';

$me = \App\require_user();
\App\csrf_verify();

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_response(['error'=>'No file'], 400);
}
$f = $_FILES['avatar'];
if ($f['size'] > 200*1024) json_response(['error'=>'Too large'], 413);
$fi = new finfo(FILEINFO_MIME_TYPE);
$mime = $fi->file($f['tmp_name']);
$ext = $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : '');
if (!$ext) json_response(['error'=>'Invalid type'], 415);
$name = bin2hex(random_bytes(8)) . '.' . $ext;
$dest = __DIR__ . '/../../assets/img/avatars/' . $name;
if (!move_uploaded_file($f['tmp_name'], $dest)) json_response(['error'=>'Save failed'], 500);
$path = '/assets/img/avatars/' . $name;
$up = pdo()->prepare("UPDATE users SET avatar_path=?, updated_at=NOW() WHERE id=?");
$up->execute([$path, $me['id']]);
json_response(['ok'=>true, 'path'=>$path]);
