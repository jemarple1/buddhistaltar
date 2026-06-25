<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_token');
            $table->string('shrine', 64);
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->timestamps();

            $table->unique(['visitor_token', 'endpoint']);
            $table->index(['shrine', 'visitor_token']);
        });

        Schema::create('offering_expiry_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('offering_type', 32);
            $table->unsignedBigInteger('offering_id');
            $table->timestamp('notified_at');

            $table->unique(['offering_type', 'offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offering_expiry_notifications');
        Schema::dropIfExists('push_subscriptions');
    }
};
