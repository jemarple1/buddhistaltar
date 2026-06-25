<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('music_tracks')->where('shrine', 'amitabha')->exists()) {
            return;
        }

        $now = now();

        DB::table('music_tracks')->insert([
            [
                'shrine' => 'amitabha',
                'youtube_id' => 'UFGWWUNWCU4',
                'youtube_start_seconds' => null,
                'title' => 'Until Space Remains by Philip Glass',
                'thumbnail_url' => 'https://img.youtube.com/vi/UFGWWUNWCU4/mqdefault.jpg',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'shrine' => 'amitabha',
                'youtube_id' => 'exod-cm3mjQ',
                'youtube_start_seconds' => null,
                'title' => 'Snowy Mountains – GangRi by Tenzin Choegyal & Philip Glass',
                'thumbnail_url' => 'https://img.youtube.com/vi/exod-cm3mjQ/mqdefault.jpg',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('music_tracks')
            ->where('shrine', 'amitabha')
            ->delete();
    }
};
