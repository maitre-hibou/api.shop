<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\PDO;

use App\Shared\Domain\Database\MigrationExecutorInterface;
use App\Shared\Domain\Database\MigrationRepositoryInterface;

final readonly class MigrationExecutor implements MigrationExecutorInterface
{
    public function __construct(
        private MigrationRepositoryInterface $repository,
        private Connection $connection
    ) {
    }

    /**
     * Executes pending migrations and returns them as list
     *
     * @return array<int, string>
     */
    public function migrate(): array
    {
        $pendingMigrations = $this->repository->getPendingMigrations();

        if (empty($pendingMigrations)) {
            return [];
        }

        $nextBatchNumber = $this->repository->getNextBatchNumber();

        return $this->connection->transaction(function (Connection $conn) use ($pendingMigrations, $nextBatchNumber) {
            $executed = [];

            foreach ($pendingMigrations as $migrationFile) {
                $migration = $this->repository->loadMigration($migrationFile);

                $migration->up($conn);

                $stmt = $conn->prepare('
                    INSERT INTO migrations (migration, batch)
                    VALUES (:migration, :batch)
                ');

                $stmt->bindValue(':migration', $migrationFile);
                $stmt->bindValue(':batch', $nextBatchNumber, \PDO::PARAM_INT);
                $stmt->execute();

                $executed[] = $migrationFile;
            }

            return $executed;
        });
    }

    public function rollback(): array
    {
        $lastBatchNumber = $this->repository->getLastBatchNumber();

        if ($lastBatchNumber === 0) {
            return [];
        }

        $migrationsInLastBatch = $this->repository->getMigrationsInBatch($lastBatchNumber);

        if (empty($migrationsInLastBatch)) {
            return [];
        }

        return $this->connection->transaction(function (Connection $conn) use ($migrationsInLastBatch) {
            $rolledBack = [];
            foreach ($migrationsInLastBatch as $migrationFile) {
                $migration = $this->repository->loadMigration($migrationFile);

                $migration->down($conn);

                $stmt = $conn->prepare('DELETE FROM migrations WHERE migration = :migration');
                $stmt->bindValue(':migration', $migrationFile);
                $stmt->execute();

                $rolledBack[] = $migrationFile;
            }

            return $rolledBack;
        });
    }
}
