<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\PDO;

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
}
