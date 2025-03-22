<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Application\Query;

use App\Security\Authentication\Application\Query\IssueJWT;
use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Infrastructure\Symfony\User;
use PHPUnit\Framework\TestCase;

final class IssueJWTTest extends TestCase
{
    private array $jwtConfig = [
        'mode' => 'hash_hmac',
        'ttl' => 300,
        'hash_hmac' => ['passphrase' => 'secret'],
    ];

    public function testIssueJWTReturnsValidJWTForUser(): void
    {
        $user = new User('test@example.com', 'hashed-password', ['ROLE_USER']);

        $issueJWT = new IssueJWT($this->jwtConfig);

        $jwt = $issueJWT($user);

        $this->assertInstanceOf(JWT::class, $jwt);
        $this->assertEquals(Header::ALG_HS256, $jwt->header->alg);
        $this->assertEquals('API.Shop', $jwt->payload['iss']);
        $this->assertEquals('test@example.com', $jwt->payload['sub']);
        $this->assertEquals(['ROLE_USER'], $jwt->payload['roles']);
        $this->assertArrayHasKey('exp', $jwt->payload);
        $this->assertArrayHasKey('iat', $jwt->payload);

        $this->assertEquals($jwt->payload['iat'] + $this->jwtConfig['ttl'], $jwt->payload['exp']);
    }

    public function testIssueJWTUsesRS256AlgorithmWhenOpenSSLModeIsUsed(): void
    {
        $openSslConfig = array_merge($this->jwtConfig, ['mode' => 'openssl']);

        $user = new User('test@example.com', 'hashed-password', ['ROLE_USER']);

        $issueJWT = new IssueJWT($openSslConfig);

        $jwt = $issueJWT($user);

        $this->assertEquals(Header::ALG_RS256, $jwt->header->alg);
    }
}
