<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain\Aggregate;

use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
use Webmozart\Assert\Assert;
use function App\urlsafe_base64_decode;
use function App\urlsafe_base64_encode;

/**
 * Simple JSON Web Token implementation based on RFC7519
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7519
 */
class JWT implements \Stringable
{
    public function __construct(
        public readonly Header $header,
        public readonly Payload $payload,
        public ?string $signature = null {
            get => $this->signature;
            set(?string $value) {
                if (isset($this->signature)) {
                    Assert::null($this->signature, 'Resigning token is forbidden');
                }

                $this->signature = $value;
            }
        },
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%s.%s', $this->imprint(), $this->signature());
    }

    public function imprint(): string
    {
        return sprintf('%s.%s', urlsafe_base64_encode((string) $this->header), urlsafe_base64_encode((string) $this->payload));
    }

    public function signature(): string
    {
        return null !== $this->signature ? urlsafe_base64_encode($this->signature) : '';
    }

    public static function expand(string $jwtString): static
    {
        $tokenData = explode('.', $jwtString);

        if (3 !== count($tokenData)) {
            throw new InvalidJWTException(sprintf('"%s" is not a valid JWT string', $jwtString));
        }

        list($header, $payload, $signature) = array_map(function (string $part) {
            $part = urlsafe_base64_decode($part);

            if (json_validate($part)) {
                return json_decode($part, true);
            }

            return $part;
        }, $tokenData);

        return new self(
            new Header($header['typ'], $header['alg']),
            new Payload($payload),
            $signature
        );
    }
}
