<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Copyright (c) 2026 Fedaa Mohamed. All rights reserved.
 */
final class PagesTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function pages(): array
    {
        return ['about', 'privacy', 'terms', 'help', 'changelog'];
    }

    public function test_every_static_page_renders_in_arabic_and_english(): void
    {
        foreach ($this->pages() as $page) {
            $this->get(route('pages.'.$page))
                ->assertOk()
                ->assertSee(__('sitecheck.pages.'.$page.'.title'));
        }

        $this->withSession(['locale' => 'en'])
            ->get(route('pages.about'))
            ->assertOk()
            ->assertSee(__('sitecheck.pages.about.title'));
    }

    public function test_pages_expose_meta_description_open_graph_and_canonical(): void
    {
        $response = $this->get(route('pages.privacy'));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('twitter:card', false);
    }

    public function test_footer_uses_product_copyright_and_owner_credit_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('© 2026 SiteCheck.', false)
            ->assertSee('https://fedaamohamed.com', false)
            ->assertSee(__('sitecheck.footer.credit'));
    }
}
