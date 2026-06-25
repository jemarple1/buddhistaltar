<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flower_offerings', function (Blueprint $table) {
            $table->string('vase_color', 10)->default('blue')->after('flower_type');
        });
    }

    public function down(): void
    {
        Schema::table('flower_offerings', function (Blueprint $table) {
            $table->dropColumn('vase_color');
        });
    }
};
