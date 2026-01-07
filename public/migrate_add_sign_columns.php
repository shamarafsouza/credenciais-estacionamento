<?php
declare(strict_types=1);

// public/migrate_add_sign_columns.php
require_once __DIR__ . '/../app/db.php';

$db = getDatabase();
echo "<pre>";
echo "DB path: " . realpath(__DIR__ . '/../data/credencial.db') . "\n\n";

// Lê colunas existentes da tabela 'records'
$cols = [];
$res = $db->query("PRAGMA table_info(records)");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $cols[strtolower($row['name'])] = true;
}
echo "Colunas atuais em 'records': " . implode(', ', array_keys($cols)) . "\n\n";

// Define colunas necessárias para assinatura
$needed = [
    'status'                => "TEXT",
    'signed_by'             => "TEXT",
    'signed_at'             => "TEXT",
    'signed_ip'             => "TEXT",
    'signature_image_path'  => "TEXT",
    'signature_hash'        => "TEXT",
    'pdf_signed_path'       => "TEXT"
];

$added = [];
foreach ($needed as $name => $type) {
    if (!isset($cols[strtolower($name)])) {
        $sql = "ALTER TABLE records ADD COLUMN $name $type";
        if (!$db->exec($sql)) {
            die("Falha ao adicionar coluna $name.\n");
        }
        $added[] = $name;
    }
}

if ($added) {
    echo "Colunas adicionadas: " . implode(', ', $added) . "\n";
} else {
    echo "Nenhuma coluna nova necessária. Tudo ok.\n";
}

// Mostra colunas após migração
$cols2 = [];
$res2 = $db->query("PRAGMA table_info(records)");
while ($r = $res2->fetchArray(SQLITE3_ASSOC)) {
    $cols2[] = $r['name'];
}
echo "\nColunas agora: " . implode(', ', $cols2) . "\n";
echo "\n✅ Migração concluída. Você já pode fechar esta página.\n";
echo "</pre>";
