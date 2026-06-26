<?php

namespace Tests\Feature;

use App\Support\PublicShrineState;
use App\Support\ShrineRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShrineStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shrine_state_strips_offering_names(): void
    {
        $state = PublicShrineState::forHtml([
            'lamps' => [['id' => 1, 'name' => 'Tenzin', 'is_permanent' => false]],
            'flowers' => [['id' => 2, 'name' => 'Lotus', 'is_permanent' => false]],
            'music' => ['active' => [['id' => 3, 'name' => 'Music Name']]],
            'water' => ['display_positions' => [1], 'display_name' => 'Water Name'],
            'offering_names' => ['Tenzin'],
            'dedication_names' => ['Tenzin'],
        ]);

        $this->assertArrayNotHasKey('name', $state['lamps'][0]);
        $this->assertArrayNotHasKey('name', $state['flowers'][0]);
        $this->assertArrayNotHasKey('name', $state['music']['active'][0]);
        $this->assertArrayNotHasKey('display_name', $state['water']);
        $this->assertArrayNotHasKey('offering_names', $state);
        $this->assertArrayNotHasKey('dedication_names', $state);
    }

    public function test_shrine_seo_meta_includes_structured_data(): void
    {
        $seo = ShrineRegistry::seoMeta('avalokiteshvara');

        $this->assertSame('Avalokiteśvara Online Shrine — Buddhist Altar', $seo['title']);
        $this->assertStringContainsString('Avalokiteśvara', $seo['description']);
        $this->assertSame('WebApplication', $seo['structured_data']['@type']);
    }
}
