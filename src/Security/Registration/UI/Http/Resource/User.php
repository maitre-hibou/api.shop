<?php

declare(strict_types=1);

namespace App\Security\Registration\UI\Http\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Security\Registration\Infrastructure\ApiPlatform\State\UserProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'User',
    operations: [
        new Post(
            status: 201,
            normalizationContext: ['groups' => ['user:read']],
            denormalizationContext: ['groups' => ['user:write']],
            validationContext: ['groups' => ['Default', 'user:write']],
            processor: UserProcessor::class
        )
    ]
)]
final class User
{
    public function __construct(
        #[Groups(['user:write', 'user:read'])]
        #[Assert\NotBlank(message: 'Username is required')]
        public string $username,

        #[Groups(['user:write', 'user:read'])]
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Email is not valid')]
        public string $email,

        #[Groups(['user:write'])]
        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters long')]
        public ?string $password = null,

        #[Groups(['user:write'])]
        #[Assert\NotBlank(message: 'Password confirmation is required')]
        #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
        public ?string $confirmedPassword = null,

        #[Groups(['user:write'])]
        #[Assert\IsTrue(message: 'You must accept the CGU')]
        public bool $cguAccepted = false,

        #[Groups(['user:read'])]
        public ?string $id = null
    ) {
    }
}
