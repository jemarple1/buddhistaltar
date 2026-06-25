<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner_presences', function (Blueprint $table) {
            $table->uuid('session_token')->primary();
            $table->timestamp('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_presences');
    }
};
