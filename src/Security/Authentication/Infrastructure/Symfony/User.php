<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Symfony;

use App\Security\Authentication\Domain\UserInterface;
use Symfony\Component\Security\Core\User as SymfonyUser;

final readonly class User implements UserInterface, SymfonyUser\UserInterface, SymfonyUser\PasswordAuthenticatedUserInterface
{
    public function __construct(
        public string $email,
        private(set) string $password,
        public array $roles = ['ROLE_USER'],
    ) {
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
