<?php

namespace App\Support;

use App\Models\ButterLamp;
use App\Models\FlowerOffering;
use App\Models\IncenseOffering;
use App\Models\MusicOffering;
use App\Models\WaterBowlSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OfferingGuard
{
    public const MAX_PER_VISITOR = 3;

    public const DURATION_HOURS = 24;

    /** @var list<class-string<Model>> */
    private const EXPIRING_MODELS = [
        ButterLamp::class,
        FlowerOffering::class,
        IncenseOffering::class,
        MusicOffering::class,
        WaterBowlSession::class,
    ];

    /** @var list<class-string<Model>> */
    private const PERMANENT_CAPABLE_MODELS = [
        ButterLamp::class,
        FlowerOffering::class,
        MusicOffering::class,
    ];

    public static function expiresAt(): Carbon
    {
        return now()->addHours(self::DURATION_HOURS);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    public static function applyActiveScope(Builder $query, string $modelClass): Builder
    {
        if (in_array($modelClass, self::EXPIRING_MODELS, true)) {
            if (in_array($modelClass, self::PERMANENT_CAPABLE_MODELS, true)) {
                $query->where(function (Builder $active) {
                    $active->where('is_permanent', true)
                        ->orWhere('expires_at', '>', now());
                });
            } else {
                $query->where('expires_at', '>', now());
            }
        }

        return $query;
    }

    /** @var list<string> */
    private const BLOCKED_WORDS = [
        'asshole',
        'bastard',
        'bitch',
        'bullshit',
        'cock',
        'cunt',
        'damn',
        'dick',
        'fuck',
        'fucking',
        'motherfucker',
        'piss',
        'shit',
        'slut',
        'whore',
    ];

    public static function normalizeName(mixed $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $trimmed = trim($name);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function assertCleanName(?string $name, string $field = 'name'): ?string
    {
        $normalized = self::normalizeName($name);

        if ($normalized !== null && self::containsProfanity($normalized)) {
            throw ValidationException::withMessages([
                $field => 'Please choose a respectful name for this offering.',
            ]);
        }

        return $normalized;
    }

    public static function containsProfanity(string $text): bool
    {
        $normalized = strtolower(preg_replace('/[^a-z\s]/', ' ', $text) ?? '');

        foreach (self::BLOCKED_WORDS as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/', $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function assertWithinLimit(
        string $visitorToken,
        string $modelClass,
        string $label,
        string $shrine = 'avalokiteshvara',
    ): void {
        $count = self::applyActiveScope(
            self::visitorOfferingQuery($modelClass, $visitorToken, $shrine),
            $modelClass,
        )->count();

        if ($count >= self::MAX_PER_VISITOR) {
            throw ValidationException::withMessages([
                'visitor_token' => "You may offer up to ".self::MAX_PER_VISITOR." {$label} in this visit.",
            ]);
        }
    }

    /**
     * @param  array<string, class-string<Model>>  $models
     * @return array<string, array{used: int, remaining: int, max: int}>
     */
    public static function limitsFor(string $visitorToken, array $models, string $shrine = 'avalokiteshvara'): array
    {
        $limits = [];

        foreach ($models as $key => $modelClass) {
            $used = self::applyActiveScope(
                self::visitorOfferingQuery($modelClass, $visitorToken, $shrine),
                $modelClass,
            )->count();

            $limits[$key] = [
                'used' => $used,
                'remaining' => max(0, self::MAX_PER_VISITOR - $used),
                'max' => self::MAX_PER_VISITOR,
            ];
        }

        return $limits;
    }

    /**
     * @return list<array{type: string, id: int, expires_at: string, label: string}>
     */
    public static function visitorOfferingsFor(string $visitorToken, string $shrine = 'avalokiteshvara'): array
    {
        $types = [
            'lamp' => [ButterLamp::class, 'butter lamp offering'],
            'flower' => [FlowerOffering::class, 'flower offering'],
            'incense' => [IncenseOffering::class, 'incense offering'],
            'music' => [MusicOffering::class, 'music offering'],
            'water' => [WaterBowlSession::class, 'water offering'],
        ];

        $offerings = [];

        foreach ($types as $type => [$modelClass, $label]) {
            $rows = self::applyActiveScope(
                self::visitorOfferingQuery($modelClass, $visitorToken, $shrine),
                $modelClass,
            )->get(['id', 'expires_at']);

            foreach ($rows as $row) {
                if ($row->expires_at === null) {
                    continue;
                }

                $offerings[] = [
                    'type' => $type,
                    'id' => (int) $row->id,
                    'expires_at' => $row->expires_at->toIso8601String(),
                    'label' => $label,
                ];
            }
        }

        return $offerings;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    private static function visitorOfferingQuery(
        string $modelClass,
        string $visitorToken,
        string $shrine,
    ): Builder {
        $query = $modelClass::query()
            ->where('shrine', $shrine)
            ->where('visitor_token', $visitorToken);

        if (in_array($modelClass, self::PERMANENT_CAPABLE_MODELS, true)) {
            $query->where('is_permanent', false);
        }

        return $query;
    }
}
