<?php

declare(strict_types=1);

namespace App\Security\Authentication\Domain;

use App\Security\Authentication\Domain\Aggregate\JWT;

interface JWTAuthorityInterface
{
    public function sign(JWT &$jwt): void;

    public function verify(JWT $jwt): bool;

    public function expired(JWT $jwt): bool;
}
