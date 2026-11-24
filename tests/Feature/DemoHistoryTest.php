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
final class DemoHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_scans_are_listed_and_read_only_when_enabled(): void
    {
        config(['sitecheck.demo_mode' => true]);

        $this->artisan('demo:reset')->assertSuccessful();

        $this->get(route('history.index'))
            ->assertOk()
            ->assertSee(__('sitecheck.history.sample_notice'));

        $scan = Scan::query()->where('owner_token', DemoData::OWNER_TOKEN)->firstOrFail();

        $this->get(route('scans.show', $scan))->assertOk()->assertSee($scan->host);
        $this->get(route('scans.export', $scan))->assertOk();

        $this->delete(route('scans.destroy', $scan))->assertNotFound();
        $this->post(route('scans.rescan', $scan))->assertNotFound();

        $this->assertDatabaseHas('scans', ['id' => $scan->id]);
    }

    public function test_demo_scans_are_hidden_when_disabled(): void
    {
        config(['sitecheck.demo_mode' => false]);

        $scan = Scan::factory()->create([
            'owner_token' => DemoData::OWNER_TOKEN,
            'host' => 'nour-clinic.example',
            'input' => 'nour-clinic.example',
        ]);

        $this->get(route('history.index'))
            ->assertOk()
            ->assertDontSee('nour-clinic.example');

        $this->get(route('scans.show', $scan))->assertNotFound();
    }
}
