<?php

declare(strict_types=1);

namespace App\Security\Registration\Domain;

use App\Security\Registration\Domain\ValueObject\CGUAccepted;
use App\Security\Registration\Domain\ValueObject\ConfirmedPassword;
use App\Security\Registration\Domain\ValueObject\Email;
use App\Security\Registration\Domain\ValueObject\Password;
use App\Security\Registration\Domain\ValueObject\Username;

interface UserInterface
{
    public Username $username {
        get;
    }

    public Email $email {
        get;
    }

    public Password $password {
        get;
    }

    public ConfirmedPassword $confirmedPassword {
        get;
    }

    public CGUAccepted $cguAccepted {
        get;
    }
}
