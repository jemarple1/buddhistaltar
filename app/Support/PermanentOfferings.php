<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PermanentOfferings
{
    public const ALL_BEINGS_NAME = 'All Beings';

    public const SNOW_LION_YOUTUBE_ID = 'QZ94XtY_fJM';

    public const SNOW_LION_START_SECONDS = 798;

    /** MySQL TIMESTAMP columns cannot store dates beyond 2038. */
    public const FAR_FUTURE_EXPIRES_AT = '2037-12-31 23:59:59';

    public static function farFutureExpiresAt(): Carbon
    {
        return Carbon::parse(self::FAR_FUTURE_EXPIRES_AT);
    }

    public static function ensureForShrine(string $shrine): void
    {
        $now = now();
        $neverExpires = self::farFutureExpiresAt();

        DB::table('music_tracks')
            ->where('shrine', $shrine)
            ->where('youtube_id', self::SNOW_LION_YOUTUBE_ID)
            ->update([
                'youtube_start_seconds' => self::SNOW_LION_START_SECONDS,
                'updated_at' => $now,
            ]);

        self::ensureSinglePermanent(
            table: 'butter_lamps',
            shrine: $shrine,
            now: $now,
            neverExpires: $neverExpires,
            attributes: [
                'name' => self::ALL_BEINGS_NAME,
                'visitor_token' => null,
            ],
        );

        self::ensureSinglePermanent(
            table: 'flower_offerings',
            shrine: $shrine,
            now: $now,
            neverExpires: $neverExpires,
            attributes: [
                'name' => self::ALL_BEINGS_NAME,
                'visitor_token' => null,
                'flower_type' => 'lotus',
                'vase_color' => 'blue',
            ],
        );

        $snowLionTrackId = DB::table('music_tracks')
            ->where('shrine', $shrine)
            ->where('youtube_id', self::SNOW_LION_YOUTUBE_ID)
            ->value('id');

        if (! $snowLionTrackId) {
            return;
        }

        self::ensureSinglePermanent(
            table: 'music_offerings',
            shrine: $shrine,
            now: $now,
            neverExpires: $neverExpires,
            attributes: [
                'music_track_id' => $snowLionTrackId,
                'name' => self::ALL_BEINGS_NAME,
                'visitor_token' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function ensureSinglePermanent(
        string $table,
        string $shrine,
        $now,
        $neverExpires,
        array $attributes,
    ): void {
        DB::transaction(function () use ($table, $shrine, $now, $neverExpires, $attributes): void {
            $permanentIds = DB::table($table)
                ->where('shrine', $shrine)
                ->where('is_permanent', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            if ($permanentIds->count() > 1) {
                DB::table($table)
                    ->whereIn('id', $permanentIds->slice(1)->all())
                    ->delete();
            }

            $keeperId = $permanentIds->first();

            if ($keeperId === null) {
                DB::table($table)->insert(array_merge([
                    'shrine' => $shrine,
                    'is_permanent' => true,
                    'expires_at' => $neverExpires,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $attributes));

                return;
            }

            DB::table($table)
                ->where('id', $keeperId)
                ->update(array_merge($attributes, [
                    'expires_at' => $neverExpires,
                    'updated_at' => $now,
                ]));
        });
    }

    /**
     * @return list<string>
     */
    public static function shrineSlugs(): array
    {
        return ShrineRegistry::slugs();
    }
}
