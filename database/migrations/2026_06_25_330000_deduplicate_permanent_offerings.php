<?php

use App\Support\PermanentOfferings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private const PERMANENT_TABLES = [
        'butter_lamps',
        'flower_offerings',
        'music_offerings',
    ];

    public function up(): void
    {
        foreach (PermanentOfferings::shrineSlugs() as $shrine) {
            foreach (self::PERMANENT_TABLES as $table) {
                $ids = DB::table($table)
                    ->where('shrine', $shrine)
                    ->where('is_permanent', true)
                    ->orderBy('id')
                    ->pluck('id');

                if ($ids->count() <= 1) {
                    continue;
                }

                DB::table($table)
                    ->whereIn('id', $ids->slice(1)->all())
                    ->delete();
            }

            PermanentOfferings::ensureForShrine($shrine);
        }
    }

    public function down(): void
    {
        // Irreversible cleanup.
    }
};
