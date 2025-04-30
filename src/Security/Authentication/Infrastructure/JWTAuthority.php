<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure;

use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\JWTAuthorityInterface;
use App\Security\Authentication\Domain\ValueObject\Header;
use Webmozart\Assert\Assert;

final readonly class JWTAuthority implements JWTAuthorityInterface
{
    public function __construct(
        private array $jwtConfig
    ) {
    }

    public function sign(JWT &$jwt): void
    {
        $secretKey = $this->jwtConfig['mode'] === 'openssl' ?
            openssl_pkey_get_private(
                file_get_contents($this->jwtConfig['openssl']['private_key']),
                $this->jwtConfig['openssl']['private_key_pass']
            ) :
            $this->jwtConfig['hash_hmac']['passphrase']
        ;

        list($function, $algorithm) = Header::SUPPORT_ALGS[$jwt->header->alg];

        switch ($function) {
            case 'hash_hmac':
                if (false === is_string($secretKey)) {
                    throw new \InvalidArgumentException('Key must be a string when using hmac encryption');
                }
                $jwt->signature = hash_hmac($algorithm, $jwt->imprint(), $secretKey);
                break;
            case 'openssl':
                try {
                    $signature = null;
                    if (false === openssl_sign($jwt->imprint(), $signature, $secretKey, $algorithm)) {
                        throw new \RuntimeException('OpenSSL failed to sign token');
                    }
                    $jwt->signature = $signature;
                } catch (\Exception) {
                    throw new \RuntimeException('OpenSSL failed to sign token');
                }
                break;
        }
    }

    public function verify(JWT $jwt): bool
    {
        $publicKey = $this->jwtConfig['mode'] === 'openssl' ?
            openssl_pkey_get_public(
                file_get_contents($this->jwtConfig['openssl']['public_key']),
            ) :
            $this->jwtConfig['hash_hmac']['passphrase']
        ;

        list($function, $algorithm) = Header::SUPPORT_ALGS[$jwt->header->alg];

        switch ($function) {
            case 'hash_hmac':
                $hash = hash_hmac($algorithm, $jwt->imprint(), $publicKey);

                if ($hash !== $jwt->signature) {
                    throw new InvalidJWTException('Invalid token signature');
                }

                break;
            case 'openssl':
                $success = openssl_verify($jwt->imprint(), $jwt->signature, $publicKey, $algorithm);

                if (1 !== $success) {
                    throw new InvalidJWTException('Invalid token signature');
                }

                break;
            default:
                throw new InvalidJWTException('Unsupported algorithm');
        }

        return true;
    }

    public function expired(JWT $jwt): bool
    {
        $payload = $jwt->payload;

        Assert::keyExists($payload, 'exp');
        Assert::keyExists($payload, 'iat');

        return $payload['exp'] < time() || $payload['iat'] > time() + $this->jwtConfig['ttl'];
    }
}
