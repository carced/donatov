<?php

declare(strict_types=1);

$root = dirname(__DIR__);
echo "Running scraper...\n";
passthru('python3 ' . escapeshellarg($root . '/scraper/scrape.py'), $code1);
if ($code1 !== 0) {
    exit($code1);
}
echo "Running import...\n";
passthru('php ' . escapeshellarg($root . '/import/import_to_mysql.php'), $code2);
exit($code2);
