<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Support\ShrineRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $slug = (string) $request->route('shrine', 'avalokiteshvara');

        if (! ShrineRegistry::exists($slug)) {
            abort(404);
        }

        $validated = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'subscription' => ['required', 'array'],
            'subscription.endpoint' => ['required', 'string'],
            'subscription.keys' => ['required', 'array'],
            'subscription.keys.p256dh' => ['required', 'string'],
            'subscription.keys.auth' => ['required', 'string'],
        ]);

        $endpoint = $validated['subscription']['endpoint'];

        PushSubscription::query()->updateOrCreate(
            [
                'visitor_token' => $validated['visitor_token'],
                'endpoint_hash' => hash('sha256', $endpoint),
            ],
            [
                'shrine' => $slug,
                'endpoint' => $endpoint,
                'public_key' => $validated['subscription']['keys']['p256dh'],
                'auth_token' => $validated['subscription']['keys']['auth'],
            ],
        );

        return response()->json(['ok' => true]);
    }
}
