<?php

declare(strict_types=1);

namespace App\Shared\Domain\Database;

interface MigrationExecutorInterface
{
    public function migrate(): array;

    public function rollback(): array;
}
