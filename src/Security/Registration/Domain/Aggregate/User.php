<?php

declare(strict_types=1);

namespace App\Security\Registration\Domain\Aggregate;

use App\Security\Registration\Domain\UserInterface;
use App\Security\Registration\Domain\ValueObject\CGUAccepted;
use App\Security\Registration\Domain\ValueObject\ConfirmedPassword;
use App\Security\Registration\Domain\ValueObject\Email;
use App\Security\Registration\Domain\ValueObject\Password;
use App\Security\Registration\Domain\ValueObject\Username;

readonly class User implements UserInterface
{
    public function __construct(
        public Username $username,
        public Email $email,
        public Password $password,
        public ConfirmedPassword $confirmedPassword,
        public CGUAccepted $cguAccepted
    ) {
    }
}
