<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain;

interface UserInterface
{
    public string $email {
        get;
    }

    public string $password {
        get;
    }

    public array $roles {
        get;
    }
}
