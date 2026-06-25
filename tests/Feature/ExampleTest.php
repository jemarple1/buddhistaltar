<?php

namespace Tests\Feature;

use App\Support\PermanentOfferings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    private const VISITOR_TOKEN = '550e8400-e29b-41d4-a716-446655440000';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function visitorPayload(array $extra = []): array
    {
        return array_merge(['visitor_token' => self::VISITOR_TOKEN], $extra);
    }

    public function test_the_shrine_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Namo');
        $response->assertSee('Avalokiteshvaraya!');
        $response->assertSee('Homage to');
        $response->assertSee('with Compassion!');
        $response->assertSee('Offerings');
        $response->assertSee('Incense');
        $response->assertSee('Water Bowls');
        $response->assertSee('Flowers');
        $response->assertSee('Butter Lamp Offering');
        $response->assertSee('With this light offering');
        $response->assertSee('Verses of Refuge and Bodhichitta');
        $response->assertSee('Please pair this recitation with prostrations');
        $response->assertSee('Accept');
        $response->assertSee('Dedicate the merit');
        $response->assertSee('The Dedication of Merit');
        $response->assertSee('Read the Sutra');
        $response->assertSee('The Dhāraṇī of Noble Avalokiteśvara');
        $response->assertSee('Mantra Repetitions');
        $response->assertSee('Live practitioners');
        $response->assertSee('Offer music');
        $response->assertSee('Visit the Amitāyus shrine', false);
        $response->assertSee('Visit the Amitābha shrine', false);
        $response->assertSee('Buddhist Altar', false);
        $response->assertSee('Add Buddhist Altar to your home screen', false);
        $response->assertSee('full app experience', false);
        $response->assertSee('Remind me when my offerings expire', false);
    }

    public function test_a_butter_lamp_can_be_offered(): void
    {
        $response = $this->postJson('/butter-lamps', $this->visitorPayload([
            'name' => 'Tenzin',
        ]));

        $response->assertCreated();
        $response->assertJsonPath('lamp.name', 'Tenzin');
        $response->assertJsonFragment(['name' => 'All Beings']);
        $response->assertJsonFragment(['name' => 'Tenzin']);
    }

    public function test_mantra_repetitions_are_pooled(): void
    {
        $response = $this->postJson('/mantra-repetitions', $this->visitorPayload(['count' => 108]));
        $response->assertCreated();
        $response->assertJsonPath('total_count', 108);

        $response = $this->postJson('/mantra-repetitions', $this->visitorPayload(['count' => 7]));
        $response->assertJsonPath('total_count', 115);
    }

    public function test_incense_offering_adds_stick_and_sets_expiry(): void
    {
        $response = $this->postJson('/incense-offerings', $this->visitorPayload(['name' => 'Sam']));
        $response->assertCreated();
        $response->assertJsonStructure(['offering' => ['expires_at'], 'shrine_state']);
        $response->assertJsonPath('shrine_state.incense.sticks', 3);
        $response->assertJsonPath('shrine_state.incense.active_offerings', 1);

        $expiresAt = Carbon::parse($response->json('offering.expires_at'));
        $this->assertTrue($expiresAt->between(now()->addHours(23), now()->addHours(25)));
    }

    public function test_practitioner_heartbeat_returns_live_count(): void
    {
        $token = '550e8400-e29b-41d4-a716-446655440000';

        $response = $this->postJson('/practitioner-presence', ['token' => $token]);
        $response->assertOk();
        $response->assertJsonPath('live_practitioners', 1);

        $response = $this->postJson('/practitioner-presence', [
            'token' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ]);
        $response->assertOk();
        $response->assertJsonPath('live_practitioners', 2);
    }

    public function test_music_offering_plays_beside_the_shrine(): void
    {
        $trackId = (int) \Illuminate\Support\Facades\DB::table('music_tracks')
            ->where('shrine', 'avalokiteshvara')
            ->value('id');

        $response = $this->postJson('/music-offerings', $this->visitorPayload([
            'track_id' => $trackId,
            'name' => 'Mila',
        ]));

        $response->assertCreated();
        $response->assertJsonPath('offering.name', 'Mila');
        $response->assertJsonPath('offering.side', 'left');
        $response->assertJsonPath('offering.track.youtube_id', 'QZ94XtY_fJM');
        $response->assertJsonPath('offering.track.title', 'Snow Lion by Tenzin Chogyal');
        $response->assertJsonPath('offering.track.youtube_start_seconds', 798);
        $response->assertJsonFragment(['name' => 'Mila']);
        $response->assertJsonFragment(['name' => 'All Beings']);
    }

    public function test_music_suggestion_is_stored(): void
    {
        $response = $this->postJson('/music-suggestions', $this->visitorPayload([
            'url' => 'https://www.youtube.com/watch?v=abc123xyz12',
            'name' => 'Sam',
        ]));

        $response->assertCreated();
        $this->assertDatabaseHas('music_suggestions', [
            'youtube_url' => 'https://www.youtube.com/watch?v=abc123xyz12',
            'suggested_by_name' => 'Sam',
        ]);
    }

    public function test_water_offering_can_be_made_with_a_name(): void
    {
        $response = $this->postJson('/water-offerings', $this->visitorPayload(['name' => 'Tenzin']));

        $response->assertCreated();
        $response->assertJsonPath('offering.name', 'Tenzin');
        $response->assertJsonPath('shrine_state.water.display_positions', [1, 2, 3, 4, 5, 6, 7]);
        $response->assertJsonPath('shrine_state.water.display_name', 'Tenzin');
        $this->assertDatabaseHas('water_bowl_sessions', [
            'name' => 'Tenzin',
        ]);
    }

    public function test_flower_offering_is_stored(): void
    {
        $response = $this->postJson('/flower-offerings', $this->visitorPayload(['name' => 'Lotus']));
        $response->assertCreated();
        $response->assertJsonPath('offering.name', 'Lotus');
        $response->assertJsonStructure(['offering' => ['flower_type', 'vase_color']]);
        $this->assertContains($response->json('offering.vase_color'), ['blue', 'white', 'yellow', 'red', 'green']);
    }

    public function test_offering_state_includes_lamps_and_mantra_total(): void
    {
        $this->postJson('/butter-lamps', $this->visitorPayload(['name' => 'Tenzin']))->assertCreated();
        $this->postJson('/mantra-repetitions', $this->visitorPayload(['count' => 21]))->assertCreated();

        $response = $this->getJson('/offerings/state?visitor_token='.self::VISITOR_TOKEN);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'All Beings']);
        $response->assertJsonFragment(['name' => 'Tenzin']);
        $response->assertJsonPath('mantra_total', 21);
    }

    public function test_profanity_in_offering_names_is_rejected(): void
    {
        $response = $this->postJson('/flower-offerings', $this->visitorPayload([
            'name' => 'bad shit',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_visitor_is_limited_to_three_offerings_per_type(): void
    {
        foreach (['One', 'Two', 'Three'] as $name) {
            $this->postJson('/incense-offerings', $this->visitorPayload(['name' => $name]))
                ->assertCreated();
        }

        $response = $this->postJson('/incense-offerings', $this->visitorPayload(['name' => 'Four']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['visitor_token']);
    }

    public function test_offerings_expire_after_twenty_four_hours(): void
    {
        $this->postJson('/incense-offerings', $this->visitorPayload(['name' => 'Sam']))->assertCreated();
        $this->postJson('/butter-lamps', $this->visitorPayload(['name' => 'Tenzin']))->assertCreated();
        $this->postJson('/water-offerings', $this->visitorPayload(['name' => 'Mila']))->assertCreated();

        $response = $this->getJson('/offerings/state');
        $response->assertJsonPath('incense.active_offerings', 1);
        $response->assertJsonFragment(['name' => 'Tenzin']);
        $response->assertJsonPath('water.display_name', 'Mila');

        $this->travel(25)->hours();

        $response = $this->getJson('/offerings/state');
        $response->assertJsonPath('incense.active_offerings', 0);
        $response->assertJsonPath('incense.sticks', 2);
        $response->assertJsonFragment(['name' => 'All Beings']);
        $response->assertJsonMissing(['name' => 'Tenzin']);
        $response->assertJsonMissing(['name' => 'Mila']);
        $response->assertJsonPath('water.display_name', null);
    }

    public function test_each_shrine_has_permanent_all_beings_offerings(): void
    {
        foreach (['/', '/amitayus', '/amitabha'] as $path) {
            $response = $this->getJson($path === '/' ? '/offerings/state' : "{$path}/offerings/state");

            $response->assertOk();
            $response->assertJsonFragment(['name' => 'All Beings']);
            $response->assertJsonPath('music.active.0.name', 'All Beings');
            $response->assertJsonPath('music.active.0.track.title', 'Snow Lion by Tenzin Chogyal');
            $response->assertJsonPath('music.active.0.track.youtube_start_seconds', 798);
        }
    }

    public function test_visitor_flower_is_added_alongside_all_beings_flower(): void
    {
        $this->postJson('/flower-offerings', $this->visitorPayload(['name' => 'Lotus']))->assertCreated();

        $response = $this->getJson('/offerings/state');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'All Beings']);
        $response->assertJsonFragment(['name' => 'Lotus']);
        $response->assertJsonPath('flowers.0.name', 'All Beings');
    }

    public function test_ensure_for_shrine_does_not_duplicate_permanent_music(): void
    {
        PermanentOfferings::ensureForShrine('avalokiteshvara');
        PermanentOfferings::ensureForShrine('avalokiteshvara');

        $this->assertSame(1, DB::table('music_offerings')->where('shrine', 'avalokiteshvara')->where('is_permanent', true)->count());
        $this->assertSame(1, DB::table('butter_lamps')->where('shrine', 'avalokiteshvara')->where('is_permanent', true)->count());
        $this->assertSame(1, DB::table('flower_offerings')->where('shrine', 'avalokiteshvara')->where('is_permanent', true)->count());

        $response = $this->getJson('/offerings/state');

        $this->assertCount(1, collect($response->json('music.active'))->where('is_permanent', true));
        $this->assertCount(1, collect($response->json('lamps'))->where('is_permanent', true));
        $this->assertCount(1, collect($response->json('flowers'))->where('is_permanent', true));
    }

    public function test_each_shrine_shares_the_same_music_catalog(): void
    {
        $expectedTitles = [
            'Om Mani Pad Me Hum by zul bayar',
            'Snow Lion by Tenzin Chogyal',
            'Snowy Mountains – GangRi by Tenzin Choegyal & Philip Glass',
            'Until Space Remains by Philip Glass',
        ];

        foreach (['/', '/amitayus', '/amitabha'] as $path) {
            $response = $this->getJson($path === '/' ? '/offerings/state' : "{$path}/offerings/state");
            $titles = collect($response->json('music.tracks'))->pluck('title')->sort()->values()->all();

            $response->assertOk();
            $this->assertSame($expectedTitles, $titles);
        }
    }

    public function test_amitayus_shrine_page_loads(): void
    {
        $response = $this->get('/amitayus');

        $response->assertStatus(200);
        $response->assertSee('Namo Amitayus!');
        $response->assertSee('Homage to the Buddha of Infinite Life!');
        $response->assertSee('Read the Prayer');
        $response->assertSee('A Prayer to Amitāyus', false);
        $response->assertSee('oṃ amaraṇi jīvantaye svāhā');
        $response->assertSee('Visit the Avalokiteśvara shrine', false);
        $response->assertSee('Visit the Amitābha shrine', false);
    }

    public function test_amitayus_offerings_are_isolated_from_avalokiteshvara(): void
    {
        $this->postJson('/butter-lamps', $this->visitorPayload(['name' => 'Chenrezik']))->assertCreated();
        $this->postJson('/amitayus/butter-lamps', $this->visitorPayload(['name' => 'Amitayus']))->assertCreated();

        $avalokiteshvara = $this->getJson('/offerings/state');
        $avalokiteshvara->assertJsonFragment(['name' => 'Chenrezik']);
        $avalokiteshvara->assertJsonMissing(['name' => 'Amitayus']);

        $amitayus = $this->getJson('/amitayus/offerings/state');
        $amitayus->assertJsonFragment(['name' => 'Amitayus']);
        $amitayus->assertJsonMissing(['name' => 'Chenrezik']);
    }

    public function test_amitabha_shrine_page_loads(): void
    {
        $response = $this->get('/amitabha');

        $response->assertStatus(200);
        $response->assertSee('Namo Amitabha!');
        $response->assertSee('Homage to the Buddha of Boundless Light!');
        $response->assertSee('Read the Prayer');
        $response->assertSee('Prayer to Buddha Amitābha', false);
        $response->assertSee('oṃ amitābha hrīḥ');
        $response->assertSee('Visit the Avalokiteśvara shrine', false);
        $response->assertSee('Visit the Amitāyus shrine', false);
    }

    public function test_amitabha_offerings_are_isolated_from_other_shrines(): void
    {
        $this->postJson('/butter-lamps', $this->visitorPayload(['name' => 'Chenrezik']))->assertCreated();
        $this->postJson('/amitayus/butter-lamps', $this->visitorPayload(['name' => 'Amitayus']))->assertCreated();
        $this->postJson('/amitabha/butter-lamps', $this->visitorPayload(['name' => 'Amitabha']))->assertCreated();

        $avalokiteshvara = $this->getJson('/offerings/state');
        $avalokiteshvara->assertJsonFragment(['name' => 'Chenrezik']);
        $avalokiteshvara->assertJsonMissing(['name' => 'Amitabha']);

        $amitayus = $this->getJson('/amitayus/offerings/state');
        $amitayus->assertJsonFragment(['name' => 'Amitayus']);
        $amitayus->assertJsonMissing(['name' => 'Amitabha']);

        $amitabha = $this->getJson('/amitabha/offerings/state');
        $amitabha->assertJsonFragment(['name' => 'Amitabha']);
        $amitabha->assertJsonMissing(['name' => 'Chenrezik']);
    }

    public function test_offerings_state_includes_visitor_offerings_for_token(): void
    {
        $this->postJson('/butter-lamps', $this->visitorPayload(['name' => 'Tenzin']))->assertCreated();

        $response = $this->getJson('/offerings/state?'.http_build_query([
            'visitor_token' => self::VISITOR_TOKEN,
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'visitor_offerings');
        $response->assertJsonPath('visitor_offerings.0.type', 'lamp');
        $response->assertJsonPath('visitor_offerings.0.label', 'butter lamp offering');
    }

    public function test_dedication_names_exclude_all_beings(): void
    {
        $response = $this->getJson('/offerings/state');

        $response->assertOk();
        $this->assertNotContains('All Beings', $response->json('offering_names') ?? []);
        $this->assertNotContains('All Beings', $response->json('dedication_names') ?? []);
    }

    public function test_push_subscription_can_be_stored(): void
    {
        $response = $this->postJson('/push-subscriptions', [
            'visitor_token' => self::VISITOR_TOKEN,
            'subscription' => [
                'endpoint' => 'https://push.example.test/subscription/1',
                'keys' => [
                    'p256dh' => 'test-public-key',
                    'auth' => 'test-auth-token',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertDatabaseHas('push_subscriptions', [
            'visitor_token' => self::VISITOR_TOKEN,
            'shrine' => 'avalokiteshvara',
            'endpoint' => 'https://push.example.test/subscription/1',
        ]);
    }

    public function test_expiry_notifier_records_sent_notifications_without_vapid_keys(): void
    {
        $this->postJson('/butter-lamps', $this->visitorPayload(['name' => 'Tenzin']))->assertCreated();

        DB::table('butter_lamps')
            ->where('name', 'Tenzin')
            ->update(['expires_at' => Carbon::now()->subMinute()]);

        $this->artisan('offerings:notify-expired')->assertSuccessful();

        $this->assertDatabaseMissing('offering_expiry_notifications', [
            'offering_type' => 'lamp',
        ]);
    }
}
