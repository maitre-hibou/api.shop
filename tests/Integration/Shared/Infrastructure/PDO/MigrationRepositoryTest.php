<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\PDO;

use App\Shared\Infrastructure\PDO\Connection;
use App\Shared\Infrastructure\PDO\MigrationRepository;
use App\Tests\CanCreateMigrationFiles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class MigrationRepositoryTest extends TestCase
{
    use CanCreateMigrationFiles;

    private MigrationRepository $migrationRepository;

    private Connection $connection;

    private Filesystem $filesystem;

    public function testIntegrationBetweenGetAppliedMigrationsAndGetPendingMigrations(): void
    {
        // Create test migration files
        $this->createTestMigrationFile('20230101000000_test_migration_1.php');
        $this->createTestMigrationFile('20230102000000_test_migration_2.php');
        $this->createTestMigrationFile('20230103000000_test_migration_3.php');

        // Insert a migration record
        $this->connection->exec("
            INSERT INTO migrations (migration, batch, applied_at)
            VALUES ('20230101000000_test_migration_1.php', 1, '2023-01-01 00:00:00')
        ");

        // Get applied migrations
        $appliedMigrations = $this->migrationRepository->getAppliedMigrations();

        // Verify applied migrations
        $this->assertCount(1, $appliedMigrations);
        $this->assertArrayHasKey('20230101000000_test_migration_1.php', $appliedMigrations);

        // Get pending migrations
        $pendingMigrations = $this->migrationRepository->getPendingMigrations();

        // Verify pending migrations
        $this->assertCount(2, $pendingMigrations);
        $this->assertContains('20230102000000_test_migration_2.php', $pendingMigrations);
        $this->assertContains('20230103000000_test_migration_3.php', $pendingMigrations);
    }

    public function testGetLastBatchNumberAndGetMigrationsInBatch(): void
    {
        // Insert migration records for two different batches
        $this->connection->exec("
            INSERT INTO migrations (migration, batch, applied_at)
            VALUES
                ('20230101000000_test_migration_1.php', 1, '2023-01-01 00:00:00'),
                ('20230102000000_test_migration_2.php', 1, '2023-01-02 00:00:00'),
                ('20230103000000_test_migration_3.php', 2, '2023-01-03 00:00:00')
        ");

        // Get last batch number
        $lastBatchNumber = $this->migrationRepository->getLastBatchNumber();

        // Verify last batch number
        $this->assertEquals(2, $lastBatchNumber);

        // Get migrations in batch 1
        $batch1Migrations = $this->migrationRepository->getMigrationsInBatch(1);

        // Verify batch 1 migrations
        $this->assertCount(2, $batch1Migrations);
        $this->assertContains('20230101000000_test_migration_1.php', $batch1Migrations);
        $this->assertContains('20230102000000_test_migration_2.php', $batch1Migrations);

        // Get migrations in batch 2
        $batch2Migrations = $this->migrationRepository->getMigrationsInBatch(2);

        // Verify batch 2 migrations
        $this->assertCount(1, $batch2Migrations);
        $this->assertContains('20230103000000_test_migration_3.php', $batch2Migrations);
    }

    public function testGetNextBatchNumber(): void
    {
        // Insert a migration record
        $this->connection->exec("
            INSERT INTO migrations (migration, batch, applied_at)
            VALUES ('20230101000000_test_migration_1.php', 3, '2023-01-01 00:00:00')
        ");

        // Get next batch number
        $nextBatchNumber = $this->migrationRepository->getNextBatchNumber();

        // Verify next batch number
        $this->assertEquals(4, $nextBatchNumber);
    }

    protected function setUp(): void
    {
        try {
            $this->connection = new Connection([
                'dsn' => 'sqlite::memory:',
                'username' => '',
                'password' => '',
            ]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Database connection could not be established: ' . $e->getMessage());
        }

        $this->filesystem = new Filesystem();
        $this->migrationsPath = sys_get_temp_dir() . '/migrations_test_' . uniqid();
        $this->filesystem->mkdir($this->migrationsPath);

        $this->migrationRepository = new MigrationRepository(
            $this->connection,
            $this->migrationsPath
        );

        $this->connection->exec('
            CREATE TABLE IF NOT EXISTS migrations (
                migration VARCHAR(255) PRIMARY KEY,
                batch INTEGER NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    protected function tearDown(): void
    {
        $this->connection->exec('DROP TABLE IF EXISTS migrations');

        $this->filesystem->remove($this->migrationsPath);
    }
}
