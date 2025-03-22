<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Domain\ValueObject;

use App\Security\Authentication\Domain\ValueObject\Payload;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    public function payloadCreationFailsWhenMissingRequiredClaimProvider(): array
    {
        return [
            ['exp', 'JWT payload should contain a "exp" (expiration time) key'],
            ['iat', 'JWT payload should contain a "iat" (issued at) key'],
            ['iss', 'JWT payload should contain a "iss" (issuer) key'],
            ['sub', 'JWT payload should contain a "sub" (subject) key'],
        ];
    }

    /**
     * @dataProvider payloadCreationFailsWhenMissingRequiredClaimProvider
     */
    public function testPayloadCreationFailsWhenMissingRequiredClaim(string $claim, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $compliant = [
            'exp' => time() + 300,
            'iat' => time(),
            'iss' => 'API.Shop Tests',
            'sub' => 'Tester',
        ];

        unset($compliant[$claim]);

        new Payload($compliant);
    }

    public function testPayloadReadingAsArray(): void
    {
        $payload = new Payload([
            'exp' => time() + 300,
            'iat' => time(),
            'iss' => 'API.Shop Tests',
            'sub' => 'user@example.com'
        ]);

        $this->assertEquals('user@example.com', $payload['sub']);
    }

    public function testPayloadModificationThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('JWT payload are read-only');

        $payload = new Payload([
            'exp' => time() + 300,
            'iat' => time(),
            'iss' => 'API.Shop Tests',
            'sub' => 'user@example.com'
        ]);

        $payload['user'] = 'john.doe@example.com';
    }

    public function testPayloadDataSuppressionThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('JWT payload are read-only');

        $payload = new Payload([
            'exp' => time() + 300,
            'iat' => time(),
            'iss' => 'API.Shop Tests',
            'sub' => 'user@example.com'
        ]);

        unset($payload['sub']);
    }

    public function testPayloadUsageAsTraversable(): void
    {
        $data = [
            'exp' => time() + 300,
            'iat' => time(),
            'iss' => 'API.Shop Tests',
            'sub' => 'user@example.com'
        ];

        $payload = new Payload($data);

        foreach ($payload as $key => $value) {
            $this->assertEquals($data[$key], $value);
        }
    }

    public function testPayloadJsonSerialization(): void
    {
        $now = time();

        $data = [
            'exp' => $now + 300,
            'iat' => $now,
            'iss' => 'API.Shop Tests',
            'sub' => 'user@example.com'
        ];

        $payload = new Payload($data);

        $this->assertEquals(sprintf('{"exp":%d,"iat":%d,"iss":"API.Shop Tests","sub":"user@example.com"}', $now + 300, $now), json_encode($payload));
    }

    public function testPayloadIsStringable(): void
    {
        $now = time();

        $data = [
            'exp' => $now + 300,
            'iat' => $now,
            'iss' => 'API.Shop Tests',
            'sub' => 'user@example.com'
        ];

        $payload = new Payload($data);

        $this->assertEquals(sprintf('{"exp":%d,"iat":%d,"iss":"API.Shop Tests","sub":"user@example.com"}', $now + 300, $now), (string) $payload);
    }
}
