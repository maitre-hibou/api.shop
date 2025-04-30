<?php

declare(strict_types=1);

namespace App\Security\Registration\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Registration\Application\Command;
use App\Security\Registration\Domain\Aggregate\User as DomainUser;
use App\Security\Registration\Domain\ValueObject\CGUAccepted;
use App\Security\Registration\Domain\ValueObject\ConfirmedPassword;
use App\Security\Registration\Domain\ValueObject\Email;
use App\Security\Registration\Domain\ValueObject\Password;
use App\Security\Registration\Domain\ValueObject\Username;
use App\Security\Registration\UI\Http\Resource\User;

final readonly class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private Command\CreateUser $createUserCommand,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof User) {
            throw new \InvalidArgumentException('Data is not an instance of User');
        }

        $user = new DomainUser(
            new Username($data->username),
            new Email($data->email),
            $pwd = new Password($data->password),
            new ConfirmedPassword($data->confirmedPassword, $pwd),
            new CGUAccepted($data->cguAccepted)
        );

        $userId = ($this->createUserCommand)($user);

        return new User(
            username: (string) $user->username,
            email: (string) $user->email,
            id: $userId
        );
    }
}
