<?php
// migrate_signing.php
declare(strict_types=1);
require_once __DIR__ . '/app/db.php';

$db = getDatabase();

echo "<pre>🚀 Iniciando migration de colunas de assinatura em 'records'...\n\n";

function columnExists(SQLite3 $db, string $table, string $column): bool {
    $stmt = $db->prepare("PRAGMA table_info($table)");
    $res = $stmt->execute();
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        if (strcasecmp($row['name'], $column) === 0) return true;
    }
    return false;
}

try {
    $res = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='records'");
    if (!$res->fetchArray()) {
        echo "❌ A tabela 'records' não existe. Gere alguma credencial primeiro.\n";
        exit;
    }

    $adds = [
        "status TEXT",
        "signed_by TEXT",
        "signed_at TEXT",
        "signed_ip TEXT",
        "signature_image_path TEXT",
        "signature_hash TEXT",
        "pdf_signed_path TEXT"
    ];

    foreach ($adds as $def) {
        [$col] = explode(' ', $def, 2);
        if (!columnExists($db, 'records', $col)) {
            $db->exec("ALTER TABLE records ADD COLUMN $def");
            echo "✅ Coluna adicionada: $col\n";
        } else {
            echo "ℹ️ Coluna já existe: $col\n";
        }
    }

    echo "\n🎉 Migration concluída com sucesso!\n";
    echo "Agora o sistema está pronto para usar o módulo de assinatura.\n";
    echo "</pre>";

} catch (Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
