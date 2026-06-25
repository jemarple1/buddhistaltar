<?php

use App\Support\PermanentOfferings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('butter_lamps', 'is_permanent')) {
            return;
        }

        $safeExpires = PermanentOfferings::FAR_FUTURE_EXPIRES_AT;

        foreach (['butter_lamps', 'flower_offerings', 'music_offerings'] as $table) {
            DB::table($table)
                ->where('is_permanent', true)
                ->update([
                    'expires_at' => $safeExpires,
                    'updated_at' => now(),
                ]);
        }

        foreach (PermanentOfferings::shrineSlugs() as $shrine) {
            PermanentOfferings::ensureForShrine($shrine);
        }
    }

    public function down(): void
    {
        // Irreversible data fix.
    }
};
