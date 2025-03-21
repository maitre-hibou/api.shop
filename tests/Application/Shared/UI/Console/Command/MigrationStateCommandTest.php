<?php

declare(strict_types=1);

namespace App\Tests\Application\Shared\UI\Console\Command;

use App\Shared\Infrastructure\PDO\MigrationRepository;
use App\Shared\UI\Console\Command\MigrationStateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationStateCommandTest extends TestCase
{
    private MigrationRepository|MockObject $repository;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MigrationRepository::class);

        $command = new MigrationStateCommand($this->repository);

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testDisplaysNoMigrations(): void
    {
        $this->repository->expects($this->once())
            ->method('getAvailableMigrations')
            ->willReturn([]);

        $this->repository->expects($this->once())
            ->method('getAppliedMigrations')
            ->willReturn([]);

        $this->commandTester->execute([
            'command' => 'migrations:state',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Showing migration state', $output);
        $this->assertStringContainsString('Migration', $output);
        $this->assertStringContainsString('Batch', $output);
        $this->assertStringContainsString('State', $output);
        $this->assertStringContainsString('Applied at', $output);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testDisplaysPendingMigrations(): void
    {
        $this->repository->expects($this->once())
            ->method('getAvailableMigrations')
            ->willReturn([
                '20230101000000_test_migration_1.php',
                '20230102000000_test_migration_2.php',
            ]);

        $this->repository->expects($this->once())
            ->method('getAppliedMigrations')
            ->willReturn([]);

        $this->commandTester->execute([
            'command' => 'migrations:state',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('20230101000000_test_migration_1.php', $output);
        $this->assertStringContainsString('20230102000000_test_migration_2.php', $output);
        $this->assertStringContainsString('Pending ...', $output);
        $this->assertStringNotContainsString('Applied !', $output);

        $this->assertMatchesRegularExpression('/\|\s+-\s+\|/', $output);
    }

    public function testDisplaysAppliedMigrations(): void
    {
        $this->repository->expects($this->once())
            ->method('getAvailableMigrations')
            ->willReturn([
                '20230101000000_test_migration_1.php',
                '20230102000000_test_migration_2.php',
            ]);

        $this->repository->expects($this->once())
            ->method('getAppliedMigrations')
            ->willReturn([
                '20230101000000_test_migration_1.php' => [
                    'migration' => '20230101000000_test_migration_1.php',
                    'batch' => 1,
                    'applied_at' => '2023-01-01 00:00:00',
                ],
                '20230102000000_test_migration_2.php' => [
                    'migration' => '20230102000000_test_migration_2.php',
                    'batch' => 2,
                    'applied_at' => '2023-01-02 00:00:00',
                ],
            ]);

        $this->commandTester->execute([
            'command' => 'migrations:state',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('20230101000000_test_migration_1.php', $output);
        $this->assertStringContainsString('20230102000000_test_migration_2.php', $output);
        $this->assertStringContainsString('Applied', $output);
        $this->assertStringNotContainsString('Pending', $output);

        $this->assertStringContainsString('1', $output);
        $this->assertStringContainsString('2', $output);
        $this->assertStringContainsString('2023-01-01 00:00:00', $output);
        $this->assertStringContainsString('2023-01-02 00:00:00', $output);
    }

    public function testDisplaysMixedMigrationStates(): void
    {
        $this->repository->expects($this->once())
            ->method('getAvailableMigrations')
            ->willReturn([
                '20230101000000_test_migration_1.php',
                '20230102000000_test_migration_2.php',
                '20230103000000_test_migration_3.php',
            ]);

        $this->repository->expects($this->once())
            ->method('getAppliedMigrations')
            ->willReturn([
                '20230101000000_test_migration_1.php' => [
                    'migration' => '20230101000000_test_migration_1.php',
                    'batch' => 1,
                    'applied_at' => '2023-01-01 00:00:00',
                ],
                '20230102000000_test_migration_2.php' => [
                    'migration' => '20230102000000_test_migration_2.php',
                    'batch' => 1,
                    'applied_at' => '2023-01-01 00:05:00',
                ],
            ]);


        $this->commandTester->execute([
            'command' => 'migrations:state',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('20230101000000_test_migration_1.php', $output);
        $this->assertStringContainsString('20230102000000_test_migration_2.php', $output);
        $this->assertStringContainsString('20230103000000_test_migration_3.php', $output);

        $this->assertStringContainsString('Applied', $output);
        $this->assertStringContainsString('Pending', $output);

        $this->assertStringContainsString('1', $output);
        $this->assertMatchesRegularExpression('/\|\s+-\s+\|/', $output);

        $this->assertStringContainsString('2023-01-01 00:00:00', $output);
        $this->assertStringContainsString('2023-01-01 00:05:00', $output);
    }
}
