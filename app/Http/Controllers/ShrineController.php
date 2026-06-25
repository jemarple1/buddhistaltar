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

    public function index(): View
    {
        $lamps = ButterLamp::query()
            ->latest()
            ->limit(200)
            ->get(['id', 'name', 'created_at']);

        $flowers = FlowerOffering::query()
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

    public function state(): JsonResponse
    {
        return response()->json($this->buildShrineState());
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
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $lamp = ButterLamp::create([
            'name' => isset($validated['name']) ? trim($validated['name']) : null,
        ]);

        if ($lamp->name === '') {
            $lamp->name = null;
            $lamp->save();
        }

        return response()->json([
            'lamp' => [
                'id' => $lamp->id,
                'name' => $lamp->name,
                'created_at' => $lamp->created_at?->toIso8601String(),
            ],
            'dedication_names' => $this->dedicationNames(),
            'offering_names' => $this->offeringNames(),
        ], 201);
    }

    public function storeMantra(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $offering = MantraRepetition::create([
            'count' => $validated['count'],
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
        ], 201);
    }

    public function storeIncense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $name = isset($validated['name']) ? trim($validated['name']) : null;
        if ($name === '') {
            $name = null;
        }

        $offering = IncenseOffering::create([
            'name' => $name,
            'expires_at' => now()->addMinutes(15),
        ]);

        return response()->json([
            'offering' => [
                'id' => $offering->id,
                'name' => $offering->name,
                'expires_at' => $offering->expires_at->toIso8601String(),
            ],
            'shrine_state' => $this->buildShrineState(),
        ], 201);
    }

    public function storeFlower(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $name = isset($validated['name']) ? trim($validated['name']) : null;
        if ($name === '') {
            $name = null;
        }

        $offering = FlowerOffering::create([
            'name' => $name,
            'flower_type' => self::FLOWER_TYPES[array_rand(self::FLOWER_TYPES)],
            'vase_color' => self::VASE_COLORS[array_rand(self::VASE_COLORS)],
        ]);

        return response()->json([
            'offering' => [
                'id' => $offering->id,
                'name' => $offering->name,
                'flower_type' => $offering->flower_type,
                'vase_color' => $offering->vase_color,
                'created_at' => $offering->created_at?->toIso8601String(),
            ],
            'shrine_state' => $this->buildShrineState(),
        ], 201);
    }

    public function storeMusic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'track_id' => ['required', 'integer', 'exists:music_tracks,id'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $track = MusicTrack::query()
            ->where('active', true)
            ->findOrFail($validated['track_id']);

        $name = isset($validated['name']) ? trim($validated['name']) : null;
        if ($name === '') {
            $name = null;
        }

        $side = $this->nextMusicSide();

        $offering = MusicOffering::create([
            'music_track_id' => $track->id,
            'name' => $name,
            'expires_at' => now()->addMinutes(15),
        ]);

        $offering->load('track');

        return response()->json([
            'offering' => $this->formatMusicOffering($offering, $side),
            'shrine_state' => $this->buildShrineState(),
        ], 201);
    }

    public function storeMusicSuggestion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500', 'regex:/(?:youtube\.com|youtu\.be)/i'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $name = isset($validated['name']) ? trim($validated['name']) : null;
        if ($name === '') {
            $name = null;
        }

        MusicSuggestion::create([
            'youtube_url' => trim($validated['url']),
            'suggested_by_name' => $name,
        ]);

        return response()->json([
            'message' => 'Thank you — your suggestion has been recorded.',
        ], 201);
    }

    public function acquireWaterLock(Request $request): JsonResponse
    {
        $this->expireStaleWaterSessions();

        $token = $request->input('token');

        $active = WaterBowlSession::query()
            ->whereNull('completed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($active !== null) {
            if ($token && $active->token === $token) {
                return response()->json([
                    'session' => $this->formatWaterSession($active),
                    'shrine_state' => $this->buildShrineState($token),
                ]);
            }

            return response()->json([
                'message' => 'Someone is currently offering water.',
                'session' => $this->formatWaterSession($active),
                'shrine_state' => $this->buildShrineState($token),
            ], 423);
        }

        $session = WaterBowlSession::create([
            'token' => (string) Str::uuid(),
            'filled_positions' => [],
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'session' => $this->formatWaterSession($session),
            'shrine_state' => $this->buildShrineState($session->token),
        ], 201);
    }

    public function fillWaterBowl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'position' => ['required', 'integer', 'min:1', 'max:7'],
        ]);

        $this->expireStaleWaterSessions();

        $session = WaterBowlSession::query()
            ->where('token', $validated['token'])
            ->whereNull('completed_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($session === null) {
            return response()->json([
                'message' => 'Your water offering session has expired.',
                'shrine_state' => $this->buildShrineState(),
            ], 410);
        }

        $positions = $session->filled_positions ?? [];

        if (in_array($validated['position'], $positions, true)) {
            return response()->json([
                'message' => 'This bowl has already been filled.',
                'session' => $this->formatWaterSession($session),
                'shrine_state' => $this->buildShrineState($session->token),
            ], 422);
        }

        $positions[] = $validated['position'];
        sort($positions);

        $session->filled_positions = $positions;
        $session->expires_at = now()->addMinutes(10);

        if (count($positions) >= 7) {
            $session->completed_at = now();
        }

        $session->save();

        return response()->json([
            'session' => $this->formatWaterSession($session),
            'shrine_state' => $this->buildShrineState($session->token),
        ]);
    }

    /**
     * @return list<string>
     */
    private function offeringNames(): array
    {
        $entries = collect()
            ->merge(
                ButterLamp::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                FlowerOffering::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                IncenseOffering::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->get(['name', 'created_at']),
            )
            ->merge(
                MusicOffering::query()
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
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
        return ButterLamp::query()
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
    private function buildShrineState(?string $viewerToken = null): array
    {
        $this->expireStaleWaterSessions();

        $activeWater = WaterBowlSession::query()
            ->whereNull('completed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        $displayWater = WaterBowlSession::query()
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->get()
            ->first(fn (WaterBowlSession $session) => count($session->filled_positions ?? []) >= 7);

        $flowers = FlowerOffering::query()
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

        return [
            'incense' => $this->formatIncenseState(),
            'flowers' => $flowers,
            'music' => $this->formatMusicState(),
            'offering_names' => $this->offeringNames(),
            'live_practitioners' => $this->livePractitionerCount(),
            'water' => [
                'display_positions' => $displayWater?->filled_positions ?? [],
                'active' => $activeWater !== null,
                'locked_by_other' => $activeWater !== null && $viewerToken !== $activeWater->token,
                'session' => $activeWater ? $this->formatWaterSession($activeWater) : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatWaterSession(WaterBowlSession $session): array
    {
        return [
            'token' => $session->token,
            'filled_positions' => $session->filled_positions ?? [],
            'expires_at' => $session->expires_at->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
        ];
    }

    private function expireStaleWaterSessions(): void
    {
        WaterBowlSession::query()
            ->whereNull('completed_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * @return array{sticks: int, active_offerings: int, expires_at: string|null}
     */
    private function formatIncenseState(): array
    {
        $activeOfferings = IncenseOffering::query()
            ->where('expires_at', '>', now())
            ->count();

        $expiresAt = IncenseOffering::query()
            ->where('expires_at', '>', now())
            ->max('expires_at');

        return [
            'sticks' => 1 + $activeOfferings,
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

        $activeOfferings = MusicOffering::query()
            ->with('track:id,youtube_id,youtube_start_seconds,title,thumbnail_url')
            ->where('expires_at', '>', now())
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
        $activeCount = MusicOffering::query()
            ->where('expires_at', '>', now())
            ->count();

        return $activeCount % 2 === 0 ? 'left' : 'right';
    }
}
