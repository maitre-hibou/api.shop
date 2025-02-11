<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

abstract class AbstractViewController
{
    public function __construct(
        protected readonly Environment $twig
    ) {
    }

    protected function render(string $view, array $parameters = [], int $statusCode = Response::HTTP_OK): Response
    {
        return new Response($this->twig->render($view, $parameters), $statusCode);
    }
}
