<?php

declare(strict_types=1);

namespace App\Content\UI\Http\Controller;

use App\Shared\UI\Http\Controller\AbstractViewController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class HomepageController extends AbstractViewController
{
    #[Route(path: '/', name: 'homepage', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('@content/homepage.html.twig');
    }
}
