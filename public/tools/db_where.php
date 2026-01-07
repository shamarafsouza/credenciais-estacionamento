<?php
declare(strict_types=1);

// Debug do SQLite via PDO
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/protected.php';
bootstrap();

$pdo = db();

header('Content-Type: text/plain; charset=utf-8');

// Caminho que o PDO está usando (via PRAGMA database_list)
echo "=== INFO DO BANCO ===\n";
$databases = $pdo->query("PRAGMA database_list")->fetchAll(PDO::FETCH_ASSOC);
foreach ($databases as $dbi) {
    // file => caminho do arquivo; name => main/temp
    $name = $dbi['name'] ?? '';
    $file = $dbi['file'] ?? '(memory)';
    echo "- {$name}: {$file}\n";
}

echo "\n=== COLUNAS DA TABELA 'records' ===\n";
$cols = $pdo->query("PRAGMA table_info(records)")->fetchAll(PDO::FETCH_ASSOC);
if (!$cols) {
    echo "(tabela não encontrada)\n";
} else {
    foreach ($cols as $c) {
        // c['name'], c['type'], c['notnull'], c['dflt_value']
        $nn = ((int)($c['notnull'] ?? 0)) ? 'NOT NULL' : 'NULL';
        $df = is_null($c['dflt_value']) ? '' : (" DEFAULT " . $c['dflt_value']);
        echo "- {$c['name']} ({$c['type']}) {$nn}{$df}\n";
    }
}

echo "\n=== CONTAGEM (ASSINADOS / PENDENTES) ===\n";
$assinados = (int)$pdo->query("
    SELECT COUNT(*) FROM records WHERE UPPER(COALESCE(status,'')) = 'ASSINADO'
")->fetchColumn();

$pendentes = (int)$pdo->query("
    SELECT COUNT(*) FROM records WHERE status IS NULL OR UPPER(status) <> 'ASSINADO'
")->fetchColumn();

$todos = (int)$pdo->query("SELECT COUNT(*) FROM records")->fetchColumn();

echo "Total: {$todos}\n";
echo "Assinados: {$assinados}\n";
echo "Pendentes: {$pendentes}\n";

echo "\n=== AMOSTRA (5 registros mais recentes) ===\n";
$rows = $pdo->query("
    SELECT id, tipo, numero_formatado, nome, date(data_emissao) AS emissao,
           date(data_validade) AS validade, COALESCE(status,'') AS status
    FROM records
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo "#{$r['id']} {$r['tipo']} {$r['numero_formatado']} - {$r['nome']} | {$r['emissao']} -> {$r['validade']} | status='{$r['status']}'\n";
}
