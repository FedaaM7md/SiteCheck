<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Checks;

use App\Domain\SiteCheck\Dto\Finding;
use App\Domain\SiteCheck\Dto\SectionResult;
use App\Domain\SiteCheck\Parsers\SeoAnalysis;
use App\Domain\SiteCheck\Parsers\SeoParser;
use App\Support\Network\Http\FetchFailedException;
use App\Support\Network\Http\HttpFetchResult;
use App\Support\Network\Http\SafeHttpFetcher;
use App\Support\Network\UnsafeUrlException;
use App\Support\Network\ValidatedUrl;
use Throwable;

final class SeoCheck
{
    public function __construct(
        private readonly SafeHttpFetcher $fetcher,
        private readonly SeoParser $parser = new SeoParser,
    ) {}

    public function run(ValidatedUrl $target, ?HttpFetchResult $primary): SectionResult
    {
        if ($primary === null) {
            return new SectionResult('seo', [
                Finding::fail('seo_unreachable', 'seo', 4),
            ], [], 'http_unreachable');
        }

        $findings = [];
        $analysis = SeoAnalysis::unparsed();
        $isHtml = $primary->response->isHtml();

        if ($isHtml) {
            $analysis = $this->parser->parse($primary->body());
        } else {
            $findings[] = Finding::warn('seo_content_type', 'seo', 1, [
                'type' => $primary->response->contentType(),
            ]);
        }

        if ($analysis->parsed) {
            $findings = array_merge($findings, $this->pageFindings($analysis, $primary));
        }

        $robots = $this->probe($target->withPath('/robots.txt'));
        $robotsExists = $robots['status'] !== null && $robots['status'] >= 200 && $robots['status'] < 300;
        $findings[] = $robotsExists
            ? Finding::pass('seo_robots_txt', 'seo', 2)
            : Finding::warn('seo_robots_txt', 'seo', 2);

        $sitemap = $this->probe($target->withPath('/sitemap.xml'));
        $sitemapBody = strtolower($sitemap['body'] ?? '');
        $sitemapValid = $sitemap['status'] !== null
            && $sitemap['status'] >= 200
            && $sitemap['status'] < 300
            && (str_contains($sitemapBody, '<urlset') || str_contains($sitemapBody, '<sitemapindex'));

        $findings[] = $sitemapValid
            ? Finding::pass('seo_sitemap', 'seo', 1)
            : Finding::warn('seo_sitemap', 'seo', 1);

        $details = [
            'page' => $this->analysisToArray($analysis),
            'robots_txt' => [
                'available' => $robotsExists,
                'status' => $robots['status'],
                'url' => $target->withPath('/robots.txt'),
            ],
            'sitemap' => [
                'available' => $sitemapValid,
                'status' => $sitemap['status'],
                'url' => $target->withPath('/sitemap.xml'),
            ],
            'x_robots_tag' => $primary->header('x-robots-tag'),
        ];

        return new SectionResult('seo', $findings, $details, $isHtml ? null : 'not_html');
    }

    /** @return list<Finding> */
    private function pageFindings(SeoAnalysis $analysis, HttpFetchResult $primary): array
    {
        $findings = [];

        $title = $analysis->title;
        $titleLength = $title === null ? 0 : mb_strlen($title);
        if ($title === null) {
            $findings[] = Finding::fail('seo_title', 'seo', 3);
        } elseif ($titleLength >= 30 && $titleLength <= 60) {
            $findings[] = Finding::pass('seo_title', 'seo', 3, ['length' => $titleLength]);
        } elseif ($titleLength < 10 || $titleLength > 70) {
            $findings[] = Finding::fail('seo_title', 'seo', 3, ['length' => $titleLength]);
        } else {
            $findings[] = Finding::warn('seo_title', 'seo', 3, ['length' => $titleLength]);
        }

        $description = $analysis->description;
        $descriptionLength = $description === null ? 0 : mb_strlen($description);
        if ($description === null) {
            $findings[] = Finding::fail('seo_description', 'seo', 2);
        } elseif ($descriptionLength >= 50 && $descriptionLength <= 160) {
            $findings[] = Finding::pass('seo_description', 'seo', 2, ['length' => $descriptionLength]);
        } elseif ($descriptionLength < 20 || $descriptionLength > 200) {
            $findings[] = Finding::fail('seo_description', 'seo', 2, ['length' => $descriptionLength]);
        } else {
            $findings[] = Finding::warn('seo_description', 'seo', 2, ['length' => $descriptionLength]);
        }

        $findings[] = $analysis->canonical !== null
            ? Finding::pass('seo_canonical', 'seo', 1)
            : Finding::warn('seo_canonical', 'seo', 1);

        $robotsMeta = strtolower((string) $analysis->robotsMeta);
        $xRobots = strtolower((string) $primary->header('x-robots-tag'));
        $noindex = str_contains($robotsMeta, 'noindex') || str_contains($xRobots, 'noindex');
        $findings[] = $noindex
            ? Finding::warn('seo_robots_meta', 'seo', 1)
            : Finding::pass('seo_robots_meta', 'seo', 1);

        if ($analysis->h1Count === 1) {
            $findings[] = Finding::pass('seo_h1', 'seo', 2, ['count' => 1]);
        } elseif ($analysis->h1Count === 0) {
            $findings[] = Finding::fail('seo_h1', 'seo', 2, ['count' => 0]);
        } else {
            $findings[] = Finding::warn('seo_h1', 'seo', 2, ['count' => $analysis->h1Count]);
        }

        $findings[] = $analysis->lang !== null
            ? Finding::pass('seo_lang', 'seo', 1, ['lang' => $analysis->lang])
            : Finding::warn('seo_lang', 'seo', 1);

        $findings[] = $analysis->viewport !== null
            ? Finding::pass('seo_viewport', 'seo', 2)
            : Finding::fail('seo_viewport', 'seo', 2);

        $ogComplete = isset($analysis->openGraph['og:title'], $analysis->openGraph['og:description'], $analysis->openGraph['og:image'])
            && $analysis->openGraph['og:title'] !== ''
            && $analysis->openGraph['og:description'] !== ''
            && $analysis->openGraph['og:image'] !== '';

        $findings[] = $ogComplete
            ? Finding::pass('seo_og', 'seo', 1, ['count' => count($analysis->openGraph)])
            : Finding::warn('seo_og', 'seo', 1, ['count' => count($analysis->openGraph)]);

        if ($analysis->imagesTotal > 0) {
            $findings[] = $analysis->imagesMissingAlt === 0
                ? Finding::pass('seo_images_alt', 'seo', 1, ['total' => $analysis->imagesTotal])
                : Finding::warn('seo_images_alt', 'seo', 1, [
                    'missing' => $analysis->imagesMissingAlt,
                    'total' => $analysis->imagesTotal,
                ]);
        }

        return $findings;
    }

    /** @return array{status: ?int, body: string} */
    private function probe(string $url): array
    {
        try {
            $result = $this->fetcher->fetch($url);

            return ['status' => $result->status(), 'body' => substr($result->body(), 0, 100_000)];
        } catch (FetchFailedException|UnsafeUrlException|Throwable) {
            return ['status' => null, 'body' => ''];
        }
    }

    /** @return array<string, mixed> */
    private function analysisToArray(SeoAnalysis $analysis): array
    {
        return [
            'parsed' => $analysis->parsed,
            'title' => $analysis->title,
            'description' => $analysis->description,
            'canonical' => $analysis->canonical,
            'robots_meta' => $analysis->robotsMeta,
            'h1_count' => $analysis->h1Count,
            'h1_texts' => $analysis->h1Texts,
            'lang' => $analysis->lang,
            'viewport' => $analysis->viewport,
            'charset' => $analysis->charset,
            'open_graph' => $analysis->openGraph,
            'images_total' => $analysis->imagesTotal,
            'images_missing_alt' => $analysis->imagesMissingAlt,
            'images_empty_alt' => $analysis->imagesEmptyAlt,
            'links_total' => $analysis->linksTotal,
        ];
    }
}
