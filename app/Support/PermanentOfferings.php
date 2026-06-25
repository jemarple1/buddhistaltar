<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PermanentOfferings
{
    public const ALL_BEINGS_NAME = 'All Beings';

    public const SNOW_LION_YOUTUBE_ID = 'QZ94XtY_fJM';

    public const SNOW_LION_START_SECONDS = 798;

    public static function ensureForShrine(string $shrine): void
    {
        $now = now();
        $neverExpires = $now->copy()->addYears(100);

        DB::table('music_tracks')
            ->where('shrine', $shrine)
            ->where('youtube_id', self::SNOW_LION_YOUTUBE_ID)
            ->update([
                'youtube_start_seconds' => self::SNOW_LION_START_SECONDS,
                'updated_at' => $now,
            ]);

        if (! DB::table('butter_lamps')->where('shrine', $shrine)->where('is_permanent', true)->exists()) {
            DB::table('butter_lamps')->insert([
                'shrine' => $shrine,
                'is_permanent' => true,
                'name' => self::ALL_BEINGS_NAME,
                'visitor_token' => null,
                'expires_at' => $neverExpires,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('flower_offerings')->where('shrine', $shrine)->where('is_permanent', true)->exists()) {
            DB::table('flower_offerings')->insert([
                'shrine' => $shrine,
                'is_permanent' => true,
                'name' => self::ALL_BEINGS_NAME,
                'visitor_token' => null,
                'flower_type' => 'lotus',
                'vase_color' => 'blue',
                'expires_at' => $neverExpires,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $snowLionTrackId = DB::table('music_tracks')
            ->where('shrine', $shrine)
            ->where('youtube_id', self::SNOW_LION_YOUTUBE_ID)
            ->value('id');

        if (! $snowLionTrackId) {
            return;
        }

        if (! DB::table('music_offerings')->where('shrine', $shrine)->where('is_permanent', true)->exists()) {
            DB::table('music_offerings')->insert([
                'shrine' => $shrine,
                'is_permanent' => true,
                'music_track_id' => $snowLionTrackId,
                'name' => self::ALL_BEINGS_NAME,
                'visitor_token' => null,
                'expires_at' => $neverExpires,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public static function shrineSlugs(): array
    {
        return ShrineRegistry::slugs();
    }
}
