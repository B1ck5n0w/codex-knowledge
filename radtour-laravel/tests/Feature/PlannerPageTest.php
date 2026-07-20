<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlannerPageTest extends TestCase
{
    public function test_the_planner_page_exposes_the_guided_planning_controls(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Familien-Radtourenplaner')
            ->assertSee('id="startAddress"', false)
            ->assertSee('id="destination"', false)
            ->assertSee('id="findGoals"', false)
            ->assertSee('id="findStops"', false)
            ->assertSee('id="calculate"', false)
            ->assertSee('id="download"', false);
    }

    public function test_automatic_start_lookup_cannot_replace_an_existing_manual_start(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('setTimeout(() => { if (!start) findStartAddress(); },650);', false);
    }
}
