<?php

declare(strict_types=1);

namespace App\Tests\Application\Shared\UI\Console\Command;

use App\Shared\UI\Console\Command\CreateMigrationCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CreateMigrationCommandTest extends TestCase
{
    private string $testMigrationsDir;

    private Filesystem $filesystem;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->testMigrationsDir = sys_get_temp_dir() . '/migrations_test_' . uniqid();
        $this->filesystem->mkdir($this->testMigrationsDir);

        $application = new Application();
        $command = new CreateMigrationCommand($this->testMigrationsDir);
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->testMigrationsDir);
    }

    public function testCreatesNewMigrationFile(): void
    {
        $this->commandTester->execute([
            'command' => 'migrations:create',
            'name' => 'create_test_table',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Created migration:', $output);

        $files = glob($this->testMigrationsDir . '/*.php');
        $this->assertCount(1, $files);

        $filename = basename($files[0]);
        $this->assertMatchesRegularExpression('/^\d{14}_create_test_table\.php$/', $filename);

        $content = file_get_contents($files[0]);
        $this->assertStringContainsString('use App\Shared\Domain\Database\MigrationInterface;', $content);
        $this->assertStringContainsString('public function up(Connection $connection): void', $content);
        $this->assertStringContainsString('public function down(Connection $connection): void', $content);
    }

    public function testSanitizesFilename(): void
    {
        $this->commandTester->execute([
            'command' => 'migrations:create',
            'name' => 'Create User-Table with SPECIAL!@#$% chars',
        ]);

        $files = glob($this->testMigrationsDir . '/*.php');
        $filename = basename($files[0]);

        $this->assertMatchesRegularExpression('/^\d{14}_create_user_table_with_special_chars\.php$/', $filename);
    }
}
