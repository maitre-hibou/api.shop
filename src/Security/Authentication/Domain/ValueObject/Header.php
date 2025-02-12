<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain\ValueObject;

use Webmozart\Assert\Assert;

class Header implements \JsonSerializable, \Stringable
{
    public const string  TYP_JWT = 'JWT';

    public const string ALG_HS256 = 'HS256';
    public const string ALG_RS256 = 'RS256';

    public const array SUPPORT_ALGS = [
        self::ALG_HS256 => [],
        self::ALG_RS256 => [],
    ];

    public function __construct(
        public readonly string $typ = self::TYP_JWT,
        public readonly string $alg = self::ALG_RS256,
    ) {
        Assert::eq($this->typ, self::TYP_JWT);
        Assert::inArray($this->alg, array_keys(self::SUPPORT_ALGS));
    }

    public function jsonSerialize(): array
    {
        return [
            'typ' => $this->typ,
            'alg' => $this->alg,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this);
    }
}
