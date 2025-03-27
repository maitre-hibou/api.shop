<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Query;

use App\Security\Authentication\Domain\UserInterface;
use App\Security\Authentication\Domain\UserRepositoryInterface;

final readonly class UserByEmail
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(string $email): ?UserInterface
    {
        return $this->userRepository->findUserByEmail($email);
    }
}
