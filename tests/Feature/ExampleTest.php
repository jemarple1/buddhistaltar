<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_shrine_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Avalokiteshvara');
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
    }

    public function test_a_butter_lamp_can_be_offered(): void
    {
        $response = $this->postJson('/butter-lamps', [
            'name' => 'Tenzin',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('lamp.name', 'Tenzin');
        $response->assertJsonPath('dedication_names.0', 'Tenzin');
        $response->assertJsonPath('offering_names.0', 'Tenzin');
    }

    public function test_mantra_repetitions_are_pooled(): void
    {
        $response = $this->postJson('/mantra-repetitions', ['count' => 108]);
        $response->assertCreated();
        $response->assertJsonPath('total_count', 108);

        $response = $this->postJson('/mantra-repetitions', ['count' => 7]);
        $response->assertJsonPath('total_count', 115);
    }

    public function test_incense_offering_adds_stick_and_sets_expiry(): void
    {
        $response = $this->postJson('/incense-offerings', ['name' => 'Sam']);
        $response->assertCreated();
        $response->assertJsonStructure(['offering' => ['expires_at'], 'shrine_state']);
        $response->assertJsonPath('shrine_state.incense.sticks', 2);
        $response->assertJsonPath('shrine_state.incense.active_offerings', 1);
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

    public function test_water_offering_locks_for_one_person_at_a_time(): void
    {
        $first = $this->postJson('/water-bowls/acquire', []);
        $first->assertCreated();
        $token = $first->json('session.token');

        $second = $this->postJson('/water-bowls/acquire', []);
        $second->assertStatus(423);

        $fill = $this->postJson('/water-bowls/fill', [
            'token' => $token,
            'position' => 1,
        ]);
        $fill->assertOk();
        $fill->assertJsonPath('session.filled_positions.0', 1);
    }

    public function test_flower_offering_is_stored(): void
    {
        $response = $this->postJson('/flower-offerings', ['name' => 'Lotus']);
        $response->assertCreated();
        $response->assertJsonPath('offering.name', 'Lotus');
        $response->assertJsonStructure(['offering' => ['flower_type', 'vase_color']]);
        $this->assertContains($response->json('offering.vase_color'), ['blue', 'white', 'yellow', 'red', 'green']);
    }
}
