<?php
declare(strict_types=1);

namespace App\Application;

use App\Domain\Task;
use App\Domain\TaskRepository;
use InvalidArgumentException;

final class TaskService
{
    public function __construct(private TaskRepository $repository)
    {
    }

    /** @return list<Task> */
    public function all(): array
    {
        return $this->repository->all();
    }

    public function find(int $id): ?Task
    {
        return $this->repository->find($id);
    }

    public function create(string $title): Task
    {
        $title = trim($title);
        if ($title === '' || strlen($title) > 200) {
            throw new InvalidArgumentException('title must be 1-200 bytes');
        }
        return $this->repository->create($title);
    }

    public function setDone(int $id, bool $done): ?Task
    {
        return $this->repository->setDone($id, $done);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
