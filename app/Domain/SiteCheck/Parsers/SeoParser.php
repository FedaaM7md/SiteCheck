<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Parsers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Local HTML analyser. LIBXML_NONET is mandatory: parsing must never fetch
 * external entities or DTDs.
 */
final class SeoParser
{
    public function parse(string $html): SeoAnalysis
    {
        if (trim($html) === '') {
            return SeoAnalysis::unparsed();
        }

        // Cut pathological documents early; the transport already caps size.
        if (strlen($html) > 2_000_000) {
            $html = substr($html, 0, 2_000_000);
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return SeoAnalysis::unparsed();
        }

        $xpath = new DOMXPath($document);

        $title = $this->cleanText($this->firstNodeValue($xpath, '//title'));
        $description = $this->metaContent($xpath, 'description');
        $canonical = null;
        foreach ($xpath->query('//link[@rel]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $rel = strtolower($node->getAttribute('rel'));
            if (in_array('canonical', preg_split('/\s+/', $rel) ?: [], true)) {
                $canonical = trim($node->getAttribute('href'));
                break;
            }
        }

        $robots = $this->metaContent($xpath, 'robots');
        $viewport = $this->metaContent($xpath, 'viewport');

        $htmlNode = $document->getElementsByTagName('html')->item(0);
        $lang = $htmlNode instanceof DOMElement ? trim($htmlNode->getAttribute('lang')) : null;
        $lang = $lang === '' ? null : $lang;

        $charset = null;
        foreach ($xpath->query('//meta[@charset]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $charset = strtolower(trim($node->getAttribute('charset')));
                break;
            }
        }

        $h1Texts = [];
        foreach ($xpath->query('//h1') ?: [] as $node) {
            $text = $this->cleanText($node->textContent);
            if ($text !== null && $text !== '') {
                $h1Texts[] = $text;
            }
        }

        $openGraph = [];
        foreach ($xpath->query('//meta[@property]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $property = strtolower(trim($node->getAttribute('property')));
            if (str_starts_with($property, 'og:') && ! isset($openGraph[$property])) {
                $openGraph[$property] = trim($node->getAttribute('content'));
            }
        }

        $imagesTotal = 0;
        $imagesMissingAlt = 0;
        $imagesEmptyAlt = 0;
        foreach ($xpath->query('//img') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $imagesTotal++;
            if (! $node->hasAttribute('alt')) {
                $imagesMissingAlt++;
            } elseif (trim($node->getAttribute('alt')) === '') {
                $imagesEmptyAlt++;
            }
        }

        $linksTotal = $xpath->query('//a[@href]')?->length ?? 0;

        return new SeoAnalysis(
            parsed: true,
            title: $title,
            description: $description,
            canonical: $canonical,
            robotsMeta: $robots,
            h1Count: count($h1Texts),
            h1Texts: array_slice($h1Texts, 0, 5),
            lang: $lang,
            viewport: $viewport,
            charset: $charset,
            openGraph: $openGraph,
            imagesTotal: $imagesTotal,
            imagesMissingAlt: $imagesMissingAlt,
            imagesEmptyAlt: $imagesEmptyAlt,
            linksTotal: $linksTotal,
        );
    }

    private function firstNodeValue(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);

        return $nodes !== false && $nodes->length > 0 ? $nodes->item(0)?->nodeValue : null;
    }

    private function metaContent(DOMXPath $xpath, string $name): ?string
    {
        foreach ($xpath->query('//meta[@name]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            if (strtolower(trim($node->getAttribute('name'))) === $name) {
                $content = trim($node->getAttribute('content'));

                return $content === '' ? null : $content;
            }
        }

        return null;
    }

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
