<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Scan;
use App\Services\DemoData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Copyright (c) 2026 Fedaa Mohamed. All rights reserved.
 */
final class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_reset_seeds_sample_history_when_enabled(): void
    {
        config(['sitecheck.demo_mode' => true]);

        Scan::factory()->count(3)->create();

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertSame(0, Scan::query()->where('owner_token', '!=', DemoData::OWNER_TOKEN)->count());
        $this->assertGreaterThanOrEqual(20, Scan::query()->where('owner_token', DemoData::OWNER_TOKEN)->count());
    }

    public function test_demo_reset_clears_everything_when_disabled(): void
    {
        config(['sitecheck.demo_mode' => false]);

        Scan::factory()->count(3)->create();

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertSame(0, Scan::query()->count());
    }

    public function test_demo_note_is_shown_when_demo_mode_is_enabled(): void
    {
        config(['sitecheck.demo_mode' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee(__('sitecheck.footer.demo_note'));
    }

    public function test_demo_note_is_hidden_when_demo_mode_is_disabled(): void
    {
        config(['sitecheck.demo_mode' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee(__('sitecheck.footer.demo_note'));
    }

    public function test_pages_contain_no_inline_event_handlers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('onsubmit=', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('javascript:', false);
    }
}
