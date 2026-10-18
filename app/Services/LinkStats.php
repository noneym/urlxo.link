<?php

namespace App\Services;

use App\Models\Link;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LinkStats
{
    /**
     * Click breakdown for one link over the last $days days (today included).
     *
     * @return array{from:string, to:string, total:int, unique:int, daily:list<array{date:string, clicks:int}>, countries:list<array{key:string, clicks:int}>, referrers:list<array{key:string, clicks:int}>, devices:list<array{key:string, clicks:int}>, browsers:list<array{key:string, clicks:int}>, os:list<array{key:string, clicks:int}>}
     */
    public function for(Link $link, int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $to = CarbonImmutable::now()->endOfDay();
        $from = $to->subDays($days - 1)->startOfDay();

        $base = fn () => DB::table('clicks')->where('link_id', $link->id)->whereBetween('clicked_at', [$from, $to]);

        $byDay = $base()
            ->selectRaw('DATE(clicked_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $daily = [];
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            $key = $day->toDateString();
            $daily[] = ['date' => $key, 'clicks' => (int) ($byDay[$key] ?? 0)];
        }

        $top = function (string $column, int $limit = 8) use ($base) {
            return $base()
                ->selectRaw("COALESCE($column, '') as k, COUNT(*) as c")
                ->groupBy('k')
                ->orderByDesc('c')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['key' => $r->k === '' ? 'Unknown' : $r->k, 'clicks' => (int) $r->c])
                ->all();
        };

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total' => (int) $base()->count(),
            'unique' => (int) $base()->distinct()->count('visitor_hash'),
            'daily' => $daily,
            'countries' => $top('country'),
            'referrers' => array_map(fn ($r) => $r['key'] === 'Unknown' ? ['key' => 'Direct', 'clicks' => $r['clicks']] : $r, $top('referrer_host')),
            'devices' => $top('device', 4),
            'browsers' => $top('browser', 6),
            'os' => $top('os', 6),
        ];
    }
}
