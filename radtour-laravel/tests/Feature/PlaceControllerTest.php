<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlaceControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('services.google.places_key', 'test-key');
        config()->set('services.google.places_daily_limit', 2);
        config()->set('services.google.places_cache_minutes', 60);
    }

    public function test_search_filters_places_outside_the_requested_radius_and_caches_the_result(): void
    {
        Http::fake([
            'https://places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [
                    [
                        'id' => 'inside-place-123',
                        'displayName' => ['text' => 'Im Radius'],
                        'location' => ['latitude' => 51.584, 'longitude' => 6.252],
                    ],
                    [
                        'id' => 'outside-place-456',
                        'displayName' => ['text' => 'Außerhalb'],
                        'location' => ['latitude' => 51.684, 'longitude' => 6.252],
                    ],
                ],
            ]),
        ]);

        $payload = [
            'text' => 'Spielplatz',
            'latitude' => 51.584,
            'longitude' => 6.252,
            'radius' => 500,
        ];

        $first = $this->postJson('/api/places/search', $payload)
            ->assertOk()
            ->assertJsonPath('cached', false)
            ->assertJsonCount(1, 'places');

        $this->assertSame('inside-place-123', $first->json('places.0.id'));

        $this->postJson('/api/places/search', $payload)
            ->assertOk()
            ->assertJsonPath('cached', true)
            ->assertJsonPath('usage.used', 1);

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return isset($body['locationRestriction']['rectangle']['low'])
                && ! isset($body['locationBias']);
        });
    }

    public function test_daily_guard_blocks_a_new_external_lookup_after_the_limit(): void
    {
        config()->set('services.google.places_daily_limit', 1);
        Http::fake([
            'https://places.googleapis.com/*' => Http::response(['places' => []]),
        ]);

        $this->postJson('/api/places/search', [
            'text' => 'Café',
            'latitude' => 51.584,
            'longitude' => 6.252,
            'radius' => 500,
        ])->assertOk();

        $this->postJson('/api/places/search', [
            'text' => 'Biergarten',
            'latitude' => 51.584,
            'longitude' => 6.252,
            'radius' => 500,
        ])->assertStatus(429)
            ->assertJsonPath('usage.blocked', true);

        Http::assertSentCount(1);
    }

    public function test_invalid_search_inputs_are_rejected_without_an_external_request(): void
    {
        Http::fake();

        $this->postJson('/api/places/search', [
            'text' => 'Café',
            'latitude' => 51.584,
            'longitude' => 6.252,
            'radius' => 26000,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('radius');

        $this->postJson('/api/places/search', [
            'text' => 'Café',
            'latitude' => 91,
            'longitude' => 6.252,
            'radius' => 500,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');

        Http::assertNothingSent();
    }
}
