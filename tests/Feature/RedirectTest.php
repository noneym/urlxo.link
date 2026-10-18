<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Link;
use App\Models\User;
use App\Services\LinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1';

    private function link(array $attrs = []): Link
    {
        return app(LinkService::class)->create(['url' => 'https://example.com/landing'] + $attrs, User::factory()->create());
    }

    public function test_redirects_and_records_a_click(): void
    {
        $link = $this->link(['code' => 'hello']);

        $this->withHeaders(['User-Agent' => self::PHONE, 'CF-IPCountry' => 'nl', 'Referer' => 'https://www.t.co/abc'])
            ->get('/hello')
            ->assertRedirect('https://example.com/landing')
            ->assertStatus(302)
            ->assertHeader('X-Robots-Tag', 'noindex');

        $click = Click::sole();
        $this->assertSame('NL', $click->country);
        $this->assertSame('t.co', $click->referrer_host);
        $this->assertSame('mobile', $click->device);
        $this->assertSame('Safari', $click->browser);
        $this->assertSame('iOS', $click->os);
        $this->assertSame(1, $link->fresh()->clicks_count);
    }

    public function test_bots_are_redirected_but_not_counted(): void
    {
        $this->link(['code' => 'botty']);

        $this->withHeaders(['User-Agent' => 'facebookexternalhit/1.1'])->get('/botty')->assertRedirect('https://example.com/landing');

        $this->assertSame(0, Click::count());
    }

    public function test_codes_are_case_sensitive(): void
    {
        $this->link(['code' => 'AbC']);

        $this->get('/abc')->assertNotFound();
        $this->get('/AbC')->assertRedirect();
    }

    public function test_missing_paused_expired_and_capped_links(): void
    {
        $this->get('/nothing-here')->assertNotFound();

        $paused = $this->link(['code' => 'paused', 'is_active' => false]);
        $this->get('/paused')->assertStatus(410)->assertSee('turned off');

        $expired = $this->link(['code' => 'old']);
        $expired->forceFill(['expires_at' => now()->subMinute()])->save();
        app(LinkService::class)->forget('old');
        $this->get('/old')->assertStatus(410)->assertSee('expired');

        $capped = $this->link(['code' => 'capped', 'max_clicks' => 1]);
        $this->withHeaders(['User-Agent' => self::PHONE])->get('/capped')->assertRedirect();
        $this->withHeaders(['User-Agent' => self::PHONE])->get('/capped')->assertStatus(410)->assertSee('click limit');
    }

    public function test_password_protected_link(): void
    {
        $this->link(['code' => 'secret', 'password' => 'hunter22']);

        $this->get('/secret')->assertOk()->assertSee('password protected');
        $this->post('/secret', ['password' => 'nope'])->assertStatus(422);
        $this->post('/secret', ['password' => 'hunter22'])->assertRedirect('https://example.com/landing');
        // Unlocked for the rest of the session.
        $this->get('/secret')->assertRedirect('https://example.com/landing');
    }

    public function test_edits_invalidate_the_cache(): void
    {
        $link = $this->link(['code' => 'moving']);
        $this->get('/moving')->assertRedirect('https://example.com/landing');

        app(LinkService::class)->update($link, ['url' => 'https://example.org/new']);

        $this->get('/moving')->assertRedirect('https://example.org/new');
    }

    public function test_qr_code_is_svg(): void
    {
        $this->link(['code' => 'qrme']);

        $this->get('/qr/qrme.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->get('/qr/none.svg')->assertNotFound();
    }
}
