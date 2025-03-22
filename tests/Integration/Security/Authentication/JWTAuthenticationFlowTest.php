<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security\Authentication;

use App\Security\Authentication\Application\Command\SignJWT;
use App\Security\Authentication\Application\Query\IssueJWT;
use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\JWTAuthorityInterface;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
use App\Security\Authentication\Infrastructure\JWTAuthority;
use App\Security\Authentication\Infrastructure\Symfony\User;
use PHPUnit\Framework\TestCase;

final class JWTAuthenticationFlowTest extends TestCase
{
    private JWTAuthorityInterface $jwtAuthority;

    private IssueJWT $issueJWT;

    private SignJWT $signJWT;

    private array $jwtConfig;

    protected function setUp(): void
    {
        $this->jwtConfig = [
            'mode' => 'hash_hmac',
            'ttl' => 300,
            'hash_hmac' => ['passphrase' => 'test-secret'],
        ];

        $this->jwtAuthority = new JWTAuthority($this->jwtConfig);
        $this->issueJWT = new IssueJWT($this->jwtConfig);
        $this->signJWT = new SignJWT($this->jwtAuthority);
    }

    public function testCompleteJWTAuthenticationFlow(): void
    {
        $user = new User('test@example.com', 'hashed-password', ['ROLE_USER']);

        $jwt = ($this->issueJWT)($user);

        ($this->signJWT)($jwt);

        $jwtString = (string)$jwt;

        $receivedJwt = JWT::expand($jwtString);

        $isValid = $this->jwtAuthority->verify($receivedJwt);

        $isExpired = $this->jwtAuthority->expired($receivedJwt);

        $this->assertNotEmpty($jwt->signature, 'JWT should have a signature after signing');
        $this->assertTrue($isValid, 'JWT signature should be valid');
        $this->assertFalse($isExpired, 'JWT should not be expired');
        $this->assertEquals($user->getUserIdentifier(), $receivedJwt->payload['sub'], 'Subject should match user email');
        $this->assertEquals(['ROLE_USER'], $receivedJwt->payload['roles'], 'Roles should be preserved');
    }

    public function testJWTVerificationFailsWhenPayloadIsModified(): void
    {
        $this->expectException(InvalidJWTException::class);

        $user = new User('test@example.com', 'hashed-password', ['ROLE_USER']);

        $iat = time();
        $jwt = new JWT(
            new Header(alg: 'HS256'),
            new Payload([
                'iat' => $iat,
                'exp' => $iat + $this->jwtConfig['ttl'],
                'iss' => 'API.Shop',
                'sub' => $user->getUserIdentifier(),
                'roles' => $user->roles,
            ])
        );

        ($this->signJWT)($jwt);

        $originalString = (string)$jwt;

        $iat = time();
        $tamperedJwt = new JWT(
            new Header(alg: Header::ALG_HS256),
            new Payload([
                'iat' => $iat,
                'exp' => $iat + $this->jwtConfig['ttl'],
                'iss' => 'API.Shop',
                'sub' => 'hacker@example.com',
                'roles' => ['ROLE_ADMIN'],
            ])
        );

        $parts = explode('.', $originalString);
        $tamperedParts = explode('.', (string) $tamperedJwt);
        $tamperedString = $parts[0] . '.' . $tamperedParts[1] . '.' . $parts[2];

        $receivedJwt = JWT::expand($tamperedString);

        $this->jwtAuthority->verify($receivedJwt);
    }
}
