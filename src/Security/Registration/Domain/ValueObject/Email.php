<?php

declare(strict_types=1);

namespace App\Security\Registration\Domain\ValueObject;

use Webmozart\Assert\Assert;

final readonly class Email implements \Stringable
{
    public function __construct(
        public string $value
    ) {
        Assert::email($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
