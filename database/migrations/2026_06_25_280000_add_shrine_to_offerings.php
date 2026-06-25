<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const SHRINE_TABLES = [
        'butter_lamps',
        'incense_offerings',
        'flower_offerings',
        'water_bowl_sessions',
        'mantra_repetitions',
        'music_offerings',
        'music_suggestions',
    ];

    public function up(): void
    {
        foreach (self::SHRINE_TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('shrine', 32)->default('avalokiteshvara')->after('id');
                $table->index('shrine');
            });
        }

        Schema::table('music_tracks', function (Blueprint $table) {
            $table->string('shrine', 32)->default('avalokiteshvara')->after('id');
            $table->index('shrine');
        });

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

        foreach (array_reverse(self::SHRINE_TABLES) as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['shrine']);
                $table->dropColumn('shrine');
            });
        }

        Schema::table('music_tracks', function (Blueprint $table) {
            $table->dropIndex(['shrine']);
            $table->dropColumn('shrine');
        });
    }
};
