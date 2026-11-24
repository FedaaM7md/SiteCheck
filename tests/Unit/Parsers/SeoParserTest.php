<?php

declare(strict_types=1);

namespace Tests\Unit\Parsers;

use App\Domain\SiteCheck\Parsers\SeoParser;
use PHPUnit\Framework\TestCase;

final class SeoParserTest extends TestCase
{
    private const FIXTURE = <<<'HTML'
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>  Example    Domain — a descriptive page title  </title>
    <meta name="description" content="A sufficiently long meta description for the example domain page.">
    <link rel="canonical" href="https://example.com/">
    <meta name="robots" content="noindex, follow">
    <meta property="og:title" content="OG Example">
    <meta property="og:description" content="OG description">
    <meta property="og:image" content="https://example.com/og.png">
    <meta property="og:url" content="https://example.com/">
</head>
<body>
    <h1>First heading</h1>
    <h1>Second heading</h1>
    <a href="/one">One</a>
    <a href="/two">Two</a>
    <img src="/a.png" alt="An image">
    <img src="/b.png">
    <img src="/c.png" alt="">
</body>
</html>
HTML;

    public function test_parses_page_metadata(): void
    {
        $analysis = (new SeoParser)->parse(self::FIXTURE);

        $this->assertTrue($analysis->parsed);
        $this->assertSame('Example Domain — a descriptive page title', $analysis->title);
        $this->assertSame('A sufficiently long meta description for the example domain page.', $analysis->description);
        $this->assertSame('https://example.com/', $analysis->canonical);
        $this->assertSame('noindex, follow', $analysis->robotsMeta);
        $this->assertSame(2, $analysis->h1Count);
        $this->assertSame(['First heading', 'Second heading'], $analysis->h1Texts);
        $this->assertSame('en-GB', $analysis->lang);
        $this->assertSame('width=device-width, initial-scale=1', $analysis->viewport);
        $this->assertSame('utf-8', $analysis->charset);
        $this->assertSame('OG Example', $analysis->openGraph['og:title']);
        $this->assertSame('https://example.com/og.png', $analysis->openGraph['og:image']);
        $this->assertSame(3, $analysis->imagesTotal);
        $this->assertSame(1, $analysis->imagesMissingAlt);
        $this->assertSame(1, $analysis->imagesEmptyAlt);
        $this->assertSame(2, $analysis->linksTotal);
    }

    public function test_handles_missing_optional_elements(): void
    {
        $analysis = (new SeoParser)->parse('<html><body><p>No metadata here.</p></body></html>');

        $this->assertTrue($analysis->parsed);
        $this->assertNull($analysis->title);
        $this->assertNull($analysis->description);
        $this->assertNull($analysis->canonical);
        $this->assertNull($analysis->lang);
        $this->assertNull($analysis->viewport);
        $this->assertSame(0, $analysis->h1Count);
        $this->assertSame([], $analysis->openGraph);
    }

    public function test_empty_document_is_marked_unparsed(): void
    {
        $analysis = (new SeoParser)->parse('   ');

        $this->assertFalse($analysis->parsed);
    }

    public function test_does_not_execute_scripts_or_load_external_resources(): void
    {
        $analysis = (new SeoParser)->parse(
            '<html><head><title>Safe</title></head><body><script>window.evil = true</script><img src="http://127.0.0.1/x.png"></body></html>',
        );

        $this->assertSame('Safe', $analysis->title);
        $this->assertSame(1, $analysis->imagesTotal);
        $this->assertSame(1, $analysis->imagesMissingAlt);
    }

    public function test_multiple_canonical_and_robots_variants(): void
    {
        $analysis = (new SeoParser)->parse(
            '<html><head><link rel="alternate canonical" href="/x"><meta name="Robots" content="index"></head><body></body></html>',
        );

        $this->assertSame('/x', $analysis->canonical);
        $this->assertSame('index', $analysis->robotsMeta);
    }
}
