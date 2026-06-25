<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const SHRINES = [
        'avalokiteshvara',
        'amitayus',
        'amitabha',
    ];

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
        foreach (['butter_lamps', 'flower_offerings', 'music_offerings'] as $table) {
            if (! Schema::hasColumn($table, 'is_permanent')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->boolean('is_permanent')->default(false)->after('shrine');
                });
            }
        }

        $now = now();
        $neverExpires = '2037-12-31 23:59:59';

        foreach (self::SHRINES as $shrine) {
            foreach (self::MUSIC_CATALOG as $track) {
                $exists = DB::table('music_tracks')
                    ->where('shrine', $shrine)
                    ->where('youtube_id', $track['youtube_id'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('music_tracks')->insert([
                    'shrine' => $shrine,
                    'youtube_id' => $track['youtube_id'],
                    'youtube_start_seconds' => $track['youtube_start_seconds'],
                    'title' => $track['title'],
                    'thumbnail_url' => $track['thumbnail_url'],
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (self::SHRINES as $shrine) {
            if (! DB::table('butter_lamps')->where('shrine', $shrine)->where('is_permanent', true)->exists()) {
                DB::table('butter_lamps')->insert([
                    'shrine' => $shrine,
                    'is_permanent' => true,
                    'name' => 'All Beings',
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
                    'name' => 'All Beings',
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
                ->where('youtube_id', 'QZ94XtY_fJM')
                ->value('id');

            if ($snowLionTrackId && ! DB::table('music_offerings')->where('shrine', $shrine)->where('is_permanent', true)->exists()) {
                DB::table('music_offerings')->insert([
                    'shrine' => $shrine,
                    'is_permanent' => true,
                    'music_track_id' => $snowLionTrackId,
                    'name' => 'All Beings',
                    'visitor_token' => null,
                    'expires_at' => $neverExpires,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('music_offerings')->where('is_permanent', true)->delete();
        DB::table('flower_offerings')->where('is_permanent', true)->delete();
        DB::table('butter_lamps')->where('is_permanent', true)->delete();

        foreach (['butter_lamps', 'flower_offerings', 'music_offerings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_permanent');
            });
        }
    }
};
