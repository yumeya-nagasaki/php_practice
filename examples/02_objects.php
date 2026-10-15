<?php
declare(strict_types=1);

final class User
{
    public function __construct(public string $name)
    {
    }
}

$original = ['name' => 'before'];
$copy = $original;
$copy['name'] = 'after';
echo $original['name'] . PHP_EOL;

$first = new User('before');
$second = $first;
$second->name = 'after';
echo $first->name . PHP_EOL;

$third = clone $first;
$third->name = 'cloned';
echo $first->name . PHP_EOL;

$items = [1, 2];
foreach ($items as &$item) {
    $item *= 2;
}
unset($item);
echo json_encode($items, JSON_THROW_ON_ERROR) . PHP_EOL;
