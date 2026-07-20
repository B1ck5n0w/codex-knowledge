<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PlaceController extends Controller
{
    private function placesUsage(): array
    {
        $date = now()->toDateString();
        $limit = max(0, (int) config('services.google.places_daily_limit', 25));
        $used = (int) Cache::get("google-places:{$date}:used", 0);

        return [
            'date' => $date,
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'blocked' => $limit === 0 || $used >= $limit,
        ];
    }

    public function usage(): JsonResponse
    {
        return response()->json($this->placesUsage());
    }

    private function reservePlacesRequest(): ?JsonResponse
    {
        $usage = $this->placesUsage();
        if ($usage['blocked']) {
            return response()->json([
                'message' => 'Google-Places-Tageslimit erreicht. Es werden keine weiteren kostenpflichtigen Abfragen ausgelöst.',
                'usage' => $usage,
            ], 429);
        }

        Cache::increment("google-places:{$usage['date']}:used");
        Cache::put("google-places:{$usage['date']}:used", (int) Cache::get("google-places:{$usage['date']}:used"), now()->endOfDay());

        return null;
    }

    private function distanceInMeters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $earthRadius = 6371000;
        $latitudeDelta = deg2rad($latitudeB - $latitudeA);
        $longitudeDelta = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function overpass(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:12000'],
        ]);

        $query = $data['query'];

        if (! str_starts_with($query, '[out:json]')) {
            return response()->json(['message' => 'Ungültige Kartenabfrage.'], 422);
        }

        $lastError = null;
        foreach ([
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
        ] as $endpoint) {
            try {
                $response = Http::asForm()->acceptJson()->timeout(8)->post($endpoint, [
                    'data' => $query,
                ]);

                if ($response->successful()) {
                    return response()->json([
                        'elements' => $response->json('elements', []),
                    ]);
                }

                $lastError = 'HTTP '.$response->status();
            } catch (\Throwable $exception) {
                $lastError = $exception->getMessage();
            }
        }

        return response()->json([
            'message' => 'Die OpenStreetMap-Suche ist gerade nicht erreichbar. Bitte versuche es in einem Moment erneut.',
        ], 503);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:180'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'integer', 'min:100', 'max:25000'],
        ]);

        $key = config('services.google.places_key');
        if (blank($key)) {
            return response()->json(['message' => 'Google Places ist noch nicht serverseitig konfiguriert.'], 503);
        }

        $radius = $data['radius'] ?? 5000;
        $cacheKey = 'google-places:search:'.sha1(implode('|', [
            mb_strtolower(trim($data['text'])),
            round((float) $data['latitude'], 4),
            round((float) $data['longitude'], 4),
            $radius,
        ]));
        if ($cached = Cache::get($cacheKey)) {
            return response()->json([
                ...$cached,
                'usage' => $this->placesUsage(),
                'cached' => true,
            ]);
        }

        if ($blocked = $this->reservePlacesRequest()) {
            return $blocked;
        }

        // Text Search's location bias can return places outside the chosen area.
        // Use a hard rectangular restriction (plus the exact circular filter below)
        // so target suggestions never escape the user-selected search radius.
        $latitudeDelta = $radius / 111320;
        $longitudeDelta = $radius / max(1, 111320 * cos(deg2rad((float) $data['latitude'])));

        $response = Http::acceptJson()
            ->withHeaders([
                'X-Goog-Api-Key' => $key,
                // Keep the discovery request deliberately lean. Rich details and photos
                // are fetched only after the user opens a specific place.
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.primaryType',
            ])
            ->timeout(15)
            ->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $data['text'],
                // Eight results are enough for a decision list. No pagination is requested.
                'pageSize' => 8,
                'locationRestriction' => [
                    'rectangle' => [
                        'low' => [
                            'latitude' => (float) $data['latitude'] - $latitudeDelta,
                            'longitude' => (float) $data['longitude'] - $longitudeDelta,
                        ],
                        'high' => [
                            'latitude' => (float) $data['latitude'] + $latitudeDelta,
                            'longitude' => (float) $data['longitude'] + $longitudeDelta,
                        ],
                    ],
                ],
                'languageCode' => 'de',
            ]);

        $payload = $response->json();
        if ($response->successful()) {
            // A location bias is not a hard boundary. Filter again server-side so the
            // interface never presents a place outside the user-selected radius.
            $payload['places'] = array_values(array_filter($payload['places'] ?? [], function (array $place) use ($data, $radius): bool {
                $location = $place['location'] ?? [];
                if (! isset($location['latitude'], $location['longitude'])) {
                    return false;
                }

                return $this->distanceInMeters(
                    (float) $data['latitude'],
                    (float) $data['longitude'],
                    (float) $location['latitude'],
                    (float) $location['longitude'],
                ) <= $radius;
            }));
            Cache::put($cacheKey, $payload, now()->addMinutes(max(1, (int) config('services.google.places_cache_minutes', 360))));
        }

        return response()->json([
            ...$payload,
            'usage' => $this->placesUsage(),
            'cached' => false,
        ], $response->status());
    }

    public function details(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{10,}$/']]);
        $key = config('services.google.places_key');
        if (blank($key)) {
            return response()->json(['message' => 'Google Places ist noch nicht serverseitig konfiguriert.'], 503);
        }

        $cacheKey = 'google-places:details:'.$data['id'];
        if ($cached = Cache::get($cacheKey)) {
            return response()->json([
                ...$cached,
                'usage' => $this->placesUsage(),
                'cached' => true,
            ]);
        }

        if ($blocked = $this->reservePlacesRequest()) {
            return $blocked;
        }

        $response = Http::acceptJson()->withHeaders([
            'X-Goog-Api-Key' => $key,
            'X-Goog-FieldMask' => 'displayName,formattedAddress,regularOpeningHours,websiteUri,rating,userRatingCount,photos',
        ])->timeout(15)->get('https://places.googleapis.com/v1/places/'.$data['id']);

        $payload = $response->json();
        if ($response->successful()) {
            // Details change relatively rarely. Caching avoids repeat lookups when a
            // place card or dialog is opened more than once during planning.
            Cache::put($cacheKey, $payload, now()->addMinutes(max(1, (int) config('services.google.places_cache_minutes', 360))));
        }

        return response()->json([...$payload, 'usage' => $this->placesUsage(), 'cached' => false], $response->status());
    }

    public function photo(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:2000', 'regex:/^places\/[A-Za-z0-9_-]+\/photos\/[A-Za-z0-9_-]+$/']]);
        $key = config('services.google.places_key');
        if (blank($key)) {
            return response()->json(['message' => 'Google Places ist noch nicht serverseitig konfiguriert.'], 503);
        }

        $cacheKey = 'google-places:photo-uri:'.sha1($data['name']);
        if ($cached = Cache::get($cacheKey)) {
            return response()->json([
                ...$cached,
                'usage' => $this->placesUsage(),
                'cached' => true,
            ]);
        }

        if ($blocked = $this->reservePlacesRequest()) {
            return $blocked;
        }

        $response = Http::acceptJson()->withHeaders(['X-Goog-Api-Key' => $key])->timeout(15)
            ->get('https://places.googleapis.com/v1/'.$data['name'].'/media', ['maxHeightPx' => 480, 'skipHttpRedirect' => 'true']);

        $payload = $response->json();
        if ($response->successful()) {
            // Only the short-lived Google media URI is cached, never the image file.
            // This keeps repeated carousel openings cheap without retaining imagery.
            Cache::put($cacheKey, $payload, now()->addMinutes(30));
        }

        return response()->json([...$payload, 'usage' => $this->placesUsage(), 'cached' => false], $response->status());
    }
}
