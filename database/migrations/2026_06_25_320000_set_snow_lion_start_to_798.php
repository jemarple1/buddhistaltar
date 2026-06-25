<?php

use App\Support\PermanentOfferings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('music_tracks')
            ->where('youtube_id', PermanentOfferings::SNOW_LION_YOUTUBE_ID)
            ->update([
                'youtube_start_seconds' => PermanentOfferings::SNOW_LION_START_SECONDS,
                'updated_at' => now(),
            ]);

        foreach (PermanentOfferings::shrineSlugs() as $shrine) {
            PermanentOfferings::ensureForShrine($shrine);
        }
    }

    public function down(): void
    {
        DB::table('music_tracks')
            ->where('youtube_id', PermanentOfferings::SNOW_LION_YOUTUBE_ID)
            ->update([
                'youtube_start_seconds' => 814,
                'updated_at' => now(),
            ]);
    }
};
