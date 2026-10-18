<?php

namespace App\Livewire\Links;

use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Links')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $url = '';

    public string $code = '';

    public string $title = '';

    public string $password = '';

    public string $expires_at = '';

    public string $max_clicks = '';

    public bool $advanced = false;

    public ?int $justCreated = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(LinkService $links): void
    {
        $data = $this->validate([
            'url' => LinkService::rules()['url'],
            'code' => LinkService::rules()['code'],
            'title' => LinkService::rules()['title'],
            'password' => LinkService::rules()['password'],
            'expires_at' => LinkService::rules()['expires_at'],
            'max_clicks' => LinkService::rules()['max_clicks'],
        ]);

        $link = $links->create(array_map(fn ($v) => $v === '' ? null : $v, $data), auth()->user(), request()->ip());

        $this->reset(['url', 'code', 'title', 'password', 'expires_at', 'max_clicks']);
        $this->justCreated = $link->id;
        $this->resetPage();
    }

    public function toggle(int $id, LinkService $links): void
    {
        $link = $this->owned($id);
        $links->update($link, ['is_active' => ! $link->is_active]);
    }

    public function delete(int $id, LinkService $links): void
    {
        $links->delete($this->owned($id));
    }

    private function owned(int $id): Link
    {
        return auth()->user()->links()->findOrFail($id);
    }

    public function render()
    {
        $user = auth()->user();
        $query = $user->links()->latest('id');

        if ($this->search !== '') {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $this->search).'%';
            $query->where(fn ($q) => $q->where('code', 'like', $term)->orWhere('url', 'like', $term)->orWhere('title', 'like', $term));
        }

        return view('livewire.links.index', [
            'links' => $query->paginate(20),
            'totals' => [
                'links' => $user->links()->count(),
                'clicks' => (int) $user->links()->sum('clicks_count'),
                'today' => DB::table('clicks')->join('links', 'links.id', '=', 'clicks.link_id')->where('links.user_id', $user->id)->where('clicks.clicked_at', '>=', today())->count(),
            ],
        ]);
    }
}
