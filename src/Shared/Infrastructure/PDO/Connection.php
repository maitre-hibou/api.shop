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

    public function transaction(callable $callback): mixed
    {
        $result = null;

        try {
            $this->beginTransaction();

            $result = $callback($this);

            if ($this->inTransaction()) {
                $this->commit();
            }

        } catch (Throwable) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }
        }

        return $result;
    }
}
