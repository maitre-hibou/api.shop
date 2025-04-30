<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Infrastructure;

use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
use App\Security\Authentication\Infrastructure\JWTAuthority;
use PHPUnit\Framework\TestCase;
use function App\urlsafe_base64_encode;

final class JWTAuthorityTest extends TestCase
{
    private array $defaultJWTParams;

    public function testJWTHmacSignature(): void
    {
        $jwt = new JWT(
            new Header(alg: Header::ALG_HS256),
            new Payload([
                'exp' => '1665409577',
                'iat' => '1665409277',
                'iss' => 'API.Shop Tests',
                'sub' => 'world',
            ])
        );

        $authority = new JWTAuthority($this->defaultJWTParams);

        $authority->sign($jwt);

        $this->assertEquals('4baa60f42e0d718d9e6ac7a27ec4af4f86e5069acff11b2db1e4a77461658e49', $jwt->signature);
    }

    public function testJWTOpenSSLSignature(): void
    {
        $jwt = new JWT(
            new Header(),
            new Payload([
                'exp' => '1665409577',
                'iat' => '1665409277',
                'iss' => 'API.Shop Tests',
                'sub' => 'world',
            ])
        );

        $authority = new JWTAuthority(array_merge($this->defaultJWTParams, ['mode' => 'openssl']));

        $authority->sign($jwt);

        $this->assertEquals('ZsTZ0G5UN-ZK-4cOYJqAJSrpJzXRFByJfJd1jvs6LMJq2VE-mlETv1YibEvX961kLAyocoN_WTfploNuYX_Ef00_p0DElgW9uoztCgJivKJMUjMDzAYeJGNAoL5AuNJCpRaeBL3iarXpKt3deUh0xi38GQSJFmPHP5KXRPpaTBfI239tacNpbxQfr6JQ-MIfCZZTwaSpuppELstZO8EPWY2nxqEreQPuM_oU87trRVaXCRfI9dcnUOkmtAe5yZ5RLNom4555AAjQ_vv6JQenpw5V7QG5xocHeUxHGKxTENk698DYbdo9wyhAwg_dJ7zlBV0eUa2FlVSxgueTKdeuaw', $jwt->signature());
    }

    public function testJWTOpenSSLVerification(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJleHAiOiIxNjY1NDA5NTc3IiwiaWF0IjoiMTY2NTQwOTI3NyIsImlzcyI6IkFQSS5TaG9wIFRlc3RzIiwic3ViIjoid29ybGQifQ.ZsTZ0G5UN-ZK-4cOYJqAJSrpJzXRFByJfJd1jvs6LMJq2VE-mlETv1YibEvX961kLAyocoN_WTfploNuYX_Ef00_p0DElgW9uoztCgJivKJMUjMDzAYeJGNAoL5AuNJCpRaeBL3iarXpKt3deUh0xi38GQSJFmPHP5KXRPpaTBfI239tacNpbxQfr6JQ-MIfCZZTwaSpuppELstZO8EPWY2nxqEreQPuM_oU87trRVaXCRfI9dcnUOkmtAe5yZ5RLNom4555AAjQ_vv6JQenpw5V7QG5xocHeUxHGKxTENk698DYbdo9wyhAwg_dJ7zlBV0eUa2FlVSxgueTKdeuaw');

        $authority = new JWTAuthority(array_merge($this->defaultJWTParams, ['mode' => 'openssl']));

        $this->assertTrue($authority->verify($jwt));
    }

    public function testJWTHMACVerification(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOiIxNjY1NDA5NTc3IiwiaWF0IjoiMTY2NTQwOTI3NyIsImlzcyI6IkFQSS5TaG9wIFRlc3RzIiwic3ViIjoid29ybGQifQ.NGJhYTYwZjQyZTBkNzE4ZDllNmFjN2EyN2VjNGFmNGY4NmU1MDY5YWNmZjExYjJkYjFlNGE3NzQ2MTY1OGU0OQ');

        $authority = new JWTAuthority($this->defaultJWTParams);

        $this->assertTrue($authority->verify($jwt));
    }

    public function testJWTVerifyThrowsExceptionOnFakedPayload(): void
    {
        $this->expectException(InvalidJWTException::class);
        $this->expectExceptionMessage('Invalid token signature');

        $fakedPayload = urlsafe_base64_encode(json_encode([
            'exp' => '1665409577',
            'iat' => '1665409277',
            'iss' => 'API.Shop Tests',
            'sub' => 'everybody',
        ]));

        $jwt = JWT::expand(sprintf('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.%s.OWVkNTZkNTI2YTZmYjEwYzQ0NzYwZTlmMTZjYjMwNzYwM2YwN2Y1ZGJiOGJkNDIzNWY1M2FkNzE1NjdiOGU3Mw', $fakedPayload));

        $authority = new JWTAuthority($this->defaultJWTParams);

        $authority->verify($jwt);
    }

    public function testExpiredMethod(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJleHAiOiIxNjY1NDA5NTc3IiwiaWF0IjoiMTY2NTQwOTI3NyIsImlzcyI6IkFQSS5TaG9wIFRlc3RzIiwic3ViIjoid29ybGQifQ.ZsTZ0G5UN-ZK-4cOYJqAJSrpJzXRFByJfJd1jvs6LMJq2VE-mlETv1YibEvX961kLAyocoN_WTfploNuYX_Ef00_p0DElgW9uoztCgJivKJMUjMDzAYeJGNAoL5AuNJCpRaeBL3iarXpKt3deUh0xi38GQSJFmPHP5KXRPpaTBfI239tacNpbxQfr6JQ-MIfCZZTwaSpuppELstZO8EPWY2nxqEreQPuM_oU87trRVaXCRfI9dcnUOkmtAe5yZ5RLNom4555AAjQ_vv6JQenpw5V7QG5xocHeUxHGKxTENk698DYbdo9wyhAwg_dJ7zlBV0eUa2FlVSxgueTKdeuaw');

        $authority = new JWTAuthority(array_merge($this->defaultJWTParams, ['mode' => 'openssl']));

        $this->assertTrue($authority->expired($jwt));
    }

    protected function setUp(): void
    {
        $this->defaultJWTParams = [
            'mode' => 'hash_hmac',
            'ttl' => 300,
            'hash_hmac' => ['passphrase' => 'secret'],
            'openssl' => [
                'private_key' => sprintf('%s/.stubs/certs/private.pem', dirname(__DIR__, 4)),
                'private_key_pass' => 'secret',
                'public_key' => sprintf('%s/.stubs/certs/public.pem', dirname(__DIR__, 4)),
            ],
        ];
    }
}
