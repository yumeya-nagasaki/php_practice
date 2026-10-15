<?php
declare(strict_types=1);

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec('CREATE TABLE tasks (id INTEGER PRIMARY KEY, title TEXT NOT NULL)');
$pdo->exec("CREATE TABLE logs (task_id INTEGER, event TEXT CHECK(event = 'created'))");

function createWithLog(PDO $pdo, string $title, bool $fail = false): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO tasks (title) VALUES (:title)');
        $stmt->execute(['title' => $title]);
        $id = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO logs (task_id, event) VALUES (:id, :event)'
        );
        $stmt->execute(['id' => $id, 'event' => $fail ? 'invalid' : 'created']);
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

createWithLog($pdo, 'committed');
try {
    createWithLog($pdo, 'rolled back', true);
} catch (PDOException $e) {
    echo 'Rollback completed' . PHP_EOL;
}
echo 'tasks=' . $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn() . PHP_EOL;
echo 'logs=' . $pdo->query('SELECT COUNT(*) FROM logs')->fetchColumn() . PHP_EOL;
