<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain\Aggregate;

use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
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
        private string $signature = '',
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
        return urlsafe_base64_encode($this->signature);
    }

    public function sign(string|\OpenSSLAsymmetricKey|\OpenSSLCertificate $key): void
    {
        list($function, $algorithm) = Header::SUPPORT_ALGS[$this->header->alg];

        switch ($function) {
            case 'hash_hmac':
                if (false === is_string($key)) {
                    throw new \InvalidArgumentException('Key must be a string when using hmac encryption');
                }
                $this->signature = hash_hmac($algorithm, $this->imprint(), $key);
                break;
            case 'openssl':
                try {
                    if (false === openssl_sign($this->imprint(), $this->signature, $key, $algorithm)) {
                        throw new \RuntimeException('OpenSSL failed to sign token');
                    }
                } catch (\Exception) {
                    throw new \RuntimeException('OpenSSL failed to sign token');
                }
                break;
        }
    }

    /**
     * @throws InvalidJWTException
     */
    public function verify(string|\OpenSSLCertificate|\OpenSSLAsymmetricKey $key): bool
    {
        list($function, $algorithm) = Header::SUPPORT_ALGS[$this->header->alg];

        switch ($function) {
            case 'hash_hmac':
                $hash = hash_hmac($algorithm, $this->imprint(), $key);

                if ($hash !== $this->signature) {
                    throw new InvalidJWTException('Invalid token signature');
                }

                break;
            case 'openssl':
                $success = openssl_verify($this->imprint(), $this->signature, $key, $algorithm);

                if (1 !== $success) {
                    throw new InvalidJWTException('Invalid token signature');
                }

                break;
            default:
                throw new InvalidJWTException('Unsupported algorithm');
        }

        return true;
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
