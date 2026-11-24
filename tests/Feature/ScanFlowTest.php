<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\SiteCheck\Dto\ScanInput;
use App\Domain\SiteCheck\ScanService;
use App\Models\Scan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\NetworkFakes;
use Tests\TestCase;

final class ScanFlowTest extends TestCase
{
    use RefreshDatabase;

    private NetworkFakes $fakes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakes = NetworkFakes::make()->healthyExampleSite()->bind($this->app);
    }

    public function test_full_scan_produces_a_real_report_without_network_access(): void
    {
        $report = $this->app->make(ScanService::class)->run(new ScanInput('example.com', ['google']));

        $this->assertSame('example.com', $report->host);
        $this->assertSame(['dns', 'ssl', 'email', 'http', 'seo'], array_keys($report->sections));
        $this->assertSame(100, $report->overallScore());
        $this->assertSame('A', $report->overallGrade());
        $this->assertSame('93.184.216.34', $report->meta['pinned_ip']);
        $this->assertSame(['google'], $report->meta['dkim_selectors']);

        $this->assertSame(100, $report->section('dns')?->score());
        $this->assertSame(100, $report->section('ssl')?->score());
        $this->assertSame(100, $report->section('email')?->score());
        $this->assertSame(100, $report->section('http')?->score());
        $this->assertSame(100, $report->section('seo')?->score());

        $this->assertSame(
            ['https://example.com/', 'http://example.com/', 'https://example.com/robots.txt', 'https://example.com/sitemap.xml'],
            array_values(array_unique($this->fakes->http->requestedUrls())),
        );
        $this->assertContains('https://example.com/', $this->fakes->http->requestedUrls());

        foreach ($report->sections as $section) {
            $this->assertNull($section->error, $section->key);
        }

        $this->assertSame('TLSv1.3', $report->section('ssl')?->details['tls']['protocol']);
        $this->assertSame('reject', $report->section('email')?->details['dmarc']['policy']);
        $this->assertCount(2, $report->section('email')?->details['mx']);
        $this->assertStringContainsString('Example Domain', $report->section('seo')?->details['page']['title'] ?? '');
    }

    public function test_findings_are_translated_in_both_locales(): void
    {
        $report = $this->app->make(ScanService::class)->run(new ScanInput('example.com', ['google']));

        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);

            foreach ($report->sections as $section) {
                foreach ($section->findings as $finding) {
                    $title = (string) __("sitecheck.findings.{$finding->id}.title", $finding->params);
                    $this->assertNotSame("sitecheck.findings.{$finding->id}.title", $title, "Missing {$locale} title for {$finding->id}");

                    $detail = (string) __("sitecheck.findings.{$finding->id}.detail.{$finding->severity}", $finding->params);
                    if ($detail === "sitecheck.findings.{$finding->id}.detail.{$finding->severity}") {
                        $detail = (string) __("sitecheck.findings.{$finding->id}.detail", $finding->params);
                    }
                    $this->assertNotSame('', $detail);
                    $this->assertNotSame("sitecheck.findings.{$finding->id}.detail", $detail, "Missing {$locale} detail for {$finding->id}");

                    if (in_array($finding->severity, ['warn', 'fail'], true)) {
                        $this->assertNotNull($finding->fix(), "Missing {$locale} fix for {$finding->id}");
                    }
                }
            }
        }
    }

    public function test_a_scan_is_persisted_and_redirects_to_the_report(): void
    {
        $response = $this->post('/scan', ['target' => 'example.com']);

        $scan = Scan::query()->firstOrFail();
        $response->assertRedirect(route('scans.show', $scan));
        $this->assertSame('example.com', $scan->host);
        $this->assertSame(100, $scan->overall_score);
        $this->assertSame('A', $scan->grade);
        $this->assertCount(5, $scan->section_scores);
        $this->assertArrayHasKey('sections', $scan->report);
    }

    public function test_report_page_renders_the_findings(): void
    {
        $this->post('/scan', ['target' => 'example.com']);
        $scan = Scan::query()->firstOrFail();

        $response = $this->get(route('scans.show', $scan));

        $response->assertOk();
        $response->assertSee('example.com');
        $response->assertSee(__('sitecheck.sections.dns.title'));
        $response->assertSee(__('sitecheck.findings.ssl_verified.title'));
        $response->assertSee(__('sitecheck.report.overall'));
    }

    public function test_print_view_is_available(): void
    {
        $this->post('/scan', ['target' => 'example.com']);
        $scan = Scan::query()->firstOrFail();

        $this->get(route('scans.print', $scan))
            ->assertOk()
            ->assertSee(__('sitecheck.report.title'));
    }

    public function test_json_export_contains_localized_findings(): void
    {
        $this->post('/scan', ['target' => 'example.com']);
        $scan = Scan::query()->firstOrFail();

        $response = $this->get(route('scans.export', $scan));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');
        $this->assertStringContainsString('attachment; filename="fedaa-sitecheck-example.com-'.$scan->id.'.json"', (string) $response->headers->get('content-disposition'));

        $payload = $response->json();
        $this->assertSame(100, $payload['overall']['score']);
        $this->assertSame('A', $payload['overall']['grade']);
        $this->assertSame('ar', $payload['locale']);
        $this->assertNotEmpty($payload['sections']['ssl']['findings'][0]['title']);
        $this->assertNotEmpty($payload['sections']['ssl']['findings'][0]['detail']);
    }
}
