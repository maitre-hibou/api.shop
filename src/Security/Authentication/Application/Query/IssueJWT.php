<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Query;

use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\UserInterface;
use App\Security\Authentication\Domain\ValueObject\Header;
use App\Security\Authentication\Domain\ValueObject\Payload;

final readonly class IssueJWT
{
    public function __construct(
        private array $jwtConfig,
    ) {
    }

    public function __invoke(UserInterface $user): JWT
    {
        $jwtMode = $this->jwtConfig['mode'];

        $iat = time();

        return new JWT(
            new Header(alg: $jwtMode === 'openssl' ? 'RS256' : 'HS256'),
            new Payload(array_merge(['iat' => $iat, 'iss' => 'API.Shop', 'exp' => $iat + $this->jwtConfig['ttl']], [
                'sub' => $user->email,
                'roles' => $user->roles,
            ])),
        );
    }
}
