<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Scan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\NetworkFakes;
use Tests\TestCase;

final class HistoryTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private function asOwner(): static
    {
        return $this->withSession(['sitecheck.owner_token' => $this->token]);
    }

    public function test_history_lists_only_the_current_session_scans(): void
    {
        Scan::factory()->create([
            'owner_token' => $this->token,
            'host' => 'alpha.example',
            'input' => 'alpha.example',
        ]);
        Scan::factory()->create([
            'owner_token' => str_repeat('b', 32),
            'host' => 'private.example',
            'input' => 'private.example',
        ]);

        $this->asOwner()
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('alpha.example')
            ->assertDontSee('private.example');
    }

    public function test_history_search_filters_by_host_and_url(): void
    {
        Scan::factory()->create(['owner_token' => $this->token, 'host' => 'alpha.example', 'input' => 'alpha.example']);
        Scan::factory()->create(['owner_token' => $this->token, 'host' => 'beta.example', 'input' => 'https://beta.example/path']);

        $this->asOwner()->get(route('history.index', ['q' => 'beta']))
            ->assertOk()
            ->assertSee('beta.example')
            ->assertDontSee('alpha.example');

        $this->asOwner()->get(route('history.index', ['q' => 'nothing-matches']))
            ->assertOk()
            ->assertSee(__('sitecheck.history.empty_search_title'));
    }

    public function test_history_search_with_a_wildcard_still_returns_rows_safely(): void
    {
        Scan::factory()->create(['owner_token' => $this->token, 'host' => 'alpha.example', 'input' => 'alpha.example']);

        $this->asOwner()->get(route('history.index', ['q' => '%']))
            ->assertOk()
            ->assertSee('alpha.example');
    }

    public function test_history_is_empty_for_a_new_session(): void
    {
        $this->asOwner()->get(route('history.index'))
            ->assertOk()
            ->assertSee(__('sitecheck.history.empty_title'));
    }

    public function test_another_session_cannot_view_print_export_or_delete_a_scan(): void
    {
        $scan = Scan::factory()->create(['owner_token' => $this->token, 'host' => 'alpha.example']);

        $attacker = $this->withSession(['sitecheck.owner_token' => str_repeat('b', 32)]);

        $attacker->get(route('scans.show', $scan))->assertNotFound();
        $attacker->get(route('scans.print', $scan))->assertNotFound();
        $attacker->get(route('scans.export', $scan))->assertNotFound();
        $attacker->delete(route('scans.destroy', $scan))->assertNotFound();

        $this->assertDatabaseHas('scans', ['id' => $scan->id]);
    }

    public function test_owner_can_delete_a_scan(): void
    {
        $scan = Scan::factory()->create(['owner_token' => $this->token, 'host' => 'alpha.example']);

        $this->asOwner()->delete(route('scans.destroy', $scan))
            ->assertRedirect(route('history.index'));

        $this->assertDatabaseMissing('scans', ['id' => $scan->id]);
    }

    public function test_owner_can_view_the_report_and_download_json(): void
    {
        $scan = Scan::factory()->forHost('alpha.example')->create(['owner_token' => $this->token]);

        $this->asOwner()->get(route('scans.show', $scan))
            ->assertOk()
            ->assertSee('alpha.example');

        $response = $this->asOwner()->get(route('scans.export', $scan));
        $response->assertOk();
        $this->assertSame('alpha.example', $response->json('target.host'));
    }

    public function test_rescan_creates_a_new_history_entry(): void
    {
        $fakes = NetworkFakes::make()->healthyExampleSite()->bind($this->app);

        $scan = Scan::factory()->create([
            'owner_token' => $this->token,
            'host' => 'example.com',
            'input' => 'example.com',
        ]);

        $response = $this->asOwner()->post(route('scans.rescan', $scan));

        $this->assertSame(2, Scan::query()->where('owner_token', $this->token)->count());
        $fresh = Scan::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('scans.show', $fresh));
        $this->assertSame(100, $fresh->overall_score);
        $this->assertNotEmpty($fakes->http->requests);
    }

    public function test_pagination_works(): void
    {
        Scan::factory()->count(10)->create(['owner_token' => $this->token]);

        $first = $this->asOwner()->get(route('history.index'));
        $first->assertOk();
        $this->assertCount(8, $first->viewData('scans')->items());

        $second = $this->asOwner()->get(route('history.index', ['page' => 2]));
        $second->assertOk();
        $this->assertCount(2, $second->viewData('scans')->items());
    }
}
