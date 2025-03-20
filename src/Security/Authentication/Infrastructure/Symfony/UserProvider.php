<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Symfony;

use App\Security\Authentication\Domain\UserInterface as DomainUserInterface;
use App\Security\Authentication\Domain\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return is_subclass_of($class, DomainUserInterface::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (null === ($user = $this->userRepository->findByEmail($identifier))) {
            throw new UserNotFoundException();
        }

        return new readonly class($user->email, $user->password, $user->roles) implements UserInterface, PasswordAuthenticatedUserInterface {
            public function __construct(
                private string $username,
                private string $password,
                private array $roles = ['ROLE_USER'],
            ) {
            }

            public function getRoles(): array
            {
                return $this->roles;
            }

            public function eraseCredentials(): void
            {
                // ...
            }

            public function getUserIdentifier(): string
            {
                return $this->username;
            }

            public function getPassword(): ?string
            {
                return $this->password;
            }
        };
    }
}
