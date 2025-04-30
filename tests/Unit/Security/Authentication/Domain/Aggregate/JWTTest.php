<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Domain\Aggregate;

use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\Exception\InvalidJWTException;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
use PHPUnit\Framework\TestCase;

class JWTTest extends TestCase
{
    public function testJWTStringRepresentation(): void
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

        $this->assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJleHAiOiIxNjY1NDA5NTc3IiwiaWF0IjoiMTY2NTQwOTI3NyIsImlzcyI6IkFQSS5TaG9wIFRlc3RzIiwic3ViIjoid29ybGQifQ.', (string) $jwt);

        $jwt = new JWT(
            new Header(alg: Header::ALG_HS256),
            new Payload([
                'exp' => '1665409577',
                'iat' => '1665409277',
                'iss' => 'API.Shop Tests',
                'sub' => 'world',
            ])
        );

        $this->assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOiIxNjY1NDA5NTc3IiwiaWF0IjoiMTY2NTQwOTI3NyIsImlzcyI6IkFQSS5TaG9wIFRlc3RzIiwic3ViIjoid29ybGQifQ.', (string) $jwt);
    }

    public function testJWTExpansion(): void
    {
        $jwt = JWT::expand('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJleHAiOiIxNjY1NDA5NTc3IiwiaWF0IjoiMTY2NTQwOTI3NyIsImlzcyI6IkFQSS5TaG9wIFRlc3RzIiwic3ViIjoid29ybGQifQ.');

        $this->assertEquals('world', $jwt->payload['sub']);
    }

    public function testJWTExpansionThrowsExceptionOnInvalidString(): void
    {
        $this->expectException(InvalidJWTException::class);
        $this->expectExceptionMessage('"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9" is not a valid JWT string');

        JWT::expand('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOiIxNjY1NDA5Mjc3IiwiaGVsbG8iOiJ3b3JsZCJ9');
    }
}
