<?php

declare(strict_types=1);

namespace App\Security\Registration\Application\Command;

use App\Security\Registration\Domain\UserInterface;
use App\Security\Registration\Domain\UserRepositoryInterface;

final readonly class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(UserInterface $domainUser): string
    {
        return $this->userRepository->createUser($domainUser);
    }
}
