<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        'music_tracks',
    ];

    public function up(): void
    {
        foreach (self::SHRINE_TABLES as $table) {
            DB::table($table)
                ->where('shrine', 'amitabha')
                ->update(['shrine' => 'amitayus']);
        }
    }

    public function down(): void
    {
        foreach (self::SHRINE_TABLES as $table) {
            DB::table($table)
                ->where('shrine', 'amitayus')
                ->update(['shrine' => 'amitabha']);
        }
    }
};
