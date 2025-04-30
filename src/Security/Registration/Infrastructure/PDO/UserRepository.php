<?php

declare(strict_types=1);

namespace App\Security\Registration\Infrastructure\PDO;

use App\Security\Authentication\Infrastructure\Symfony\User;
use App\Security\Registration\Domain\UserInterface;
use App\Security\Registration\Domain\UserRepositoryInterface;
use App\Shared\Infrastructure\PDO\Connection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createUser(UserInterface $user): string
    {
        return $this->connection->transaction(function (Connection $conn) use ($user) {
            $uuid = Uuid::v4()->toRfc4122();

            $stmt = $conn->prepare('
                INSERT INTO users (id, username, email, password)
                VALUES (:id, :username, :email, :password)
            ');

            $symfonyUser = new User((string) $user->email, (string) $user->password, ['ROLE_USER']);

            $stmt->execute([
                'id' => $uuid,
                'username' => (string)$user->username,
                'email' => (string)$user->email,
                'password' => $this->passwordHasher->hashPassword($symfonyUser, (string) $user->password),
            ]);

            return $uuid;
        });
    }
}
