<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\PDO;

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Infrastructure\PDO\Connection;
use App\Shared\Infrastructure\PDO\MigrationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class MigrationRepositoryTest extends TestCase
{
    private MigrationRepository $migrationRepository;

    private string $migrationsPath;

    private MockObject $connection;

    private Filesystem $filesystem;

    public function testGetAvailableMigrations(): void
    {
        $this->createTestMigrationFile('20230101000000_test_migration_1.php');
        $this->createTestMigrationFile('20230102000000_test_migration_2.php');

        $migrations = $this->migrationRepository->getAvailableMigrations();

        $this->assertCount(2, $migrations);
        $this->assertContains('20230101000000_test_migration_1.php', $migrations);
        $this->assertContains('20230102000000_test_migration_2.php', $migrations);

        $this->assertEquals('20230101000000_test_migration_1.php', $migrations[0]);
        $this->assertEquals('20230102000000_test_migration_2.php', $migrations[1]);
    }

    public function testGetAppliedMigrations(): void
    {
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['migration' => '20230101000000_test_migration_1.php', 'batch' => 1, 'applied_at' => '2023-01-01 00:00:00'],
                false
            );

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT migration, batch, applied_at'))
            ->willReturn($mockStmt);

        $this->connection->expects($this->once())
            ->method('exec')
            ->with($this->stringContains('CREATE TABLE IF NOT EXISTS migrations'));

        $result = $this->migrationRepository->getAppliedMigrations();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('20230101000000_test_migration_1.php', $result);
        $this->assertEquals(1, $result['20230101000000_test_migration_1.php']['batch']);
    }

    public function testGetLastBatchNumber(): void
    {
        $mockStmt = $this->createMock(\PDOStatement::class);

        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('3');

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with('SELECT MAX(batch) FROM migrations')
            ->willReturn($mockStmt);

        $result = $this->migrationRepository->getLastBatchNumber();

        $this->assertEquals(3, $result);
    }

    public function testGetNextBatchNumber(): void
    {
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('3');

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with('SELECT MAX(batch) FROM migrations')
            ->willReturn($mockStmt);

        $result = $this->migrationRepository->getNextBatchNumber();

        $this->assertEquals(4, $result);
    }

    public function testGetMigrationsInBatch(): void
    {
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN)
            ->willReturn([
                '20230101000000_test_migration_1.php',
                '20230102000000_test_migration_2.php',
            ]);

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE batch = :batch'))
            ->willReturn($mockStmt);

        $result = $this->migrationRepository->getMigrationsInBatch(2);

        $this->assertCount(2, $result);
        $this->assertEquals('20230101000000_test_migration_1.php', $result[0]);
        $this->assertEquals('20230102000000_test_migration_2.php', $result[1]);
    }

    public function testGetPendingMigrations(): void
    {
        $this->createTestMigrationFile('20230101000000_test_migration_1.php');
        $this->createTestMigrationFile('20230102000000_test_migration_2.php');
        $this->createTestMigrationFile('20230103000000_test_migration_3.php');

        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->expects($this->once())
            ->method('execute');

        $mockStmt->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['migration' => '20230101000000_test_migration_1.php', 'batch' => 1, 'applied_at' => '2023-01-01 00:00:00'],
                false
            );

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT migration, batch, applied_at'))
            ->willReturn($mockStmt);

        $this->connection->expects($this->once())
            ->method('exec')
            ->with($this->stringContains('CREATE TABLE IF NOT EXISTS migrations'));

        $pendingMigrations = $this->migrationRepository->getPendingMigrations();

        $this->assertCount(2, $pendingMigrations);
        $this->assertContains('20230102000000_test_migration_2.php', $pendingMigrations);
        $this->assertContains('20230103000000_test_migration_3.php', $pendingMigrations);
    }

    public function testLoadMigration(): void
    {
        $this->createTestMigrationFile($filename = '20230101000000_test_migration_1.php');

        $migration = $this->migrationRepository->loadMigration($filename);

        $this->assertInstanceOf(MigrationInterface::class, $migration);
    }

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->migrationsPath = sys_get_temp_dir() . '/migrations_test_' . uniqid();
        $this->filesystem->mkdir($this->migrationsPath);

        $this->connection = $this->createMock(Connection::class);

        $this->migrationRepository = new MigrationRepository(
            $this->connection,
            $this->migrationsPath
        );
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->migrationsPath);
    }

    private function createTestMigrationFile(string $filename): void
    {
        $content = <<<'PHP'
<?php
declare(strict_types=1);

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Infrastructure\PDO\Connection;

return new class implements MigrationInterface {
    public function up(Connection $connection): void {}
    public function down(Connection $connection): void {}
};
PHP;

        file_put_contents($this->migrationsPath . '/' . $filename, $content);
    }
}
