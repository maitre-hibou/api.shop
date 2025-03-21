<?php

declare(strict_types=1);

namespace App\Shared\UI\Console\Command;

use App\Shared\Infrastructure\PDO\MigrationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrations:state', description: 'Show the state of migrations.')]
final class MigrationStateCommand extends AbstractCommand
{
    public function __construct(
        private readonly MigrationRepository $repository,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Showing migration state ...');

        $availableMigrations = $this->repository->getAvailableMigrations();
        $appliedMigrations = $this->repository->getAppliedMigrations();

        $infos = [];
        foreach ($availableMigrations as $migration) {
            if (array_key_exists($migration, $appliedMigrations)) {
                $infos[] = [
                    $migration,
                    $appliedMigrations[$migration]['batch'],
                    '<info>Applied !</info>',
                    $appliedMigrations[$migration]['applied_at'],
                ];
            } else {
                $infos[] = [
                    $migration,
                    '-',
                    '<comment>Pending ...</comment>',
                    '-',
                ];
            }
        }

        $table = new Table($output)
            ->setHeaders(['Migration', 'Batch', 'State', 'Applied at'])
            ->setRows($infos)
        ;

        $table->render();

        return Command::SUCCESS;
    }
}
