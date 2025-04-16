<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\{Order};
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;

/**
 * Helper class for Google Maps distance and time calculations
 */
class MapsHelper
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Validate coordinate array structure
     */
    private function isValidCoordinates(?array $coordinates): bool
    {
        return isset($coordinates['lat']) && isset($coordinates['lng']) &&
            is_numeric($coordinates['lat']) && is_numeric($coordinates['lng']);
    }

    /**
     * Build Google Maps API URL
     */
    private function buildUrl(array $origin, array $destination, string $apiKey): string
    {
        return 'https://maps.googleapis.com/maps/api/directions/json?' .
            'origin=' . $destination['lat'] . ',' . $destination['lng'] .
            '&destination=' . $origin['lat'] . ',' . $origin['lng'] .
            '&key=' . $apiKey;
    }

    /**
     * Get distance and time between two points
     */
    public function getDistanceAndTime(array $origin, array $destination, string $apiKey): array
    {
        if (!$this->isValidCoordinates($origin) || !$this->isValidCoordinates($destination)) {
            return [
                'totalDistance' => 0,
                'averageTime' => 0,
            ];
        }

        $url = $this->buildUrl($origin, $destination, $apiKey);
        $promise = $this->client->getAsync($url);
        $response = $promise->wait();
        $data = json_decode((string) $response->getBody(), true);

        if ($data['status'] === 'OK') {
            return [
                'totalDistance' => $data['routes'][0]['legs'][0]['distance']['value'],
                'averageTime' => $data['routes'][0]['legs'][0]['duration']['value'],
            ];
        }

        return [
            'totalDistance' => 0,
            'averageTime' => 0,
        ];
    }

    /**
     * Calculate distance and time for an order and its child orders
     */
    public function calculateDistanceAndTime(int $orderId): void
    {
        $parentOrder = Order::with(['warehouse', 'childOrders' => function ($query) {
            $query->join('disp', 'id', '=', 'disp.oid')
                ->orderBy('num', 'ASC');
        }])->findOrFail($orderId);

        $apiKey = config('services.google.maps_api_key');
        $warehouse = $parentOrder->warehouse;
        $promises = [];

        // Calculate warehouse to parent order distance
        if ($this->hasValidCoordinates($warehouse, $parentOrder)) {
            $promises[] = $this->calculateWarehouseToOrderDistanceAsync($warehouse, $parentOrder, $apiKey);
        }

        // Calculate distances between orders asynchronously
        $promises = array_merge($promises, $this->calculateOrderChainDistancesAsync($parentOrder, $warehouse, $apiKey));

        // Wait for all promises to complete
        Utils::settle($promises)->wait();
    }

    /**
     * Check if both locations have valid coordinates
     */
    private function hasValidCoordinates($location1, $location2): bool
    {
        return $location1->latitude !== null &&
            $location1->longitude !== null &&
            $location2->lat !== null &&
            $location2->lng !== null;
    }

    /**
     * Calculate distance between warehouse and order
     */
    private function calculateWarehouseToOrderDistanceAsync($warehouse, $order, string $apiKey): PromiseInterface
    {
        $destination = [
            'lat' => floatval($warehouse->latitude),
            'lng' => floatval($warehouse->longitude)
        ];
        $origin = [
            'lat' => floatval($order->lat),
            'lng' => floatval($order->lng)
        ];

        $url = $this->buildUrl($origin, $destination, $apiKey);
        return $this->client->getAsync($url)->then(
            function ($response) use ($order) {
                $data = json_decode((string) $response->getBody(), true);
                if ($data['status'] === 'OK') {
                    $order->update([
                        'total_distance' => $data['routes'][0]['legs'][0]['distance']['value'],
                        'total_duration' => $data['routes'][0]['legs'][0]['duration']['value']
                    ]);
                }
            }
        );
    }

    /**
     * Calculate distances between orders in chain
     */
    private function calculateOrderChainDistancesAsync(Order $parentOrder, $warehouse, string $apiKey): array
    {
        $promises = [];
        $previousOrder = $parentOrder;
        $childOrders = $parentOrder->childOrders;

        foreach ($childOrders as $key => $order) {
            if ($order->lat === null || $order->lng === null) {
                continue;
            }

            $promises[] = $this->calculateDistanceBetweenOrdersAsync($previousOrder, $order, $apiKey);

            // Calculate return distance for last order
            if ($key === $childOrders->count() - 1) {
                $promises[] = $this->calculateReturnDistanceAsync($order, $warehouse, $apiKey);
            }

            $previousOrder = $order;
        }

        return $promises;
    }

    /**
     * Calculate distance between two orders
     */
    private function calculateDistanceBetweenOrdersAsync($fromOrder, $toOrder, string $apiKey): PromiseInterface
    {
        $origin = [
            'lat' => floatval($fromOrder->lat),
            'lng' => floatval($fromOrder->lng)
        ];
        $destination = [
            'lat' => floatval($toOrder->lat),
            'lng' => floatval($toOrder->lng)
        ];

        $url = $this->buildUrl($origin, $destination, $apiKey);
        return $this->client->getAsync($url)->then(
            function ($response) use ($toOrder) {
                $data = json_decode((string) $response->getBody(), true);
                if ($data['status'] === 'OK') {
                    $toOrder->update([
                        'total_distance' => $data['routes'][0]['legs'][0]['distance']['value'],
                        'total_duration' => $data['routes'][0]['legs'][0]['duration']['value']
                    ]);
                }
            }
        );
    }

    /**
     * Calculate return distance to warehouse
     */
    private function calculateReturnDistanceAsync($order, $warehouse, string $apiKey): PromiseInterface
    {
        $origin = [
            'lat' => floatval($order->lat),
            'lng' => floatval($order->lng)
        ];
        $destination = [
            'lat' => floatval($warehouse->latitude),
            'lng' => floatval($warehouse->longitude)
        ];

        $url = $this->buildUrl($origin, $destination, $apiKey);
        return $this->client->getAsync($url)->then(
            function ($response) use ($order) {
                $data = json_decode((string) $response->getBody(), true);
                if ($data['status'] === 'OK') {
                    $order->update([
                        'return_distance' => $data['routes'][0]['legs'][0]['distance']['value'],
                        'return_duration' => $data['routes'][0]['legs'][0]['duration']['value']
                    ]);
                }
            }
        );
    }

    public function getDistanceAndTimeWithWaypoints(
        array $origin,
        array $destination,
        ?string $waypoints,
        string $mode,
        string $apiKey
    ): array {
        $originStr = "{$origin['lat']},{$origin['lng']}";
        $destinationStr = "{$destination['lat']},{$destination['lng']}";

        $url = "https://maps.googleapis.com/maps/api/directions/json?"
            . "origin=" . urlencode($originStr)
            . "&destination=" . urlencode($destinationStr)
            . "&mode=" . urlencode($mode)
            . "&overview=full"
            . "&key=" . urlencode($apiKey);

        if ($waypoints) {
            $url .= "&waypoints=" . urlencode($waypoints);
        }

        try {
            $response = $this->client->get($url);
            $data = json_decode((string) $response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['routes'])) {
                return [
                    'routes' => array_map(function ($route) {
                        return [
                            'legs' => array_map(function ($leg) {
                                return [
                                    'distance' => $leg['distance']['value'],
                                    'duration' => $leg['duration']['value']
                                ];
                            }, $route['legs']),
                            'overview_polyline' => [
                                'points' => $route['overview_polyline']['points']
                            ],
                            'waypoint_order' => $route['waypoint_order'] ?? []
                        ];
                    }, $data['routes']),
                    'status' => $data['status']
                ];
            }

            return [
                'routes' => [],
                'status' => $data['status'] ?? 'UNKNOWN_ERROR'
            ];
        } catch (\Exception $e) {
            return [
                'routes' => [],
                'status' => 'REQUEST_FAILED'
            ];
        }
    }

    /**
     * Decode Google Maps encoded polyline points into array of coordinates
     *
     * @param string $encodedString
     * @return array Array of [lat, lng] coordinates
     */
    public function decodePolyline(string $encodedString): array
    {
        $points = [];
        $index = $i = 0;
        $previousLat = $previousLng = 0;
        $len = strlen($encodedString);

        while ($i < $len) {
            $shift = $result = 0;

            do {
                $bit = ord($encodedString[$i]) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
                $i++;
            } while ($bit >= 0x20);

            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $previousLat += $dlat;

            $shift = $result = 0;

            do {
                $bit = ord($encodedString[$i]) - 63;
                $result |= ($bit & 0x1f) << $shift;
                $shift += 5;
                $i++;
            } while ($bit >= 0x20);

            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $previousLng += $dlng;

            $points[] = [
                'lat' => $previousLat * 1e-5,
                'lng' => $previousLng * 1e-5
            ];
        }

        return $points;
    }

    /**
     * Encode coordinates array back to Google Maps polyline format
     *
     * @param array $coordinates Array of [lat, lng] coordinates
     * @return string Encoded polyline string
     */
    public function encodePolyline(array $coordinates): string
    {
        $points = array_map(function ($coord) {
            return [
                (int)round($coord['lat'] * 1e5),
                (int)round($coord['lng'] * 1e5)
            ];
        }, $coordinates);

        $encodedString = '';
        $previousLat = 0;
        $previousLng = 0;

        foreach ($points as $point) {
            $encodedString .= $this->encodeValue($point[0] - $previousLat);
            $encodedString .= $this->encodeValue($point[1] - $previousLng);
            $previousLat = $point[0];
            $previousLng = $point[1];
        }

        return $encodedString;
    }

    /**
     * Helper method to encode a single value for polyline
     */
    private function encodeValue(int $value): string
    {
        $value = $value < 0 ? ~($value << 1) : ($value << 1);
        $encoded = '';

        while ($value >= 0x20) {
            $encoded .= chr((0x20 | ($value & 0x1f)) + 63);
            $value >>= 5;
        }

        $encoded .= chr($value + 63);
        return $encoded;
    }

    /**
     * Get route polyline from OSRM service
     *
     * @param array $origin ['lat' => float, 'lng' => float]
     * @param array $destination ['lat' => float, 'lng' => float]
     * @return array|null ['points' => string, 'coordinates' => array]
     */
    public function getOSRMPolyline(array $origin, array $destination): ?array
    {
        try {
            // Validate coordinates are within reasonable bounds
            if (
                !$this->isValidLatLng($origin['lat'], $origin['lng']) ||
                !$this->isValidLatLng($destination['lat'], $destination['lng'])
            ) {
                return null;
            }

            $url = sprintf(
                'https://router.project-osrm.org/route/v1/driving/%f,%f;%f,%f?overview=full&geometries=polyline',
                $origin['lng'],
                $origin['lat'],
                $destination['lng'],
                $destination['lat']
            );

            $response = $this->client->get($url);
            $data = json_decode((string) $response->getBody(), true);

            if (isset($data['code']) && $data['code'] === 'Ok' && !empty($data['routes'][0]['geometry'])) {
                $points = $data['routes'][0]['geometry'];
                $coordinates = $this->decodePolyline($points);

                return [
                    'points' => $points,
                    'coordinates' => $coordinates
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if latitude and longitude are within valid ranges
     */
    private function isValidLatLng(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Calculate distance between two coordinates using OSRM with Haversine fallback
     * 
     * @param float $lat1 Latitude of the first point
     * @param float $lon1 Longitude of the first point
     * @param float $lat2 Latitude of the second point
     * @param float $lon2 Longitude of the second point
     * @return float Distance in kilometers
     */
    public function calculateHaversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {

        // Fallback to Haversine calculation
        return $this->calculateHaversineFallback($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Get road distance using OSRM service
     */
    private function getOSRMDistance(array $origin, array $destination): ?float
    {
        if (
            !$this->isValidLatLng($origin['lat'], $origin['lng']) ||
            !$this->isValidLatLng($destination['lat'], $destination['lng'])
        ) {
            return null;
        }

        try {
            $url = sprintf(
                'https://router.project-osrm.org/route/v1/driving/%f,%f;%f,%f?overview=false',
                $origin['lng'],
                $origin['lat'],
                $destination['lng'],
                $destination['lat']
            );

            $response = $this->client->get($url, ['timeout' => 2]);
            $data = json_decode((string)$response->getBody(), true);

            if (isset($data['code']) && $data['code'] === 'Ok' && !empty($data['routes'][0]['distance'])) {
                return round($data['routes'][0]['distance'] / 1000, 3); // Convert meters to km
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("OSRM request failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Pure Haversine formula implementation (fallback)
     */
    private function calculateHaversineFallback(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
