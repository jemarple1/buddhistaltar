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
            $query->where('expires_at', '>', now());
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
    public static function assertWithinLimit(string $visitorToken, string $modelClass, string $label): void
    {
        $count = self::applyActiveScope(
            $modelClass::query()->where('visitor_token', $visitorToken),
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
    public static function limitsFor(string $visitorToken, array $models): array
    {
        $limits = [];

        foreach ($models as $key => $modelClass) {
            $used = self::applyActiveScope(
                $modelClass::query()->where('visitor_token', $visitorToken),
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
}
