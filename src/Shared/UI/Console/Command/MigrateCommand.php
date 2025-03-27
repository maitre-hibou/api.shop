<?php

declare(strict_types=1);

namespace App\Shared\UI\Console\Command;

use App\Shared\Application\Command\Database as Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrations:migrate', description: 'Execute pending database migrations.')]
final class MigrateCommand extends AbstractCommand
{
    public function __construct(
        private readonly Command\Migrate $migrateCommand,
        private readonly Command\Rollback $rollbackCommand
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Rollback the last batch of migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rollback = $input->getOption('rollback');

        $this->io->title(match ($rollback) {
            true => 'Rolling back last batch of database migrations...',
            false => 'Applying database migrations...'
        });

        try {
            $executed = match ($rollback) {
                false => ($this->migrateCommand)(),
                default => ($this->rollbackCommand)(),
            };
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());

            return SymfonyCommand::FAILURE;
        }


        if (empty($executed)) {
            $this->io->warning(match ($rollback) {
                false => 'Nothing to migrate.',
                default => 'Nothing to rollback.',
            });

            return SymfonyCommand::SUCCESS;
        }

        $this->io->success(match ($rollback) {
            false => sprintf('Executed %d database migrations !', count($executed)),
            default => sprintf('Rolled back the last batch of %d migrations !', count($executed)),
        });
        $this->io->listing($executed);

        return SymfonyCommand::SUCCESS;
    }
}
