<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\DependencyInjection;

use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * Component used to load Symfony services definition files from within domains directories.
 *
 * @see src/Kernel.php
 */
final class DomainConfigLoader
{
    public static function load(string $projectDir, LoaderInterface $loader): void
    {
        $files = self::recursiveGlob(sprintf('%s/src/**/services.{yaml,yml}', $projectDir), GLOB_BRACE);

        array_walk($files, function (&$file) use ($loader) {
            $loader->load($file);
        });
    }

    private static function recursiveGlob(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR) as $directory) {
            $files = array_merge($files, self::recursiveGlob($directory.'/'.basename($pattern), $flags));
        }

        return $files;
    }
}
