<?php
declare(strict_types=1);

use App\Application\TaskService;
use App\Infrastructure\PdoTaskRepository;

require dirname(__DIR__) . '/bootstrap.php';
$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$schema = file_get_contents(dirname(__DIR__) . '/schema.sql');
if ($schema === false) {
    throw new RuntimeException('Cannot read schema.sql');
}
$pdo->exec($schema);
$service = new TaskService(new PdoTaskRepository($pdo));
$count = 0;

function check(bool $condition, string $label): void
{
    global $count;
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $label);
    }
    $count++;
    echo 'OK: ' . $label . PHP_EOL;
}

function expectException(callable $action, string $class, string $label): void
{
    try {
        $action();
    } catch (Throwable $e) {
        check($e instanceof $class, $label);
        return;
    }
    check(false, $label);
}

check($service->all() === [], 'initial list is empty');
$task = $service->create('  Learn PHP  ');
check($task->title === 'Learn PHP', 'title is trimmed');
check($task->done === false, 'new task is incomplete');
check($service->find($task->id)?->title === 'Learn PHP', 'read created task');
check(count($service->all()) === 1, 'list contains created task');
expectException(fn() => $service->create('   '), InvalidArgumentException::class, 'blank title rejected');
expectException(fn() => $service->create(str_repeat('x', 201)), InvalidArgumentException::class, '201 bytes rejected');
$boundary = $service->create(str_repeat('x', 200));
check(strlen($boundary->title) === 200, '200 bytes accepted');
expectException(fn() => $service->create(123), TypeError::class, 'integer title rejected by strict caller');
check(count($service->all()) === 2, 'rejected requests did not add rows');
check($service->setDone($task->id, true)?->done === true, 'task completed');
check($service->setDone($task->id, false)?->done === false, 'false remains boolean false');
check($service->setDone(999999, true) === null, 'update missing task returns null');
check($service->find(999999) === null, 'find missing task returns null');
$payload = "x'); DROP TABLE tasks; --";
$literal = $service->create($payload);
check($service->find($literal->id)?->title === $payload, 'SQL-looking text stored as data');
check(count($service->all()) === 3, 'table survives SQL-looking input');
check($service->delete($task->id), 'delete existing task succeeds');
check(!$service->delete($task->id), 'second delete reports missing');
check($service->find($task->id) === null, 'deleted task is absent');
check(count($service->all()) === 2, 'other tasks remain after delete');
echo 'Passed ' . $count . ' checks' . PHP_EOL;
