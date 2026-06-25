<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('music_tracks', 'youtube_start_seconds')) {
            Schema::table('music_tracks', function (Blueprint $table) {
                $table->unsignedInteger('youtube_start_seconds')->nullable()->after('youtube_id');
            });
        }

        DB::table('music_tracks')
            ->where('youtube_id', 'QZ94XtY_fJM')
            ->update([
                'title' => 'Snow Lion by Tenzin Chogyal',
                'youtube_start_seconds' => 814,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('music_tracks')
            ->where('youtube_id', 'QZ94XtY_fJM')
            ->update([
                'title' => 'Sacred Offering Music',
                'youtube_start_seconds' => null,
                'updated_at' => now(),
            ]);

        if (Schema::hasColumn('music_tracks', 'youtube_start_seconds')) {
            Schema::table('music_tracks', function (Blueprint $table) {
                $table->dropColumn('youtube_start_seconds');
            });
        }
    }
};
