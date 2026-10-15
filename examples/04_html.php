<?php
declare(strict_types=1);

$title = '<script>alert("test")</script>';
echo htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . PHP_EOL;
