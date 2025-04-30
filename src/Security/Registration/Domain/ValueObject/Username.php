<?php

namespace App\Security\Registration\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Username implements \Stringable
{
    public function __construct(
        public string $value
    ) {
        Assert::notEmpty($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
