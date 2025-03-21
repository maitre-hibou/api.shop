<?php

declare(strict_types=1);

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Infrastructure\PDO\Connection;

return new class implements MigrationInterface
{
    public function up(Connection $connection): void
    {
        $connection->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    public function down(Connection $connection): void
    {
        $connection->exec('
            DROP TABLE IF EXISTS users
        ');
    }
};
