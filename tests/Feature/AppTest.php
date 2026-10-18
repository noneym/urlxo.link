<?php

namespace Tests\Feature;

use App\Livewire\Account\Settings;
use App\Livewire\Links\Index;
use App\Livewire\Links\Show;
use App\Livewire\Shortener;
use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        foreach (['/', '/docs', '/legal/terms', '/legal/privacy', '/login', '/register'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_guest_can_shorten_from_the_landing_page(): void
    {
        Livewire::test(Shortener::class)
            ->set('url', 'example.com/page')
            ->call('shorten')
            ->assertHasNoErrors()
            ->assertSet('destination', 'https://example.com/page');

        $this->assertNull(Link::sole()->user_id);
    }

    public function test_honeypot_blocks_bots(): void
    {
        Livewire::test(Shortener::class)->set('url', 'example.com')->set('website', 'spam')->call('shorten');

        $this->assertSame(0, Link::count());
    }

    public function test_dashboard_create_toggle_delete(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('url', 'https://example.com/x')
            ->set('code', 'my-alias')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSee('my-alias');

        $link = $user->links()->sole();

        Livewire::test(Index::class)->call('toggle', $link->id);
        $this->assertFalse($link->fresh()->is_active);

        Livewire::test(Index::class)->call('delete', $link->id);
        $this->assertSame(0, Link::count());
    }

    public function test_link_page_is_private_and_editable(): void
    {
        $user = User::factory()->create();
        $link = Link::create(['user_id' => $user->id, 'code' => 'edit-me', 'url' => 'https://example.com']);

        $this->actingAs(User::factory()->create())->get(route('links.show', $link))->assertNotFound();

        $this->actingAs($user);
        Livewire::test(Show::class, ['link' => $link])
            ->set('url', 'https://example.org')
            ->set('code', 'edited')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('edited', $link->fresh()->code);
        $this->assertSame('https://example.org', $link->fresh()->url);
    }

    public function test_api_keys_can_be_created_and_revoked(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(Settings::class)->set('tokenName', 'CI')->call('createToken');
        $this->assertNotNull($component->get('plainToken'));
        $this->assertSame(1, $user->tokens()->count());

        $component->call('revokeToken', $user->tokens()->first()->id);
        $this->assertSame(0, $user->tokens()->count());
    }
}
