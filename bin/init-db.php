<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
$schema = file_get_contents(dirname(__DIR__) . '/schema.sql');
if ($schema === false) {
    throw new RuntimeException('Cannot read schema.sql');
}
openDatabase()->exec($schema);
echo 'Database initialized' . PHP_EOL;
