<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\UI\Console\Command;

use App\Shared\Application\Database\MigrationExecutor;
use App\Shared\Infrastructure\PDO\Connection;
use App\Shared\Infrastructure\PDO\MigrationRepository;
use App\Shared\UI\Console\Command\MigrateCommand;
use App\Tests\CanCreateMigrationFiles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MigrateCommandTest extends TestCase
{
    use CanCreateMigrationFiles;

    private Connection $connection;

    private MigrationRepository $repository;

    private MigrationExecutor $executor;

    private CommandTester $commandTester;

    private Filesystem $filesystem;

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

        $this->connection->exec('
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->repository = new MigrationRepository($this->connection, $this->migrationsPath);
        $this->executor = new MigrationExecutor($this->repository, $this->connection);

        $command = new MigrateCommand($this->executor);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->migrationsPath);
    }

    public function testExecuteMigrateWithRealMigrations(): void
    {
        $this->createTestMigrationWithSchema('20230101000000_create_test_table.php', 'test_table', ['id INTEGER', 'name TEXT']);
        $this->createTestMigrationWithSchema('20230102000000_create_another_table.php', 'another_table', ['id INTEGER', 'description TEXT']);

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Executed 2 database migrations', $output);

        $tables = $this->getTableNames();
        $this->assertContains('test_table', $tables);
        $this->assertContains('another_table', $tables);

        $appliedMigrations = $this->repository->getAppliedMigrations();
        $this->assertCount(2, $appliedMigrations);
        $this->assertArrayHasKey('20230101000000_create_test_table.php', $appliedMigrations);
        $this->assertArrayHasKey('20230102000000_create_another_table.php', $appliedMigrations);
    }

    public function testExecuteRollbackWithRealMigrations(): void
    {
        $this->createTestMigrationWithSchema('20230101000000_create_test_table.php', 'test_table', ['id INTEGER', 'name TEXT']);
        $this->createTestMigrationWithSchema('20230102000000_create_another_table.php', 'another_table', ['id INTEGER', 'description TEXT']);

        $this->executor->migrate();

        $tables = $this->getTableNames();
        $this->assertContains('test_table', $tables);
        $this->assertContains('another_table', $tables);

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
            '--rollback' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Rolled back the last batch of 2 migrations', $output);

        $tables = $this->getTableNames();
        $this->assertNotContains('test_table', $tables);
        $this->assertNotContains('another_table', $tables);

        $appliedMigrations = $this->repository->getAppliedMigrations();
        $this->assertCount(0, $appliedMigrations);
    }

    public function testMigrateWhenNoMigrationsExist(): void
    {
        $this->commandTester->execute([
            'command' => 'migrations:migrate',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Nothing to migrate', $output);
    }

    public function testRollbackWhenNoMigrationsApplied(): void
    {
        $this->commandTester->execute([
            'command' => 'migrations:migrate',
            '--rollback' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Nothing to rollback', $output);
    }

    private function createTestMigrationWithSchema(string $filename, string $tableName, array $columns): void
    {
        $columnsStr = implode(', ', $columns);

        $content = <<<PHP
<?php
declare(strict_types=1);

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Infrastructure\PDO\Connection;

return new class implements MigrationInterface {
    public function up(Connection \$connection): void {
        \$connection->exec('CREATE TABLE {$tableName} ({$columnsStr})');
    }

    public function down(Connection \$connection): void {
        \$connection->exec('DROP TABLE IF EXISTS {$tableName}');
    }
};
PHP;

        file_put_contents($this->migrationsPath . '/' . $filename, $content);
    }

    private function getTableNames(): array
    {
        $stmt = $this->connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
