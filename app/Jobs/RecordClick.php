<?php

namespace App\Jobs;

use App\Models\Click;
use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RecordClick implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array{country:?string, referrer_host:?string, device:?string, browser:?string, os:?string, visitor_hash:?string} $data */
    public function __construct(public int $linkId, public string $clickedAt, public array $data)
    {
        $this->onQueue('clicks');
    }

    public function handle(): void
    {
        if (! Link::query()->whereKey($this->linkId)->exists()) {
            return;
        }

        Click::create(['link_id' => $this->linkId, 'clicked_at' => $this->clickedAt] + $this->data);

        Link::query()->whereKey($this->linkId)->update([
            'clicks_count' => DB::raw('clicks_count + 1'),
            'last_clicked_at' => $this->clickedAt,
        ]);
    }
}
