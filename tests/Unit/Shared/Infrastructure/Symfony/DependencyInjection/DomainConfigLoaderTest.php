<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\DependencyInjection;

use App\Shared\Infrastructure\Symfony\DependencyInjection\DomainConfigLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;

class DomainConfigLoaderTest extends TestCase
{
    public function testDomainConfigLoading(): void
    {
        $loaderMock = $this->createMock(LoaderInterface::class);
        $loaderMock->expects($this->exactly(3))->method('load');

        DomainConfigLoader::load(dirname(__DIR__, 5).'/.stubs/structure', $loaderMock);
    }
}
