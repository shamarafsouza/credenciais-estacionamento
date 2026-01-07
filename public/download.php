<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/protected.php';
bootstrap();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo 'ID inválido.'; exit; }

$s = db()->prepare("SELECT * FROM records WHERE id=?");
$s->execute([$id]);
$r = $s->fetch();
if (!$r) { http_response_code(404); echo 'Registro não encontrado.'; exit; }

$path = $r['pdf_path'] ?? '';
if (!is_file($path)) {
  // tenta normalizar (caso tenha salvo relativo)
  $cands = [
    __DIR__ . '/../' . ltrim($path,'/\\'),
    storagePath(basename((string)$path)),
  ];
  foreach ($cands as $c) if (is_file($c)) { $path=$c; break; }
}
if (!is_file($path)) { http_response_code(404); echo 'PDF não encontrado.'; exit; }

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
