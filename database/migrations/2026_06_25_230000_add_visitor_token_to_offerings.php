<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('butter_lamps', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('name');
            $table->index('visitor_token');
        });

        Schema::table('incense_offerings', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('name');
            $table->index('visitor_token');
        });

        Schema::table('flower_offerings', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('name');
            $table->index('visitor_token');
        });

        Schema::table('music_offerings', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('name');
            $table->index('visitor_token');
        });

        Schema::table('music_suggestions', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('suggested_by_name');
            $table->index('visitor_token');
        });

        Schema::table('mantra_repetitions', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('count');
            $table->index('visitor_token');
        });

        Schema::table('water_bowl_sessions', function (Blueprint $table) {
            $table->uuid('visitor_token')->nullable()->after('token');
            $table->index('visitor_token');
        });
    }

    public function down(): void
    {
        foreach ([
            'butter_lamps',
            'incense_offerings',
            'flower_offerings',
            'music_offerings',
            'music_suggestions',
            'mantra_repetitions',
            'water_bowl_sessions',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['visitor_token']);
                $table->dropColumn('visitor_token');
            });
        }
    }
};
