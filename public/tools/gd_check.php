<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . "\n";
if (extension_loaded('gd')) {
    $gd = gd_info();
    echo "GD OK\n";
    echo "GD Version: " . ($gd['GD Version'] ?? 'n/a') . "\n";
    echo "PNG Support: " . (!empty($gd['PNG Support']) ? 'yes' : 'no') . "\n";
} else {
    echo "GD AUSENTE\n";
}