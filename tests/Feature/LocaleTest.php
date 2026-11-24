<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_is_the_default_locale_and_direction(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee(__('sitecheck.home.title'));
        $response->assertSee('dir="rtl"', false);
    }

    public function test_switching_to_english_updates_the_session_and_direction(): void
    {
        $this->from('/')->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'en');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="en" dir="ltr">', false);
        $response->assertSee(__('sitecheck.home.title'));
    }

    public function test_switching_back_to_arabic_works(): void
    {
        $this->withSession(['locale' => 'en'])->get('/')->assertSee('<html lang="en"', false);

        $this->withSession(['locale' => 'en'])
            ->from('/')
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertSessionHas('locale', 'ar');

        $this->get('/')->assertSee('<html lang="ar"', false);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->from('/')->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->get('/')->assertSee('<html lang="ar"', false);
    }

    public function test_language_switch_form_is_rendered(): void
    {
        $this->get('/')
            ->assertSee(route('locale.update'), false)
            ->assertSee('العربية')
            ->assertSee('English');
    }

    public function test_security_headers_are_sent(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_translations_are_complete_in_both_languages(): void
    {
        $english = require base_path('lang/en/sitecheck.php');
        $arabic = require base_path('lang/ar/sitecheck.php');

        $this->assertSame([], $this->missingKeys($english, $arabic, 'en'), 'Arabic is missing keys present in English');
        $this->assertSame([], $this->missingKeys($arabic, $english, 'ar'), 'English is missing keys present in Arabic');
    }

    /** @return list<string> */
    private function missingKeys(array $reference, array $candidate, string $path): array
    {
        $missing = [];

        foreach ($reference as $key => $value) {
            $currentPath = $path.'.'.$key;

            if (! array_key_exists($key, $candidate)) {
                $missing[] = $currentPath;

                continue;
            }

            if (is_array($value) && is_array($candidate[$key])) {
                $missing = array_merge($missing, $this->missingKeys($value, $candidate[$key], $currentPath));
            }
        }

        return $missing;
    }
}
