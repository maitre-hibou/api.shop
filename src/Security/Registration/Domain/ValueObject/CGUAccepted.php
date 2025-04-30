<?php

namespace App\Security\Registration\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class CGUAccepted
{
    public function __construct(
        public bool $value
    ) {
        Assert::true($value);
    }
}
