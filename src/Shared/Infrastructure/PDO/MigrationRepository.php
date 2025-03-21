<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\PDO;

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Domain\Database\MigrationRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class MigrationRepository implements MigrationRepositoryInterface
{
    private const string MIGRATIONS_TABLE_NAME = 'migrations';

    public function __construct(
        private Connection $connection,
        private string $migrationsPath
    ) {
        Assert::directory($migrationsPath);
    }

    /**
     * Returns an array containing applied migrations indexed by batch number.
     *
     * @return array<int, string>
     */
    public function getAppliedMigrations(): array
    {
        $this->ensureMigrationsTableExists();

        $stmt = $this->connection->prepare('
            SELECT migration, batch, applied_at
            FROM '.self::MIGRATIONS_TABLE_NAME.'
            ORDER BY batch, migration ASC
        ');
        $stmt->execute();

        $appliedMigrations = [];
        while ($row = $stmt->fetch(mode: \PDO::FETCH_ASSOC)) {
            $appliedMigrations[$row['migration']] = $row;
        }

        return $appliedMigrations;
    }

    /**
     * Returns an array containing the list of available migrations files.
     *
     * @return array<int, string>
     */
    public function getAvailableMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.php');
        $availableMigrations = array_map(function ($file) {
            return basename($file);
        }, $files);

        sort($availableMigrations);

        return $availableMigrations;
    }

    public function getLastBatchNumber(): int
    {
        $stmt = $this->connection->prepare('SELECT MAX(batch) FROM migrations');
        $stmt->execute();
        $result = $stmt->fetchColumn();

        return $result !== false ? (int) $result : 0;
    }

    /**
     * Returns an array containing all the migration of a given batch number
     *
     * @return array<int, string>
     */
    public function getMigrationsInBatch(int $batchNumber): array
    {
        $stmt = $this->connection->prepare('
            SELECT migration FROM migrations
            WHERE batch = :batch
            ORDER BY applied_at ASC
        ');

        $stmt->bindValue(':batch', $batchNumber, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    public function getPendingMigrations(): array
    {
        return array_diff($this->getAvailableMigrations(), array_keys($this->getAppliedMigrations()));
    }

    public function loadMigration(string $migrationFile): MigrationInterface
    {
        $path = $this->migrationsPath . '/' . $migrationFile;
        Assert::fileExists($path);

        $class = require $path;

        Assert::isInstanceOf($class, MigrationInterface::class);

        return $class;
    }

    protected function ensureMigrationsTableExists(): void
    {
        $this->connection->exec('
            CREATE TABLE IF NOT EXISTS '.self::MIGRATIONS_TABLE_NAME.' (
                migration VARCHAR(255) PRIMARY KEY,
                batch INTEGER NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }
}
