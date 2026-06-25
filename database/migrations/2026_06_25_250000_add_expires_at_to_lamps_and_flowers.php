<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('butter_lamps', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('visitor_token');
        });

        Schema::table('flower_offerings', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('visitor_token');
        });

        $this->backfill('butter_lamps');
        $this->backfill('flower_offerings');
    }

    public function down(): void
    {
        Schema::table('butter_lamps', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('flower_offerings', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }

    private function backfill(string $table): void
    {
        DB::table($table)
            ->whereNull('expires_at')
            ->orderBy('id')
            ->lazyById()
            ->each(function ($row) use ($table) {
                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        'expires_at' => Carbon::parse($row->created_at)->addHours(24),
                    ]);
            });
    }
};
