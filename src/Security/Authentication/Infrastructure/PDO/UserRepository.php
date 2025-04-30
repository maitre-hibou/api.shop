<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\PDO;

use App\Security\Authentication\Domain\UserInterface;
use App\Security\Authentication\Domain\UserRepositoryInterface;
use App\Security\Authentication\Infrastructure\Symfony\User;
use App\Shared\Infrastructure\PDO\Connection;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findUserByEmail(string $email): ?UserInterface
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if (false === ($data = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            return null;
        }

        return new User($data['email'], $data['password']);
    }
}
