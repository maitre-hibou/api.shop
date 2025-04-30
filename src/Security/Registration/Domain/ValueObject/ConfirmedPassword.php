<?php

declare(strict_types=1);

namespace App\Security\Registration\Domain\ValueObject;

use Webmozart\Assert\Assert;

readonly class ConfirmedPassword extends Password
{
    public function __construct(
        private string $value,
        private Password $compared
    ) {
        parent::__construct($value);

        Assert::eq($value, (string) $this->compared);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
