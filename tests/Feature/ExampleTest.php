<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
    }

    public function test_a_butter_lamp_can_be_offered(): void
    {
        $response = $this->postJson('/butter-lamps', $this->visitorPayload([
            'name' => 'Tenzin',
        ]));

        $response->assertCreated();
        $response->assertJsonPath('lamp.name', 'Tenzin');
        $response->assertJsonPath('dedication_names.0', 'Tenzin');
        $response->assertJsonPath('offering_names.0', 'Tenzin');
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
        $trackId = (int) \Illuminate\Support\Facades\DB::table('music_tracks')->value('id');

        $response = $this->postJson('/music-offerings', $this->visitorPayload([
            'track_id' => $trackId,
            'name' => 'Mila',
        ]));

        $response->assertCreated();
        $response->assertJsonPath('offering.name', 'Mila');
        $response->assertJsonPath('offering.side', 'left');
        $response->assertJsonPath('offering.track.youtube_id', 'QZ94XtY_fJM');
        $response->assertJsonPath('offering.track.title', 'Snow Lion by Tenzin Chogyal');
        $response->assertJsonPath('offering.track.youtube_start_seconds', 814);
        $response->assertJsonPath('shrine_state.music.active.0.name', 'Mila');
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
        $response->assertJsonPath('lamps.0.name', 'Tenzin');
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
        $response->assertJsonPath('lamps.0.name', 'Tenzin');
        $response->assertJsonPath('water.display_name', 'Mila');

        $this->travel(25)->hours();

        $response = $this->getJson('/offerings/state');
        $response->assertJsonPath('incense.active_offerings', 0);
        $response->assertJsonPath('incense.sticks', 2);
        $response->assertJsonPath('lamps', []);
        $response->assertJsonPath('water.display_name', null);
    }
}
