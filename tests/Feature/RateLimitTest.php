<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Scan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\NetworkFakes;
use Tests\TestCase;

final class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NetworkFakes::make()->healthyExampleSite()->bind($this->app);
    }

    public function test_scans_are_limited_per_ip_address(): void
    {
        config([
            'sitecheck.rate_limits.per_ip_per_minute' => 2,
            'sitecheck.rate_limits.per_session_per_minute' => 20,
        ]);

        $this->post('/scan', ['target' => 'example.com'])->assertRedirect();
        $this->post('/scan', ['target' => 'example.com'])->assertRedirect();

        $response = $this->post('/scan', ['target' => 'example.com']);

        $response->assertStatus(429);
        $response->assertSee(__('sitecheck.errors.429_title'));
        $this->assertSame(2, Scan::query()->count());
    }

    public function test_the_named_limiter_is_registered(): void
    {
        $limiter = RateLimiter::limiter('scan');

        $this->assertNotNull($limiter);
        $this->assertContains('throttle:scan', app('router')->getRoutes()->getByName('scan.store')->gatherMiddleware());
        $this->assertContains('throttle:scan', app('router')->getRoutes()->getByName('scans.rescan')->gatherMiddleware());
    }
}
