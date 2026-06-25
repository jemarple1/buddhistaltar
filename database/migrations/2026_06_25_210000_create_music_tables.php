<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_id', 20);
            $table->unsignedInteger('youtube_start_seconds')->nullable();
            $table->string('title');
            $table->string('thumbnail_url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('music_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('music_track_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('music_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('youtube_url');
            $table->string('suggested_by_name')->nullable();
            $table->timestamps();
        });

        DB::table('music_tracks')->insert([
            'youtube_id' => 'QZ94XtY_fJM',
            'youtube_start_seconds' => 814,
            'title' => 'Snow Lion by Tenzin Chogyal',
            'thumbnail_url' => 'https://img.youtube.com/vi/QZ94XtY_fJM/mqdefault.jpg',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('music_suggestions');
        Schema::dropIfExists('music_offerings');
        Schema::dropIfExists('music_tracks');
    }
};
