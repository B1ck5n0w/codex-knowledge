<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteControllerTest extends TestCase
{
    public function test_it_forwards_the_requested_routing_preference_to_open_route_service(): void
    {
        config()->set('services.openrouteservice.key', 'test-key');
        config()->set('services.openrouteservice.url', 'https://routing.test/geojson');

        Http::fake([
            'https://routing.test/geojson' => Http::response([
                'features' => [[
                    'geometry' => ['coordinates' => [[6.25, 51.58], [6.18, 51.47]]],
                    'properties' => ['summary' => ['distance' => 15000, 'duration' => 3000]],
                ]],
            ]),
        ]);

        $this->postJson('/api/route', [
            'coordinates' => [[6.25, 51.58], [6.18, 51.47]],
            'preference' => 'fastest',
            'avoid_features' => ['steps'],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $body['preference'] === 'fastest'
                && $body['options']['avoid_features'] === ['steps'];
        });
    }

    public function test_it_requests_alternatives_only_when_the_return_route_needs_them(): void
    {
        config()->set('services.openrouteservice.key', 'test-key');
        config()->set('services.openrouteservice.url', 'https://routing.test/geojson');

        Http::fake([
            'https://routing.test/geojson' => Http::response(['features' => []]),
        ]);

        $this->postJson('/api/route', [
            'coordinates' => [[6.25, 51.58], [6.18, 51.47]],
            'preference' => 'recommended',
            'alternative_routes' => true,
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $body['alternative_routes'] === [
                'target_count' => 2,
                'share_factor' => 0.6,
                'weight_factor' => 1.6,
            ];
        });
    }

    public function test_invalid_route_coordinates_are_rejected_before_calling_the_routing_service(): void
    {
        config()->set('services.openrouteservice.key', 'test-key');
        Http::fake();

        $this->postJson('/api/route', [
            'coordinates' => [[6.25, 51.58]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('coordinates');

        Http::assertNothingSent();
    }
}
