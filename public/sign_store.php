<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/protected.php';
bootstrap();

$pdo = db();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Método inválido.');
    }

    // Campos do POST
    $id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $by      = strtoupper(trim((string)($_POST['by'] ?? '')));
    $dataUrl = (string)($_POST['dataUrl'] ?? ''); // <-- TEM QUE SER "dataUrl" no form

    if ($id <= 0) {
        throw new RuntimeException('ID inválido.');
    }
    if (!in_array($by, ['RENANN','KARINE'], true)) {
        // fallback seguro
        $by = 'RENANN';
    }

    // confere existência do registro e status
    $stmt = $pdo->prepare("SELECT id, status FROM records WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rec) {
        throw new RuntimeException('Registro não encontrado.');
    }
    if (strtoupper((string)($rec['status'] ?? '')) === 'ASSINADO') {
        throw new RuntimeException('Registro já assinado.');
    }

    // valida DataURL PNG
    $prefix = 'data:image/png;base64,';
    if (strpos($dataUrl, $prefix) !== 0) {
        throw new RuntimeException('Assinatura inválida (formato).');
    }

    $b64 = substr($dataUrl, strlen($prefix));
    $bin = base64_decode($b64, true);
    if ($bin === false || strlen($bin) < 100) {
        throw new RuntimeException('Imagem de assinatura vazia ou inválida.');
    }

    // garante pasta e salva arquivo
    $dirFs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'signatures';
    if (!is_dir($dirFs)) {
        if (!mkdir($dirFs, 0777, true) && !is_dir($dirFs)) {
            throw new RuntimeException('Falha ao criar diretório de assinaturas.');
        }
    }

    $ts   = date('Ymd_His');
    $name = "sign_{$id}_{$by}_{$ts}.png";
    $fileFs = $dirFs . DIRECTORY_SEPARATOR . $name;

    if (file_put_contents($fileFs, $bin) === false) {
        throw new RuntimeException('Erro ao salvar a imagem da assinatura.');
    }

    // caminho que vai pro banco (relativo à raiz do projeto)
    $imgRel = 'data/signatures/' . $name;

    // auditoria
    $hash = hash('sha256', $bin);
    $ip   = $_SERVER['REMOTE_ADDR']     ?? '';
    $ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // grava metadados da assinatura
    $upd = $pdo->prepare("
        UPDATE records
           SET signed_by = :by,
               signed_ip = :ip,
               signature_image_path = :img,
               signature_hash = :hash
         WHERE id = :id
    ");
    $upd->execute([
        ':by'   => $by,
        ':ip'   => $ip,         // (ua não está sendo salvo; se quiser, crie a coluna)
        ':img'  => $imgRel,
        ':hash' => $hash,
        ':id'   => $id,
    ]);

    if ($upd->rowCount() < 1) {
        // se não atualizou nada, é porque o WHERE id não bateu
        throw new RuntimeException('Falha ao vincular a assinatura ao registro.');
    }

    // segue para a finalização (sobrepor no PDF e marcar como ASSINADO)
    header('Location: sign_finalize.php?id=' . $id);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo "<pre>Erro: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
    echo '<p><a href="javascript:history.back()">Voltar</a></p>';
}
