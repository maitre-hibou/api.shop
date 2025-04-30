<?php

declare(strict_types=1);

namespace App\Security\Registration\Domain\ValueObject;

use Webmozart\Assert\Assert;

readonly class Password implements \Stringable
{
    public function __construct(
        private string $value
    ) {
        Assert::notEmpty($value);
        //TODO : add here more validation rules for user password
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
