<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Shared\Application\Command\Database\Migrate;
use App\Shared\Application\Command\Database\Rollback;
use App\Shared\UI\Console\Command\MigrateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{
    protected \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $app = new Application();
        $app->add(new MigrateCommand(
            static::getContainer()->get(Migrate::class),
            static::getContainer()->get(Rollback::class),
        ));

        $app->doRun(new StringInput('migrations:migrate'), new NullOutput());
    }
}
