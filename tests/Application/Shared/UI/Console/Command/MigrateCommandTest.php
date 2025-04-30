<?php

declare(strict_types=1);

namespace App\Tests\Application\Shared\UI\Console\Command;

use App\Shared\Application\Command\Database\Migrate;
use App\Shared\Application\Command\Database\Rollback;
use App\Shared\UI\Console\Command\MigrateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    private Migrate|MockObject $migrateCommand;
    private Rollback|MockObject $rollbackCommand;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->migrateCommand = $this->createMock(Migrate::class);
        $this->rollbackCommand = $this->createMock(Rollback::class);

        $command = new MigrateCommand($this->migrateCommand, $this->rollbackCommand);

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteMigrateSuccess(): void
    {
        $this->migrateCommand->expects($this->once())
            ->method('__invoke')
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
        $this->migrateCommand->expects($this->once())
            ->method('__invoke')
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
        $this->rollbackCommand->expects($this->once())
            ->method('__invoke')
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
        $this->rollbackCommand->expects($this->once())
            ->method('__invoke')
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
        $this->migrateCommand->expects($this->once())
            ->method('__invoke')
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
        $this->rollbackCommand->expects($this->once())
            ->method('__invoke')
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
