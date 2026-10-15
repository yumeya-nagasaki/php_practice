<?php
declare(strict_types=1);

namespace App\Domain;

interface TaskRepository
{
    /** @return list<Task> */
    public function all(): array;
    public function find(int $id): ?Task;
    public function create(string $title): Task;
    public function setDone(int $id, bool $done): ?Task;
    public function delete(int $id): bool;
}
