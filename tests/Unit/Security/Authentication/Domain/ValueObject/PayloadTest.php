<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Domain\ValueObject;

use App\Security\Authentication\Domain\ValueObject\Payload;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    public function testPayloadCreationFailsWhenNoIatFieldIsProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT payload should contain an "iat" key');

        new Payload([]);
    }

    public function testPayloadReadingAsArray(): void
    {
        $payload = new Payload(['iat' => time(), 'email' => 'user@example.com']);

        $this->assertEquals('user@example.com', $payload['email']);
    }

    public function testPayloadModificationThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('JWT payload are read-only');

        $payload = new Payload(['iat' => time(), 'email' => 'user@example.com']);

        $payload['user'] = 'john.doe@example.com';
    }

    public function testPayloadDataSuppressionThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('JWT payload are read-only');

        $payload = new Payload(['iat' => time(), 'email' => 'user@example.com']);

        unset($payload['user']);
    }

    public function testPayloadUsageAsTraversable(): void
    {
        $data = [
            'iat' => time(),
            'email' => 'user@example.com',
        ];

        $payload = new Payload($data);

        foreach ($payload as $key => $value) {
            $this->assertEquals($data[$key], $value);
        }
    }

    public function testPayloadJsonSerialization(): void
    {
        $now = time();

        $payload = new Payload(['iat' => $now]);

        $this->assertEquals(sprintf('{"iat":%d}', $now), json_encode($payload));
    }

    public function testPayloadIsStringable(): void
    {
        $now = time();

        $payload = new Payload(['iat' => $now]);

        $this->assertEquals(sprintf('{"iat":%d}', $now), (string) $payload);
    }
}
