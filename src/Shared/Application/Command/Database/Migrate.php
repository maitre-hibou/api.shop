<?php

declare(strict_types=1);

namespace App\Shared\Application\Command\Database;

use App\Shared\Domain\Database\MigrationExecutorInterface;

final readonly class Migrate
{
    public function __construct(
        private MigrationExecutorInterface $executor,
    ) {
    }

    public function __invoke(): array
    {
        return $this->executor->migrate();
    }
}
