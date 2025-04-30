<?php

declare(strict_types=1);

namespace App\Tests\Application\Security\Registration\UI\Http\Resource;

use App\Tests\Application\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends WebTestCase
{
    public function testCreateUser(): void
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'confirmedPassword' => 'Password123',
            'cguAccepted' => true
        ];

        $this->client->request(
            method: 'POST',
            uri: '/api/users',
            server: ['CONTENT_TYPE' => 'application/ld+json'],
            content: json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('username', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('confirmedPassword', $responseData);
        $this->assertArrayNotHasKey('cguAccepted', $responseData);

        $this->assertEquals('testuser', $responseData['username']);
        $this->assertEquals('test@example.com', $responseData['email']);
        $this->assertNotEmpty($responseData['id']);
    }

    public function testCreateUserWithInvalidData(): void
    {
        $invalidUserData = [
            'username' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'confirmedPassword' => 'different',
            'cguAccepted' => false
        ];

        $this->client->request(
            method: 'POST',
            uri: '/api/users',
            server: ['CONTENT_TYPE' => 'application/ld+json',],
            content: json_encode($invalidUserData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('violations', $responseData);
        $violations = $responseData['violations'];

        // Verify all expected validation errors are present
        $this->assertViolation($violations, 'username', 'Username is required');
        $this->assertViolation($violations, 'email', 'Email is not valid');
        $this->assertViolation($violations, 'password', 'Password must be at least 8 characters long');
        $this->assertViolation($violations, 'confirmedPassword', 'Passwords do not match');
        $this->assertViolation($violations, 'cguAccepted', 'You must accept the CGU');
    }

    /**
     * Helper method to check for a specific violation in the API response
     */
    private function assertViolation(array $violations, string $propertyPath, string $message): void
    {
        $found = false;
        foreach ($violations as $violation) {
            if ($violation['propertyPath'] === $propertyPath && $violation['message'] === $message) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            sprintf('Expected violation for property "%s" with message "%s" not found', $propertyPath, $message)
        );
    }
}
