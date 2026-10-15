<?php
declare(strict_types=1);

$name = 'Java developer';
$years = 18;
echo $name . ': ' . $years . ' years' . PHP_EOL;

function add(int $a, int $b): int
{
    return $a + $b;
}

echo add(2, 3) . PHP_EOL;
var_dump(100 == '100');
var_dump(100 === '100');

$languages = ['Java', 'PHP'];
$languages[] = 'SQL';
foreach ($languages as $language) {
    echo $language . PHP_EOL;
}

$user = ['name' => 'Setsura', 'email' => null];
echo ($user['nickname'] ?? 'guest') . PHP_EOL;
var_dump(isset($user['email']));
var_dump(array_key_exists('email', $user));
var_dump(empty('0'));

$amounts = [100, 200, 300];
$doubled = array_map(fn(int $n): int => $n * 2, $amounts);
echo json_encode($doubled, JSON_THROW_ON_ERROR) . PHP_EOL;

try {
    add('2', 3);
} catch (TypeError $e) {
    echo 'TypeError: string is not int' . PHP_EOL;
}
