<?php

declare(strict_types=1);

namespace Tests\Unit\Network;

use App\Support\Network\Http\RedirectResolver;
use App\Support\Network\UnsafeUrlException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RedirectResolverTest extends TestCase
{
    #[DataProvider('locations')]
    public function test_resolves_locations(string $base, string $location, string $expected): void
    {
        $this->assertSame($expected, (new RedirectResolver)->resolve($base, $location));
    }

    /** @return array<string, array{string, string, string}> */
    public static function locations(): array
    {
        return [
            'absolute' => ['https://example.com/a/b', 'https://other.example/x?y=1', 'https://other.example/x?y=1'],
            'scheme relative' => ['https://example.com/a', '//cdn.example.com/x', 'https://cdn.example.com/x'],
            'rooted' => ['https://example.com/a/b', '/root', 'https://example.com/root'],
            'relative file' => ['https://example.com/a/b', 'next', 'https://example.com/a/next'],
            'relative directory' => ['https://example.com/a/b', 'c/d', 'https://example.com/a/c/d'],
            'parent' => ['https://example.com/a/b/c', '../d', 'https://example.com/a/d'],
            'query only' => ['https://example.com/a/b?x=1', '?y=2', 'https://example.com/a/b?y=2'],
            'fragment only' => ['https://example.com/a/b?x=1', '#top', 'https://example.com/a/b?x=1'],
            'fragment stripped' => ['https://example.com/a', '/b#section', 'https://example.com/b'],
            'dot segments' => ['https://example.com/a/', './b/../c', 'https://example.com/a/c'],
            'non default port' => ['https://example.com:443/a', '/b', 'https://example.com/b'],
            'trailing slash' => ['https://example.com/a', 'b/', 'https://example.com/b/'],
        ];
    }

    public function test_rejects_invalid_location(): void
    {
        $this->expectException(UnsafeUrlException::class);
        (new RedirectResolver)->resolve('https://example.com/', 'https://example.com/a b');
    }

    public function test_rejects_empty_location(): void
    {
        $this->expectException(UnsafeUrlException::class);
        (new RedirectResolver)->resolve('https://example.com/', '   ');
    }

    public function test_rejects_backslash_location(): void
    {
        $this->expectException(UnsafeUrlException::class);
        (new RedirectResolver)->resolve('https://example.com/', '\\\\evil.example');
    }
}
