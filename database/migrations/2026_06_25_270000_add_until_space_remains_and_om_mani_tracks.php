<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('music_tracks')->insert([
            [
                'youtube_id' => 'UFGWWUNWCU4',
                'youtube_start_seconds' => null,
                'title' => 'Until Space Remains by Philip Glass',
                'thumbnail_url' => 'https://img.youtube.com/vi/UFGWWUNWCU4/mqdefault.jpg',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'youtube_id' => 'z4GYHcJgRcI',
                'youtube_start_seconds' => null,
                'title' => 'Om Mani Pad Me Hum by zul bayar',
                'thumbnail_url' => 'https://img.youtube.com/vi/z4GYHcJgRcI/mqdefault.jpg',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('music_tracks')
            ->whereIn('youtube_id', ['UFGWWUNWCU4', 'z4GYHcJgRcI'])
            ->delete();
    }
};
