<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Scan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\NetworkFakes;
use Tests\TestCase;

final class ScanValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NetworkFakes::make()->healthyExampleSite()->bind($this->app);
    }

    public function test_empty_target_is_rejected(): void
    {
        $this->post('/scan', ['target' => ''])
            ->assertSessionHasErrors('target');

        $this->assertSame(0, Scan::query()->count());
    }

    public function test_unsupported_scheme_is_rejected(): void
    {
        $this->post('/scan', ['target' => 'ftp://example.com/'])
            ->assertSessionHasErrors('target');
    }

    public function test_credentials_are_rejected(): void
    {
        $this->post('/scan', ['target' => 'https://user:pass@example.com/'])
            ->assertSessionHasErrors('target');
    }

    public function test_internal_hostnames_are_rejected_before_scanning(): void
    {
        foreach (['http://localhost/', 'http://127.0.0.1/', 'http://intranet/', 'http://metadata.google.internal/'] as $target) {
            $this->post('/scan', ['target' => $target])->assertSessionHasErrors('target');
        }

        $this->assertSame(0, Scan::query()->count());
    }

    public function test_too_long_target_is_rejected(): void
    {
        $this->post('/scan', ['target' => 'https://example.com/'.str_repeat('a', 2100)])
            ->assertSessionHasErrors('target');
    }

    public function test_invalid_dkim_selector_is_rejected(): void
    {
        $this->post('/scan', [
            'target' => 'example.com',
            'dkim_selectors' => 'google, bad selector!',
        ])->assertSessionHasErrors('dkim_selectors');

        $this->assertSame(0, Scan::query()->count());
    }

    public function test_too_many_dkim_selectors_are_capped(): void
    {
        $this->post('/scan', [
            'target' => 'example.com',
            'dkim_selectors' => 'a,b,c,d,e,f,g',
        ]);

        $scan = Scan::query()->firstOrFail();
        $this->assertCount(5, $scan->report['meta']['dkim_selectors']);
    }

    public function test_form_contains_csrf_token(): void
    {
        $this->get('/')->assertSee('_token', false);
    }

    public function test_valid_target_is_accepted(): void
    {
        $this->post('/scan', ['target' => 'example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Scan::query()->count());
    }
}
