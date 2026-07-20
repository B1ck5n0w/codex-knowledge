<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'coordinates' => ['required', 'array', 'min:2', 'max:50'],
            'coordinates.*' => ['array', 'size:2'],
            'coordinates.*.0' => ['numeric', 'between:-180,180'],
            'coordinates.*.1' => ['numeric', 'between:-90,90'],
            'avoid_features' => ['sometimes', 'array'],
            'preference' => ['sometimes', 'in:recommended,fastest'],
            'alternative_routes' => ['sometimes', 'boolean'],
        ]);

        $key = config('services.openrouteservice.key');
        if (blank($key)) {
            return response()->json(['message' => 'OpenRouteService ist noch nicht serverseitig konfiguriert.'], 503);
        }

        $payload = [
            'coordinates' => $data['coordinates'],
            'preference' => $data['preference'] ?? 'recommended',
            'options' => ['avoid_features' => $data['avoid_features'] ?? []],
            'extra_info' => ['surface', 'waytype', 'steepness'],
        ];

        if ($data['alternative_routes'] ?? false) {
            $payload['alternative_routes'] = [
                'target_count' => 2,
                'share_factor' => 0.6,
                'weight_factor' => 1.6,
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => $key,
            // The /geojson endpoint requires that GeoJSON is an accepted response type.
            'Accept' => 'application/json, application/geo+json, application/gpx+xml; charset=utf-8',
        ])
            ->timeout(25)
            ->post(config('services.openrouteservice.url'), $payload);

        return response()->json($response->json(), $response->status());
    }
}
