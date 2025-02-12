<?php

declare(strict_types=1);

namespace App\Tests\Application\Security\Authentication\UI\Console\Command;

use App\Security\Authentication\UI\Console\Command\GenerateCertsCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @see GenerateCertsCommand
 */
class GenerateCertsCommandTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        $this->clearKeys();
    }

    public function testExecute(): void
    {
        $this->clearKeys();

        $jwtConfig = self::$kernel->getContainer()->getParameter('jwt');

        $app = new Application(self::$kernel);
        $command = $app->find('app:security:generate-certs');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertTrue(file_exists($jwtConfig['openssl']['private_key']));
        $this->assertTrue(file_exists($jwtConfig['openssl']['public_key']));
    }

    public function testDryRunExecute(): void
    {
        $this->clearKeys();

        $jwtConfig = self::$kernel->getContainer()->getParameter('jwt');

        $app = new Application(self::$kernel);
        $command = $app->find('app:security:generate-certs');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $keyContent = file_get_contents($jwtConfig['openssl']['private_key']);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('[INFO] Update your private key', $output);
        $this->assertStringContainsString('[INFO] Update your public key', $output);
        $this->assertSame(file_get_contents($jwtConfig['openssl']['private_key']), $keyContent);
    }

    public function testOverwriteExecute(): void
    {
        $this->clearKeys();

        $jwtConfig = self::$kernel->getContainer()->getParameter('jwt');

        $app = new Application(self::$kernel);
        $command = $app->find('app:security:generate-certs');

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $keyContent = file_get_contents($jwtConfig['openssl']['private_key']);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--no-interaction' => true, '--overwrite' => true]);
        $commandTester->assertCommandIsSuccessful();

        $this->assertNotSame(file_get_contents($jwtConfig['openssl']['private_key']), $keyContent);
    }

    private function clearKeys(): void
    {
        $jwtConfig = self::$kernel->getContainer()->getParameter('jwt');

        if (file_exists($jwtConfig['openssl']['private_key'])) {
            unlink($jwtConfig['openssl']['private_key']);
        }

        if (file_exists($jwtConfig['openssl']['public_key'])) {
            unlink($jwtConfig['openssl']['public_key']);
        }
    }
}
