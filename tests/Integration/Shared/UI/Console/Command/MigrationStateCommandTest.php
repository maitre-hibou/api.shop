<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\UI\Console\Command;

use App\Shared\Infrastructure\PDO\Connection;
use App\Shared\Infrastructure\PDO\MigrationRepository;
use App\Shared\UI\Console\Command\MigrationStateCommand;
use App\Tests\CanCreateMigrationFiles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MigrationStateCommandTest extends TestCase
{
    use CanCreateMigrationFiles;

    private Connection $connection;

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
        $this->migrationsPath = sys_get_temp_dir() . '/migrations_state_test_' . uniqid();
        $this->filesystem->mkdir($this->migrationsPath);

        $this->connection->exec('
            CREATE TABLE IF NOT EXISTS migrations (
                migration VARCHAR(255) PRIMARY KEY,
                batch INTEGER NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $repository = new MigrationRepository(
            $this->connection,
            $this->migrationsPath
        );

        $command = new MigrationStateCommand($repository);

        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->connection->exec('DROP TABLE IF EXISTS migrations');

        $this->filesystem->remove($this->migrationsPath);
    }

    public function testIntegrationWithRealMigrationFiles(): void
    {
        $this->createTestMigrationFile('20230101000000_test_migration_1.php');
        $this->createTestMigrationFile('20230102000000_test_migration_2.php');
        $this->createTestMigrationFile('20230103000000_test_migration_3.php');

        $this->connection->exec("
            INSERT INTO migrations (migration, batch, applied_at)
            VALUES
                ('20230101000000_test_migration_1.php', 1, '2023-01-01 00:00:00'),
                ('20230102000000_test_migration_2.php', 2, '2023-01-02 00:00:00')
        ");

        $this->commandTester->execute([
            'command' => 'migrations:state',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('20230101000000_test_migration_1.php', $output);
        $this->assertStringContainsString('20230102000000_test_migration_2.php', $output);
        $this->assertStringContainsString('Applied !', $output);
        $this->assertStringContainsString('1', $output); // batch 1
        $this->assertStringContainsString('2', $output); // batch 2
        $this->assertStringContainsString('2023-01-01 00:00:00', $output);
        $this->assertStringContainsString('2023-01-02 00:00:00', $output);

        $this->assertStringContainsString('20230103000000_test_migration_3.php', $output);
        $this->assertStringContainsString('Pending ...', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testIntegrationWithNoMigrationFiles(): void
    {
        $this->commandTester->execute([
            'command' => 'migrations:state',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Migration', $output);
        $this->assertStringContainsString('Batch', $output);
        $this->assertStringContainsString('State', $output);
        $this->assertStringContainsString('Applied at', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }
}
