<?php

namespace Tests\Feature;

use App\Models\AuthSite;
use App\Support\AuthHub\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthHubTest extends TestCase
{
    use RefreshDatabase;

    private const HUB = 'https://auth.urlxo.link';

    private const NONCE = 'n0nce-from-the-site-session';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'authhub.domain' => 'auth.urlxo.link',
            'authhub.google.client_id' => 'client-123.apps.googleusercontent.com',
            'authhub.google.client_secret' => 'shh',
            'authhub.google.redirect' => self::HUB.'/callback',
            'authhub.admin_token' => str_repeat('a', 32),
        ]);
    }

    private function site(array $attrs = []): AuthSite
    {
        return AuthSite::create($attrs + [
            'slug' => 'demo',
            'name' => 'Demo',
            'consume_url' => 'https://demo.example/auth/hub',
            'secret' => 'site-secret',
        ]);
    }

    /** Runs /start and returns [state, browser cookie]. */
    private function begin(string $return = '/profile'): array
    {
        $response = $this->get(self::HUB.'/start?'.http_build_query(['site' => 'demo', 'nonce' => self::NONCE, 'return' => $return]));
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(self::HUB.'/callback', $query['redirect_uri']);
        $this->assertSame('openid email profile', $query['scope']);

        return [$query['state'], $response->getCookie('hub_b')->getValue()];
    }

    private function fakeGoogle(array $claims = []): void
    {
        $idToken = Jwt::encode($claims + [
            'iss' => 'https://accounts.google.com',
            'aud' => 'client-123.apps.googleusercontent.com',
            'sub' => '1234567890',
            'email' => 'ayse@example.com',
            'email_verified' => true,
            'name' => 'Ayse Y',
            'picture' => 'https://lh3.googleusercontent.com/a/x',
            'exp' => time() + 3600,
        ], 'google-signs-this');

        Http::fake(['oauth2.googleapis.com/token' => Http::response(['id_token' => $idToken, 'access_token' => 'x'])]);
    }

    public function test_full_sign_in_hands_a_signed_token_to_the_site(): void
    {
        $this->site();
        [$state, $browser] = $this->begin();
        $this->fakeGoogle();

        $response = $this->withCookie('hub_b', $browser)->get(self::HUB.'/callback?'.http_build_query(['state' => $state, 'code' => 'abc']));

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://demo.example/auth/hub?token=', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $claims = Jwt::decode($query['token'], 'site-secret');
        $this->assertNotNull($claims);
        $this->assertSame('demo', $claims['aud']);
        $this->assertSame('1234567890', $claims['sub']);
        $this->assertSame('ayse@example.com', $claims['email']);
        $this->assertTrue($claims['email_verified']);
        $this->assertSame(self::NONCE, $claims['nonce']);
        $this->assertSame('/profile', $claims['return']);
        $this->assertGreaterThan(time(), $claims['exp']);
        $this->assertNull(Jwt::decode($query['token'], 'another-secret'));
        $this->assertSame(1, AuthSite::first()->logins_count);

        Http::assertSent(fn ($request) => $request['redirect_uri'] === self::HUB.'/callback' && $request['code'] === 'abc');
    }

    public function test_callback_rejects_a_different_browser(): void
    {
        $this->site();
        [$state] = $this->begin();
        $this->fakeGoogle();

        $this->withCookie('hub_b', 'someone-elses-cookie')
            ->get(self::HUB.'/callback?'.http_build_query(['state' => $state, 'code' => 'abc']))
            ->assertStatus(400);
    }

    public function test_callback_rejects_a_tampered_state(): void
    {
        $this->site();
        [$state, $browser] = $this->begin();
        [$payload, $sig] = explode('.', $state);
        $data = json_decode(Jwt::unb64($payload), true);
        $data['s'] = 'other';
        $forged = Jwt::b64(json_encode($data)).'.'.$sig;

        $this->withCookie('hub_b', $browser)
            ->get(self::HUB.'/callback?'.http_build_query(['state' => $forged, 'code' => 'abc']))
            ->assertStatus(400);
    }

    public function test_id_token_for_another_client_is_rejected(): void
    {
        $this->site();
        [$state, $browser] = $this->begin();
        $this->fakeGoogle(['aud' => 'someone-else.apps.googleusercontent.com']);

        $this->withCookie('hub_b', $browser)
            ->get(self::HUB.'/callback?'.http_build_query(['state' => $state, 'code' => 'abc']))
            ->assertStatus(502);
    }

    public function test_denied_consent_goes_back_to_the_site_with_an_error(): void
    {
        $this->site();
        [$state, $browser] = $this->begin();

        $response = $this->withCookie('hub_b', $browser)->get(self::HUB.'/callback?'.http_build_query(['state' => $state, 'error' => 'access_denied']));

        $this->assertSame('https://demo.example/auth/hub?error=access_denied&return=%2Fprofile', $response->headers->get('Location'));
    }

    public function test_start_validates_site_nonce_and_return_path(): void
    {
        $this->get(self::HUB.'/start?site=nope&nonce='.self::NONCE)->assertNotFound();

        $this->site();
        $this->get(self::HUB.'/start?site=demo&nonce=short')->assertStatus(400);

        AuthSite::first()->update(['active' => false]);
        $this->get(self::HUB.'/start?site=demo&nonce='.self::NONCE)->assertNotFound();
        AuthSite::first()->update(['active' => true]);

        // Off-site return paths are replaced with "/".
        [$state] = $this->begin('//evil.example/x');
        $this->assertSame('/', json_decode(Jwt::unb64(explode('.', $state)[0]), true)['r']);
    }

    public function test_hub_host_does_not_serve_short_links(): void
    {
        $this->get(self::HUB.'/')->assertOk()->assertSee('sign-in service');
        $this->get(self::HUB.'/abc123')->assertNotFound();
        $this->get(self::HUB.'/login')->assertNotFound();
    }

    public function test_registry_api(): void
    {
        $auth = ['Authorization' => 'Bearer '.str_repeat('a', 32)];

        $this->getJson(self::HUB.'/api/hub/sites')->assertUnauthorized();

        $created = $this->postJson(self::HUB.'/api/hub/sites', ['slug' => 'demo', 'name' => 'Demo', 'consume_url' => 'https://demo.example/auth/hub'], $auth)
            ->assertCreated()->json('data');
        $this->assertNotEmpty($created['secret']);
        $this->assertSame($created['secret'], AuthSite::first()->secret);

        $this->postJson(self::HUB.'/api/hub/sites', ['slug' => 'insecure', 'name' => 'X', 'consume_url' => 'http://insecure.example/cb'], $auth)
            ->assertUnprocessable();

        $this->getJson(self::HUB.'/api/hub/sites', $auth)->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.secret');

        $this->patchJson(self::HUB.'/api/hub/sites/demo', ['consume_url' => 'https://new-domain.example/auth/hub'], $auth)->assertOk();
        $this->assertSame('https://new-domain.example/auth/hub', AuthSite::first()->consume_url);

        $rotated = $this->postJson(self::HUB.'/api/hub/sites/demo/rotate', [], $auth)->assertOk()->json('data.secret');
        $this->assertNotSame($created['secret'], $rotated);

        $this->deleteJson(self::HUB.'/api/hub/sites/demo', [], $auth)->assertNoContent();
        $this->assertSame(0, AuthSite::count());
    }

    public function test_registry_api_is_only_on_the_hub_host(): void
    {
        $this->getJson('https://urlxo.link/api/hub/sites', ['Authorization' => 'Bearer '.str_repeat('a', 32)])->assertNotFound();
    }

    public function test_artisan_command_adds_and_lists_sites(): void
    {
        $this->artisan('authhub:site', ['action' => 'add', 'slug' => 'demo', '--url' => 'https://demo.example/auth/hub'])
            ->expectsOutputToContain('AUTH_HUB_SECRET=')
            ->assertSuccessful();
        $this->artisan('authhub:site', ['action' => 'add', 'slug' => 'bad', '--url' => 'ftp://x'])->assertFailed();
        $this->artisan('authhub:site', ['action' => 'disable', 'slug' => 'demo'])->assertSuccessful();
        $this->assertFalse(AuthSite::first()->active);
    }
}
