<?php

declare(strict_types=1);

namespace App\Shared\UI\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrations:create', description: 'Create a new migration file')]
final class CreateMigrationCommand extends AbstractCommand
{
    public function __construct(
        private readonly string $migrationsPath
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the migration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Create new migration file ...');

        $name = $input->getArgument('name');

        // Format the name
        $name = preg_replace('/[^a-z0-9_]+/', '_', strtolower($name));
        $timestamp = date('YmdHis');
        $filename = sprintf('%s_%s.php', $timestamp, $name);
        $filepath = $this->migrationsPath . '/' . $filename;

        $template = <<<'PHP'
<?php

declare(strict_types=1);

use App\Shared\Domain\Database\MigrationInterface;
use App\Shared\Infrastructure\PDO\Connection;

return new class implements MigrationInterface
{
    public function up(Connection $connection): void
    {
        $connection->exec('
            -- Your SQL code here
        ');
    }

    public function down(Connection $connection): void
    {
        $connection->exec('
            -- Revert SQL code here
        ');
    }
};
PHP;

        file_put_contents($filepath, $template);

        $this->io->success(sprintf('Created migration: %s', $filename));

        return Command::SUCCESS;
    }
}
