<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?UserInterface;
}
