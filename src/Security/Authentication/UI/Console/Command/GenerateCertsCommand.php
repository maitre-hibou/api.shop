<?php

declare(strict_types=1);

namespace App\Security\Authentication\UI\Console\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:security:generate-certs')]
class GenerateCertsCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly array $jwtConfig,
        private readonly Filesystem $fs,
    ) {
        parent::__construct('app:security:generate-certs');
    }

    protected function configure(): void
    {
        $this->setDescription('Generate OpenSSL certificates used for JWT encryption.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not update certificates files.');
        $this->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite certificates files.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Certificates generation command');

        $jwtOpenSSLConfig = $this->jwtConfig['openssl'];

        if ($input->getOption('dry-run')) {
            list($publicKey, $privateKey) = $this->generateKeyPair($jwtOpenSSLConfig['private_key_pass']);

            $this->io->success('Keys generated !');
            $this->io->info(sprintf('Update your public key in "%s"', $jwtOpenSSLConfig['public_key']));
            $this->io->writeln($publicKey);
            $this->io->info(sprintf('Update your private key in "%s"', $jwtOpenSSLConfig['private_key']));
            $this->io->writeln($privateKey);

            return Command::SUCCESS;
        }

        if ($this->fs->exists([$jwtOpenSSLConfig['public_key'], $jwtOpenSSLConfig['private_key']])) {
            try {
                $this->handleExistingKeys($input);
            } catch (\RuntimeException $e) {
                $this->io->error($e->getMessage());

                return Command::FAILURE;
            }

            if (
                !$input->getOption('no-interaction') &&
                !$this->io->confirm('You are about to replace your existing keys. Are you sure you wish to continue?', false)
            ) {
                $this->io->comment('Your action was canceled.');

                return Command::SUCCESS;
            }
        }

        list ($publicKey, $privateKey) = $this->generateKeyPair($jwtOpenSSLConfig['private_key_pass']);

        $this->fs->dumpFile($jwtOpenSSLConfig['public_key'], $publicKey);
        $this->fs->dumpFile($jwtOpenSSLConfig['private_key'], $privateKey);

        $this->io->success('Done!');

        return Command::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function generateKeyPair(string $privateKeyPass): array
    {
        $resource = openssl_pkey_new();
        if (false === $resource) {
            throw new \RuntimeException(openssl_error_string());
        }

        $success = openssl_pkey_export($resource, $privateKey, $privateKeyPass);
        if (false === $success) {
            throw new \RuntimeException(openssl_error_string());
        }

        $publicKeyData = openssl_pkey_get_details($resource);
        if(false === $publicKeyData) {
            throw new \RuntimeException(openssl_error_string());
        }

        $publicKey = $publicKeyData['key'];

        return [$publicKey, $privateKey];
    }

    private function handleExistingKeys(InputInterface $input): void
    {
        if (false === $input->getOption('overwrite')) {
            throw new \RuntimeException('Your keys already exist. Use the `--overwrite` option to force regeneration.', 1);
        }
    }
}
