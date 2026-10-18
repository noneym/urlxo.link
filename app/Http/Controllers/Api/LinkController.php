<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\LinkService;
use App\Services\LinkStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LinkController extends Controller
{
    public function __construct(private readonly LinkService $links) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->user()->links()->latest('id');
        if ($q = $request->string('q')->toString()) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('code', 'like', $term)->orWhere('url', 'like', $term)->orWhere('title', 'like', $term));
        }

        $page = $query->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'data' => collect($page->items())->map->toApi(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(LinkService::rules());
        $link = $this->links->create($data, $request->user(), $request->ip());

        return response()->json(['data' => $link->toApi()], 201);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        return response()->json(['data' => $this->find($request, $code)->toApi()]);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $link = $this->find($request, $code);
        $data = $request->validate(array_merge(LinkService::rules(true), ['expires_at' => ['nullable', 'date']]));
        $this->links->update($link, $data);

        return response()->json(['data' => $link->fresh()->toApi()]);
    }

    public function destroy(Request $request, string $code): Response
    {
        $this->links->delete($this->find($request, $code));

        return response()->noContent();
    }

    public function stats(Request $request, string $code, LinkStats $stats): JsonResponse
    {
        $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:365']]);
        $link = $this->find($request, $code);

        return response()->json(['data' => ['code' => $link->code, 'all_time_clicks' => $link->clicks_count] + $stats->for($link, (int) $request->input('days', 30))]);
    }

    private function find(Request $request, string $code): Link
    {
        return $request->user()->links()->where('code', $code)->firstOrFail();
    }
}
