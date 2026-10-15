<?php
declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Task;
use App\Domain\TaskRepository;
use PDO;

final class PdoTaskRepository implements TaskRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<Task> */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT id, title, done FROM tasks ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $row): Task => $this->hydrate($row), $rows);
    }

    public function find(int $id): ?Task
    {
        $stmt = $this->pdo->prepare('SELECT id, title, done FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    public function create(string $title): Task
    {
        $stmt = $this->pdo->prepare('INSERT INTO tasks (title) VALUES (:title)');
        $stmt->execute(['title' => $title]);
        return new Task((int) $this->pdo->lastInsertId(), $title, false);
    }

    public function setDone(int $id, bool $done): ?Task
    {
        $stmt = $this->pdo->prepare('UPDATE tasks SET done = :done WHERE id = :id');
        $stmt->bindValue(':done', $done ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() === 1;
    }

    private function hydrate(array $row): Task
    {
        return new Task((int) $row['id'], (string) $row['title'], (int) $row['done'] === 1);
    }
}
