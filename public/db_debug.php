<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/init.php';
bootstrap();

$pdo = db();

echo "<h3>PRAGMA database_list</h3><pre>";
foreach ($pdo->query("PRAGMA database_list") as $row) {
    print_r($row);
}
echo "</pre>";

echo "<h3>Schema de records</h3><pre>";
foreach ($pdo->query("PRAGMA table_info(records)") as $row) {
    print_r($row);
}
echo "</pre>";
