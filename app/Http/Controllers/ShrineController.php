<?php

namespace App\Http\Controllers;

use App\Models\ButterLamp;
use App\Models\FlowerOffering;
use App\Models\IncenseOffering;
use App\Models\MantraRepetition;
use App\Models\MusicOffering;
use App\Models\MusicSuggestion;
use App\Models\MusicTrack;
use App\Models\PractitionerPresence;
use App\Models\WaterBowlSession;
use App\Support\OfferingGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ShrineController extends Controller
{
    /** @var list<string> */
    private const FLOWER_TYPES = ['lotus', 'tulip', 'sunflower', 'marigold', 'peony', 'rose'];

    /** @var list<string> */
    private const VASE_COLORS = ['blue', 'white', 'yellow', 'red', 'green'];

    /** @var array<string, class-string<\Illuminate\Database\Eloquent\Model>> */
    private const VISITOR_LIMIT_MODELS = [
        'butter_lamps' => ButterLamp::class,
        'incense' => IncenseOffering::class,
        'flowers' => FlowerOffering::class,
        'music' => MusicOffering::class,
        'water' => WaterBowlSession::class,
        'mantra' => MantraRepetition::class,
        'music_suggestions' => MusicSuggestion::class,
    ];

    public function index(): View
    {
        $lamps = $this->activeOfferingQuery(ButterLamp::class)
            ->latest()
            ->limit(200)
            ->get(['id', 'name', 'created_at']);

        $flowers = $this->activeOfferingQuery(FlowerOffering::class)
            ->latest()
            ->limit(100)
            ->get(['id', 'name', 'flower_type', 'vase_color', 'created_at']);

        $dedicationNames = $lamps
            ->pluck('name')
            ->filter(fn (?string $name) => filled($name))
            ->values();

        $offeringNames = $this->offeringNames();

        $totalMantraCount = (int) MantraRepetition::query()->sum('count');

        $shrineState = $this->buildShrineState();

        return view('shrine', compact(
            'lamps',
            'flowers',
            'dedicationNames',
            'offeringNames',
            'totalMantraCount',
            'shrineState',
        ));
    }

    public function state(Request $request): JsonResponse
    {
        $visitorToken = $request->string('visitor_token')->toString();

        return response()->json($this->buildShrineState(
            visitorToken: Str::isUuid($visitorToken) ? $visitorToken : null,
        ));
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
        ]);

        PractitionerPresence::query()->updateOrInsert(
            ['session_token' => $validated['token']],
            ['last_seen_at' => now()],
        );

        $this->pruneStalePractitionerPresences();

        return response()->json([
            'live_practitioners' => $this->livePractitionerCount(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $visitorToken = $validated['visitor_token'];
        $name = OfferingGuard::assertCleanName($validated['name'] ?? null);
        OfferingGuard::assertWithinLimit($visitorToken, ButterLamp::class, 'butter lamps');

        $lamp = ButterLamp::create([
            'name' => $name,
            'visitor_token' => $visitorToken,
            'expires_at' => OfferingGuard::expiresAt(),
        ]);

        return response()->json([
            'lamp' => [
                'id' => $lamp->id,
                'name' => $lamp->name,
                'created_at' => $lamp->created_at?->toIso8601String(),
            ],
            'dedication_names' => $this->dedicationNames(),
            'offering_names' => $this->offeringNames(),
            'shrine_state' => $this->buildShrineState(visitorToken: $visitorToken),
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    public function storeMantra(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'count' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $visitorToken = $validated['visitor_token'];
        OfferingGuard::assertWithinLimit($visitorToken, MantraRepetition::class, 'mantra contributions');

        $offering = MantraRepetition::create([
            'count' => $validated['count'],
            'visitor_token' => $visitorToken,
        ]);

        return response()->json([
            'offering' => [
                'id' => $offering->id,
                'count' => $offering->count,
                'created_at' => $offering->created_at?->toIso8601String(),
            ],
            'total_count' => (int) MantraRepetition::query()->sum('count'),
            'dedication_names' => $this->dedicationNames(),
            'offering_names' => $this->offeringNames(),
            'shrine_state' => $this->buildShrineState(visitorToken: $visitorToken),
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    public function storeIncense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $visitorToken = $validated['visitor_token'];
        $name = OfferingGuard::assertCleanName($validated['name'] ?? null);
        OfferingGuard::assertWithinLimit($visitorToken, IncenseOffering::class, 'incense offerings');

        $offering = IncenseOffering::create([
            'name' => $name,
            'visitor_token' => $visitorToken,
            'expires_at' => OfferingGuard::expiresAt(),
        ]);

        return response()->json([
            'offering' => [
                'id' => $offering->id,
                'name' => $offering->name,
                'expires_at' => $offering->expires_at->toIso8601String(),
            ],
            'shrine_state' => $this->buildShrineState(visitorToken: $visitorToken),
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    public function storeFlower(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $visitorToken = $validated['visitor_token'];
        $name = OfferingGuard::assertCleanName($validated['name'] ?? null);
        OfferingGuard::assertWithinLimit($visitorToken, FlowerOffering::class, 'flower offerings');

        $offering = FlowerOffering::create([
            'name' => $name,
            'visitor_token' => $visitorToken,
            'flower_type' => self::FLOWER_TYPES[array_rand(self::FLOWER_TYPES)],
            'vase_color' => self::VASE_COLORS[array_rand(self::VASE_COLORS)],
            'expires_at' => OfferingGuard::expiresAt(),
        ]);

        return response()->json([
            'offering' => [
                'id' => $offering->id,
                'name' => $offering->name,
                'flower_type' => $offering->flower_type,
                'vase_color' => $offering->vase_color,
                'created_at' => $offering->created_at?->toIso8601String(),
            ],
            'shrine_state' => $this->buildShrineState(visitorToken: $visitorToken),
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    public function storeMusic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'track_id' => ['required', 'integer', 'exists:music_tracks,id'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $visitorToken = $validated['visitor_token'];
        $name = OfferingGuard::assertCleanName($validated['name'] ?? null);
        OfferingGuard::assertWithinLimit($visitorToken, MusicOffering::class, 'music offerings');

        $track = MusicTrack::query()
            ->where('active', true)
            ->findOrFail($validated['track_id']);

        $side = $this->nextMusicSide();

        $offering = MusicOffering::create([
            'music_track_id' => $track->id,
            'name' => $name,
            'visitor_token' => $visitorToken,
            'expires_at' => OfferingGuard::expiresAt(),
        ]);

        $offering->load('track');

        return response()->json([
            'offering' => $this->formatMusicOffering($offering, $side),
            'shrine_state' => $this->buildShrineState(visitorToken: $visitorToken),
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    public function storeMusicSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'url' => ['required', 'string', 'max:500', 'regex:/(?:youtube\.com|youtu\.be)/i'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $visitorToken = $validated['visitor_token'];
        $name = OfferingGuard::assertCleanName($validated['name'] ?? null, 'name');
        OfferingGuard::assertWithinLimit($visitorToken, MusicSuggestion::class, 'music suggestions');

        MusicSuggestion::create([
            'youtube_url' => trim($validated['url']),
            'suggested_by_name' => $name,
            'visitor_token' => $visitorToken,
        ]);

        return response()->json([
            'message' => 'Thank you — your suggestion has been recorded.',
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    public function storeWater(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $visitorToken = $validated['visitor_token'];
        $name = OfferingGuard::assertCleanName($validated['name'] ?? null);
        OfferingGuard::assertWithinLimit($visitorToken, WaterBowlSession::class, 'water offerings');

        $session = WaterBowlSession::create([
            'token' => (string) Str::uuid(),
            'visitor_token' => $visitorToken,
            'name' => $name,
            'filled_positions' => [1, 2, 3, 4, 5, 6, 7],
            'expires_at' => OfferingGuard::expiresAt(),
            'completed_at' => now(),
        ]);

        return response()->json([
            'offering' => [
                'id' => $session->id,
                'name' => $session->name,
                'filled_positions' => $session->filled_positions,
            ],
            'shrine_state' => $this->buildShrineState(visitorToken: $visitorToken),
            'offering_names' => $this->offeringNames(),
            'visitor_limits' => OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS),
        ], 201);
    }

    /**
     * @return list<string>
     */
    private function offeringNames(): array
    {
        $entries = collect()
            ->merge(
                $this->activeOfferingQuery(ButterLamp::class)
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                $this->activeOfferingQuery(FlowerOffering::class)
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                $this->activeOfferingQuery(IncenseOffering::class)
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                $this->activeOfferingQuery(MusicOffering::class)
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                $this->activeOfferingQuery(WaterBowlSession::class)
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->whereNotNull('completed_at')
                    ->get(['name', 'created_at']),
            )
            ->sortBy('created_at')
            ->pluck('name')
            ->values();

        return $entries->all();
    }

    /**
     * @return list<string>
     */
    private function dedicationNames(): array
    {
        return $this->activeOfferingQuery(ButterLamp::class)
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderByDesc('created_at')
            ->limit(200)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildShrineState(?string $visitorToken = null): array
    {
        $this->pruneExpiredOfferings();

        $displayWater = $this->activeOfferingQuery(WaterBowlSession::class)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->first();

        $lamps = $this->activeOfferingQuery(ButterLamp::class)
            ->latest()
            ->limit(200)
            ->get(['id', 'name', 'created_at'])
            ->map(fn (ButterLamp $lamp) => [
                'id' => $lamp->id,
                'name' => $lamp->name,
            ])
            ->values()
            ->all();

        $flowers = $this->activeOfferingQuery(FlowerOffering::class)
            ->latest()
            ->limit(100)
            ->get(['id', 'name', 'flower_type', 'vase_color', 'created_at'])
            ->map(fn (FlowerOffering $flower) => [
                'id' => $flower->id,
                'name' => $flower->name,
                'flower_type' => $flower->flower_type,
                'vase_color' => $flower->vase_color ?? 'blue',
            ])
            ->values()
            ->all();

        $state = [
            'incense' => $this->formatIncenseState(),
            'lamps' => $lamps,
            'flowers' => $flowers,
            'music' => $this->formatMusicState(),
            'mantra_total' => (int) MantraRepetition::query()->sum('count'),
            'dedication_names' => $this->dedicationNames(),
            'offering_names' => $this->offeringNames(),
            'live_practitioners' => $this->livePractitionerCount(),
            'water' => [
                'display_positions' => $displayWater?->filled_positions ?? [],
                'display_name' => $displayWater?->name,
            ],
        ];

        if ($visitorToken !== null) {
            $state['visitor_limits'] = OfferingGuard::limitsFor($visitorToken, self::VISITOR_LIMIT_MODELS);
        }

        return $state;
    }

    private function pruneExpiredOfferings(): void
    {
        ButterLamp::query()->where('expires_at', '<=', now())->delete();
        FlowerOffering::query()->where('expires_at', '<=', now())->delete();
        IncenseOffering::query()->where('expires_at', '<=', now())->delete();
        MusicOffering::query()->where('expires_at', '<=', now())->delete();
        WaterBowlSession::query()->where('expires_at', '<=', now())->delete();
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function activeOfferingQuery(string $modelClass)
    {
        return OfferingGuard::applyActiveScope($modelClass::query(), $modelClass);
    }

    /**
     * @return array{sticks: int, active_offerings: int, expires_at: string|null}
     */
    private function formatIncenseState(): array
    {
        $activeOfferings = $this->activeOfferingQuery(IncenseOffering::class)->count();

        $expiresAt = $this->activeOfferingQuery(IncenseOffering::class)->max('expires_at');

        return [
            'sticks' => 2 + $activeOfferings,
            'active_offerings' => $activeOfferings,
            'expires_at' => $expiresAt
                ? Carbon::parse($expiresAt)->toIso8601String()
                : null,
        ];
    }

    private function livePractitionerCount(): int
    {
        return PractitionerPresence::query()
            ->where('last_seen_at', '>=', now()->subMinutes(30))
            ->count();
    }

    private function pruneStalePractitionerPresences(): void
    {
        PractitionerPresence::query()
            ->where('last_seen_at', '<', now()->subMinutes(30))
            ->delete();
    }

    /**
     * @return array{tracks: list<array<string, mixed>>, active: list<array<string, mixed>>}
     */
    private function formatMusicState(): array
    {
        $tracks = MusicTrack::query()
            ->where('active', true)
            ->orderBy('title')
            ->get(['id', 'youtube_id', 'youtube_start_seconds', 'title', 'thumbnail_url'])
            ->map(fn (MusicTrack $track) => [
                'id' => $track->id,
                'youtube_id' => $track->youtube_id,
                'youtube_start_seconds' => $track->youtube_start_seconds,
                'title' => $track->title,
                'thumbnail_url' => $track->thumbnail_url
                    ?? "https://img.youtube.com/vi/{$track->youtube_id}/mqdefault.jpg",
            ])
            ->values()
            ->all();

        $activeOfferings = $this->activeOfferingQuery(MusicOffering::class)
            ->with('track:id,youtube_id,youtube_start_seconds,title,thumbnail_url')
            ->orderBy('created_at')
            ->get();

        $active = $activeOfferings
            ->values()
            ->map(fn (MusicOffering $offering, int $index) => $this->formatMusicOffering(
                $offering,
                $index % 2 === 0 ? 'left' : 'right',
            ))
            ->all();

        return [
            'tracks' => $tracks,
            'active' => $active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMusicOffering(MusicOffering $offering, string $side): array
    {
        $track = $offering->track;

        return [
            'id' => $offering->id,
            'name' => $offering->name,
            'side' => $side,
            'expires_at' => $offering->expires_at->toIso8601String(),
            'track' => [
                'id' => $track->id,
                'youtube_id' => $track->youtube_id,
                'youtube_start_seconds' => $track->youtube_start_seconds,
                'title' => $track->title,
                'thumbnail_url' => $track->thumbnail_url
                    ?? "https://img.youtube.com/vi/{$track->youtube_id}/mqdefault.jpg",
            ],
        ];
    }

    private function nextMusicSide(): string
    {
        $activeCount = $this->activeOfferingQuery(MusicOffering::class)->count();

        return $activeCount % 2 === 0 ? 'left' : 'right';
    }
}
