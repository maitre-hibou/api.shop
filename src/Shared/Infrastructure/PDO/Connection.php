<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\PDO;

use Throwable;
use Webmozart\Assert\Assert;

final class Connection extends \PDO
{
    public function __construct(array $dbParameters)
    {
        Assert::keyExists($dbParameters, 'dsn');
        Assert::keyExists($dbParameters, 'username');
        Assert::keyExists($dbParameters, 'password');

        parent::__construct(
            dsn: $dbParameters['dsn'],
            username: $dbParameters['username'],
            password: $dbParameters['password'],
            options: $dbParameters['options'] ?? []
        );
    }

    public function transaction(callable $callback, int $maxRetries = 3, int $initialDelay = 10000): mixed
    {
        $retries = 0;
        $delay = $initialDelay;

        while (true) {
            try {
                $this->beginTransaction();

                $result = $callback($this);

                if ($this->inTransaction()) {
                    $this->commit();
                }

                return $result;

            } catch (Throwable $e) {
                if ($this->inTransaction()) {
                    $this->rollBack();
                }

                $isDeadlock = false;
                $errorCode = $e->getCode();
                $errorMessage = strtolower($e->getMessage());

                if (
                    $errorCode === 1213 || // MySQL deadlock
                    $errorCode === '40P01' || // PostgreSQL deadlock
                    strpos($errorMessage, 'deadlock') !== false ||
                    strpos($errorMessage, 'database is locked') !== false ||
                    strpos($errorMessage, 'serialization failure') !== false
                ) {
                    $isDeadlock = true;
                }

                if ($isDeadlock && $retries < $maxRetries) {
                    $retries++;
                    // Sleep before retry & increment delay
                    usleep($delay);
                    $delay *= 2;
                    continue;
                }

                throw $e;
            }
        }
    }
}
