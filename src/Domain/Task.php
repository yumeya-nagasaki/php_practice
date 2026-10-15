<?php
declare(strict_types=1);

namespace App\Domain;

final class Task
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly bool $done,
    ) {
    }

    /** @return array{id: int, title: string, done: bool} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'done' => $this->done];
    }
}
