<?php

namespace App\Livewire\Links;

use App\Models\Link;
use App\Services\LinkService;
use App\Services\LinkStats;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Link $link;

    #[Url(except: 30)]
    public int $days = 30;

    public bool $editing = false;

    public string $url = '';

    public string $code = '';

    public string $title = '';

    public string $password = '';

    public bool $removePassword = false;

    public string $expires_at = '';

    public string $max_clicks = '';

    public function mount(Link $link): void
    {
        abort_unless($link->user_id === auth()->id(), 404);
        $this->link = $link;
        $this->days = in_array($this->days, [7, 30, 90, 365], true) ? $this->days : 30;
        $this->fillForm();
    }

    private function fillForm(): void
    {
        $this->url = $this->link->url;
        $this->code = $this->link->code;
        $this->title = (string) $this->link->title;
        $this->password = '';
        $this->removePassword = false;
        $this->expires_at = $this->link->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->max_clicks = (string) ($this->link->max_clicks ?? '');
    }

    public function setDays(int $days): void
    {
        $this->days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    }

    public function save(LinkService $links): void
    {
        $rules = LinkService::rules(true);
        $rules['expires_at'] = ['nullable', 'date'];
        $data = $this->validate([
            'url' => ['required', 'string', 'max:2048'],
            'code' => ['required', ...array_slice($rules['code'], 1)],
            'title' => $rules['title'],
            'password' => $rules['password'],
            'expires_at' => $rules['expires_at'],
            'max_clicks' => $rules['max_clicks'],
        ]);

        $payload = [
            'url' => $data['url'],
            'code' => $data['code'],
            'title' => $data['title'] ?: null,
            'expires_at' => $data['expires_at'] ?: null,
            'max_clicks' => $data['max_clicks'] ?: null,
        ];
        if ($this->removePassword) {
            $payload['password'] = null;
        } elseif (filled($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $links->update($this->link, $payload);
        $this->link->refresh();
        $this->editing = false;
        $this->fillForm();
        session()->flash('saved', 'Link updated.');
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetErrorBag();
        $this->fillForm();
    }

    public function toggle(LinkService $links): void
    {
        $links->update($this->link, ['is_active' => ! $this->link->is_active]);
    }

    public function delete(LinkService $links)
    {
        $links->delete($this->link);

        return $this->redirectRoute('links.index', navigate: true);
    }

    public function render(LinkStats $stats)
    {
        return view('livewire.links.show', ['stats' => $stats->for($this->link, $this->days)])
            ->title($this->link->title ?: '/'.$this->link->code);
    }
}
