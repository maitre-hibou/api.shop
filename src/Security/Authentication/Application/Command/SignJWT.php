<?php

declare(strict_types=1);

namespace App\Security\Authentication\Application\Command;

use App\Security\Authentication\Domain\Aggregate\JWT;
use App\Security\Authentication\Domain\JWTAuthorityInterface;

final readonly class SignJWT
{
    public function __construct(
        private JWTAuthorityInterface $jwtAuthority,
    ) {
    }

    public function __invoke(JWT &$jwt): void
    {
        $this->jwtAuthority->sign($jwt);
    }
}
