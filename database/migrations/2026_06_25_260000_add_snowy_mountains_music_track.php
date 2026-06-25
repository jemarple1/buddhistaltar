<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('music_tracks')->insert([
            'youtube_id' => 'exod-cm3mjQ',
            'youtube_start_seconds' => null,
            'title' => 'Snowy Mountains – GangRi by Tenzin Choegyal & Philip Glass',
            'thumbnail_url' => 'https://img.youtube.com/vi/exod-cm3mjQ/mqdefault.jpg',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('music_tracks')
            ->where('youtube_id', 'exod-cm3mjQ')
            ->delete();
    }
};
