<?php

namespace DevWizard\Payify\Tests\Fixtures;

use GuzzleHttp\Psr7\Response;

class FixtureLoader
{
    public static function json(string $path, int $status = 200, array $headers = []): Response
    {
        $full = __DIR__.'/'.ltrim($path, '/');
        if (! file_exists($full)) {
            throw new \RuntimeException("Fixture not found: {$full}");
        }

        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            file_get_contents($full),
        );
    }

    public static function raw(string $path): string
    {
        $full = __DIR__.'/'.ltrim($path, '/');

        return file_get_contents($full);
    }
}
