<?php

use App\Support\PermanentOfferings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<array{youtube_id: string, youtube_start_seconds: int|null, title: string, thumbnail_url: string}> */
    private const MUSIC_CATALOG = [
        [
            'youtube_id' => 'QZ94XtY_fJM',
            'youtube_start_seconds' => 798,
            'title' => 'Snow Lion by Tenzin Chogyal',
            'thumbnail_url' => 'https://img.youtube.com/vi/QZ94XtY_fJM/mqdefault.jpg',
        ],
        [
            'youtube_id' => 'exod-cm3mjQ',
            'youtube_start_seconds' => null,
            'title' => 'Snowy Mountains – GangRi by Tenzin Choegyal & Philip Glass',
            'thumbnail_url' => 'https://img.youtube.com/vi/exod-cm3mjQ/mqdefault.jpg',
        ],
        [
            'youtube_id' => 'UFGWWUNWCU4',
            'youtube_start_seconds' => null,
            'title' => 'Until Space Remains by Philip Glass',
            'thumbnail_url' => 'https://img.youtube.com/vi/UFGWWUNWCU4/mqdefault.jpg',
        ],
        [
            'youtube_id' => 'z4GYHcJgRcI',
            'youtube_start_seconds' => null,
            'title' => 'Om Mani Pad Me Hum by zul bayar',
            'thumbnail_url' => 'https://img.youtube.com/vi/z4GYHcJgRcI/mqdefault.jpg',
        ],
    ];

    public function up(): void
    {
        if (DB::table('music_tracks')->where('shrine', 'shakyamuni')->exists()) {
            PermanentOfferings::ensureForShrine('shakyamuni');

            return;
        }

        $now = now();

        foreach (self::MUSIC_CATALOG as $track) {
            DB::table('music_tracks')->insert([
                'shrine' => 'shakyamuni',
                'youtube_id' => $track['youtube_id'],
                'youtube_start_seconds' => $track['youtube_start_seconds'],
                'title' => $track['title'],
                'thumbnail_url' => $track['thumbnail_url'],
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        PermanentOfferings::ensureForShrine('shakyamuni');
    }

    public function down(): void
    {
        DB::table('music_offerings')->where('shrine', 'shakyamuni')->delete();
        DB::table('flower_offerings')->where('shrine', 'shakyamuni')->delete();
        DB::table('butter_lamps')->where('shrine', 'shakyamuni')->delete();
        DB::table('music_tracks')->where('shrine', 'shakyamuni')->delete();
    }
};
