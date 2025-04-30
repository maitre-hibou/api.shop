<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Authentication\Application\Command;

use App\Security\Authentication\Application\Command\SignJWT;
use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\JWTAuthorityInterface;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;
use PHPUnit\Framework\TestCase;

final class SignJWTTest extends TestCase
{
    public function testSignCommandShouldSignJWTWithAuthority(): void
    {
        $jwt = new JWT(
            new Header(),
            new Payload([
                'exp' => time() + 300,
                'iat' => time(),
                'iss' => 'API.Shop Tests',
                'sub' => 'test@example.com',
            ])
        );

        $jwtAuthority = $this->createMock(JWTAuthorityInterface::class);
        $jwtAuthority->expects($this->once())
            ->method('sign')
            ->with($this->identicalTo($jwt));

        $signJWTCommand = new SignJWT($jwtAuthority);

        $signJWTCommand($jwt);
    }
}
