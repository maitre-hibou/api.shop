<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Symfony;

use App\Security\Authentication\Application\Query as Query;
use App\Security\Authentication\Domain\UserInterface as DomainUserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private Query\UserByEmail $queryUserByEmail
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
        if (null === ($user = ($this->queryUserByEmail)($identifier))) {
            throw new UserNotFoundException();
        }

        return new User($user->email, $user->password, $user->roles);
    }
}
