<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(?User $user = null): array
    {
        $user ??= User::factory()->create();

        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken, 'Accept' => 'application/json'];
    }

    public function test_requires_a_key(): void
    {
        $this->getJson('/api/v1/links')->assertUnauthorized();
    }

    public function test_full_crud_and_stats(): void
    {
        $h = $this->token();

        $this->postJson('/api/v1/links', ['url' => 'example.com/a', 'code' => 'launch', 'password' => 'abcd'], $h)
            ->assertCreated()
            ->assertJsonPath('data.code', 'launch')
            ->assertJsonPath('data.url', 'https://example.com/a')
            ->assertJsonPath('data.has_password', true)
            ->assertJsonPath('data.clicks', 0)
            ->assertJsonPath('data.short_url', 'https://'.config('urlxo.short_domain').'/launch')
            ->assertJsonMissingPath('data.password');

        $this->postJson('/api/v1/links', ['url' => 'https://example.com/b', 'code' => 'launch'], $h)
            ->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->getJson('/api/v1/links?q=launch', $h)->assertOk()->assertJsonPath('meta.total', 1);

        $this->patchJson('/api/v1/links/launch', ['url' => 'https://example.com/c', 'password' => null], $h)
            ->assertOk()
            ->assertJsonPath('data.url', 'https://example.com/c')
            ->assertJsonPath('data.has_password', false);

        $this->getJson('/api/v1/links/launch/stats?days=7', $h)
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonCount(7, 'data.daily');

        $this->deleteJson('/api/v1/links/launch', [], $h)->assertNoContent();
        $this->getJson('/api/v1/links/launch', $h)->assertNotFound();
    }

    public function test_cannot_touch_other_users_links(): void
    {
        $owner = User::factory()->create();
        Link::create(['user_id' => $owner->id, 'code' => 'mine', 'url' => 'https://example.com']);

        $h = $this->token();
        $this->getJson('/api/v1/links/mine', $h)->assertNotFound();
        $this->deleteJson('/api/v1/links/mine', [], $h)->assertNotFound();
    }

    public function test_rejects_bad_urls_and_reserved_codes(): void
    {
        $h = $this->token();

        $this->postJson('/api/v1/links', ['url' => 'javascript:alert(1)'], $h)->assertJsonValidationErrors('url');
        $this->postJson('/api/v1/links', ['url' => 'https://'.config('urlxo.short_domain').'/x'], $h)->assertJsonValidationErrors('url');
        $this->postJson('/api/v1/links', ['url' => 'https://localhost'], $h)->assertJsonValidationErrors('url');
        $this->postJson('/api/v1/links', ['url' => 'https://example.com', 'code' => 'login'], $h)->assertJsonValidationErrors('code');
        $this->postJson('/api/v1/links', ['url' => 'https://example.com', 'code' => 'a b'], $h)->assertJsonValidationErrors('code');
    }
}
