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
}
