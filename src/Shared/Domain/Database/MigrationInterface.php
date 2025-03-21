<?php

declare(strict_types=1);

namespace App\Shared\Domain\Database;

use App\Shared\Infrastructure\PDO\Connection;

interface MigrationInterface
{
    public function up(Connection $connection): void;

    public function down(Connection $connection): void;
}
