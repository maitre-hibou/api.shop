<?php

namespace App\Security\Registration\Domain;

interface UserRepositoryInterface
{
    public function createUser(UserInterface $user): string;
}
