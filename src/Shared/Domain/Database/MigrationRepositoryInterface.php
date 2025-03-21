<?php

declare(strict_types=1);

namespace App\Shared\Domain\Database;

interface MigrationRepositoryInterface
{
    public function getAppliedMigrations(): array;

    public function getAvailableMigrations(): array;

    public function getLastBatchNumber(): int;

    public function getMigrationsInBatch(int $batchNumber): array;

    public function getNextBatchNumber(): int;

    public function getPendingMigrations(): array;

    public function loadMigration(string $migrationFile): MigrationInterface;
}
