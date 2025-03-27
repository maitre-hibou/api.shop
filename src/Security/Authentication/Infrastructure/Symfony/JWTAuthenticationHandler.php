<?php

declare(strict_types=1);

namespace App\Security\Authentication\Infrastructure\Symfony;

use App\Security\Authentication\Application\Command as Command;
use App\Security\Authentication\Application\Query as Query;
use App\Security\Authentication\Domain\UserInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class JWTAuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    public function __construct(
        private Query\IssueJWT  $issueQuery,
        private Command\SignJWT $signCommand,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $code = Response::HTTP_BAD_REQUEST;
        $message = $exception->getMessage();

        if (null !== $exception->getPrevious()) {
            $code = match(get_class($exception->getPrevious())) {
                DisabledException::class => Response::HTTP_UNAUTHORIZED,
                default => Response::HTTP_BAD_REQUEST,
            };
            $message = $exception->getPrevious()->getMessage();
        }

        return new JsonResponse([
            'message' => $message,
        ], $code);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        /** @var UserInterface $user */
        $user = $token->getUser();

        $jwt = ($this->issueQuery)($user);

        ($this->signCommand)($jwt);

        return new JsonResponse([
            'access_token' => (string) $jwt,
        ]);
    }
}
