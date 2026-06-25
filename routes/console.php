<?php

use App\Support\OfferingExpiryNotifier;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('offerings:notify-expired', function (OfferingExpiryNotifier $notifier) {
    $sent = $notifier->notifyRecentlyExpired();

    $this->info("Sent {$sent} offering expiry notification(s).");
})->purpose('Send push notifications when visitor offerings expire');

Artisan::command('push:generate-vapid', function () {
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

    $this->line('Add these to your .env file:');
    $this->newLine();
    $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
    $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
})->purpose('Generate VAPID keys for web push notifications');
