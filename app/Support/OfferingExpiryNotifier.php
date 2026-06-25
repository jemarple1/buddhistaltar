<?php

namespace App\Support;

use App\Models\ButterLamp;
use App\Models\FlowerOffering;
use App\Models\IncenseOffering;
use App\Models\MusicOffering;
use App\Models\PushSubscription;
use App\Models\WaterBowlSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class OfferingExpiryNotifier
{
    /** @var array<string, array{class-string<Model>, string, bool}> */
    private const OFFERING_TYPES = [
        'lamp' => [ButterLamp::class, 'butter lamp', true],
        'flower' => [FlowerOffering::class, 'flower', true],
        'incense' => [IncenseOffering::class, 'incense', false],
        'music' => [MusicOffering::class, 'music offering', true],
        'water' => [WaterBowlSession::class, 'water offering', false],
    ];

    public function notifyRecentlyExpired(int $lookbackMinutes = 15): int
    {
        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        if (! is_string($publicKey) || $publicKey === '' || ! is_string($privateKey) || $privateKey === '') {
            return 0;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $windowStart = now()->subMinutes($lookbackMinutes);
        $sent = 0;

        foreach ($this->expiredOfferings($windowStart) as $offering) {
            if ($this->wasNotified($offering['type'], $offering['id'])) {
                continue;
            }

            $subscriptions = PushSubscription::query()
                ->where('visitor_token', $offering['visitor_token'])
                ->where('shrine', $offering['shrine'])
                ->get();

            if ($subscriptions->isEmpty()) {
                continue;
            }

            $shrineName = ShrineRegistry::crosslinkDisplayName($offering['shrine']);
            $label = $offering['label'];
            $body = "Your {$label} at the {$shrineName} shrine has ended after 24 hours.";
            $url = ShrineRegistry::apiBase($offering['shrine']);
            $url = $url === '' ? '/' : $url;

            $payload = json_encode([
                'title' => 'Offering ended',
                'body' => $body,
                'url' => $url,
                'tag' => "offering-expired-{$offering['type']}-{$offering['id']}",
            ], JSON_THROW_ON_ERROR);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                    ]),
                    $payload,
                );
            }

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    continue;
                }
            }

            $this->markNotified($offering['type'], $offering['id']);
            $sent++;
        }

        return $sent;
    }

    /**
     * @return list<array{type: string, id: int, shrine: string, visitor_token: string, label: string}>
     */
    private function expiredOfferings(Carbon $windowStart): array
    {
        $offerings = [];

        foreach (self::OFFERING_TYPES as $type => [$modelClass, $label, $skipPermanent]) {
            $query = $modelClass::query()
                ->whereNotNull('visitor_token')
                ->where('expires_at', '<=', now())
                ->where('expires_at', '>', $windowStart);

            if ($skipPermanent) {
                $query->where('is_permanent', false);
            }

            foreach ($query->get(['id', 'shrine', 'visitor_token']) as $row) {
                $offerings[] = [
                    'type' => $type,
                    'id' => (int) $row->id,
                    'shrine' => (string) $row->shrine,
                    'visitor_token' => (string) $row->visitor_token,
                    'label' => $label,
                ];
            }
        }

        return $offerings;
    }

    private function wasNotified(string $type, int $id): bool
    {
        return DB::table('offering_expiry_notifications')
            ->where('offering_type', $type)
            ->where('offering_id', $id)
            ->exists();
    }

    private function markNotified(string $type, int $id): void
    {
        DB::table('offering_expiry_notifications')->insert([
            'offering_type' => $type,
            'offering_id' => $id,
            'notified_at' => now(),
        ]);
    }
}
