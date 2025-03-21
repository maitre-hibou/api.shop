<?php

declare(strict_types=1);

namespace App\Tests\Application\Shared\UI\Console\Command;

use App\Shared\Application\Database\MigrationExecutor;
use App\Shared\UI\Console\Command\MigrateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    private MigrationExecutor|MockObject $migrationExecutor;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->migrationExecutor = $this->createMock(MigrationExecutor::class);

        $command = new MigrateCommand($this->migrationExecutor);

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteMigrateSuccess(): void
    {
        $this->migrationExecutor->expects($this->once())
            ->method('migrate')
            ->willReturn([
                '20230101000000_test_migration_1.php',
                '20230102000000_test_migration_2.php',
            ]);

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Applying database migrations', $output);
        $this->assertStringContainsString('Executed 2 database migrations', $output);
        $this->assertStringContainsString('20230101000000_test_migration_1.php', $output);
        $this->assertStringContainsString('20230102000000_test_migration_2.php', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteMigrateWithNoMigrations(): void
    {
        $this->migrationExecutor->expects($this->once())
            ->method('migrate')
            ->willReturn([]);

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Applying database migrations', $output);
        $this->assertStringContainsString('Nothing to migrate', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteRollbackSuccess(): void
    {
        $this->migrationExecutor->expects($this->once())
            ->method('rollback')
            ->willReturn([
                '20230102000000_test_migration_2.php',
                '20230101000000_test_migration_1.php',
            ]);

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
            '--rollback' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Rolling back last batch of database migrations', $output);
        $this->assertStringContainsString('Rolled back the last batch of 2 migrations', $output);
        $this->assertStringContainsString('20230102000000_test_migration_2.php', $output);
        $this->assertStringContainsString('20230101000000_test_migration_1.php', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteRollbackWithNoMigrations(): void
    {
        $this->migrationExecutor->expects($this->once())
            ->method('rollback')
            ->willReturn([]);

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
            '--rollback' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Rolling back last batch of database migrations', $output);
        $this->assertStringContainsString('Nothing to rollback', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteMigrateFailure(): void
    {
        $this->migrationExecutor->expects($this->once())
            ->method('migrate')
            ->willThrowException(new \Exception('Database connection error'));

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Applying database migrations', $output);
        $this->assertStringContainsString('Database connection error', $output);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteRollbackFailure(): void
    {
        $this->migrationExecutor->expects($this->once())
            ->method('rollback')
            ->willThrowException(new \Exception('Failed to roll back migrations'));

        $this->commandTester->execute([
            'command' => 'migrations:migrate',
            '--rollback' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Rolling back last batch of database migrations', $output);
        $this->assertStringContainsString('Failed to roll back migrations', $output);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
}
