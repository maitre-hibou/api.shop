<?php

declare(strict_types=1);

namespace App\Tests\Application\Content\UI\Http\Controller;

use App\Tests\Application\WebTestCase;

class HomepageControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Shop');
    }
}
