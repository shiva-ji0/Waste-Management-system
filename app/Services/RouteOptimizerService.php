<?php

namespace App\Services;

class RouteOptimizerService
{

    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Optimize route using Nearest Neighbor Algorithm (Greedy Approach)
     * Always go to the closest unvisited location next
     *
     * @param array $pickups Array of pickups with latitude and longitude
     * @param array|null $startPoint Starting point [lat, lon], uses first pickup if null
     * @return array ['route' => ordered points, 'totalDistance' => float, 'directions' => array]
     */
    public function optimizeRouteNearestNeighbor(array $pickups, ?array $startPoint = null): array
    {
        $n = count($pickups);

        if ($n === 0) {
            return ['route' => [], 'totalDistance' => 0, 'directions' => [], 'estimatedTime' => '0 minutes'];
        }

        if ($n === 1) {
            $distance = 0;
            if ($startPoint) {
                $distance = $this->haversineDistance(
                    $startPoint[0], $startPoint[1],
                    $pickups[0]['latitude'], $pickups[0]['longitude']
                );
            }

            return [
                'route' => $startPoint ? [
                    [
                        'id' => 'start',
                        'latitude' => $startPoint[0],
                        'longitude' => $startPoint[1],
                        'is_start' => true
                    ],
                    $pickups[0]
                ] : [$pickups[0]],
                'totalDistance' => round($distance, 2),
                'directions' => [],
                'estimatedTime' => $this->estimateTime($distance)
            ];
        }

        // Determine starting point
        if ($startPoint) {
            $currentLocation = [
                'id' => 'start',
                'latitude' => $startPoint[0],
                'longitude' => $startPoint[1],
                'is_start' => true
            ];
        } else {
            $currentLocation = $pickups[0];
            array_shift($pickups);
        }

        // Initialize route and tracking
        $route = [$currentLocation];
        $unvisited = $pickups;
        $totalDistance = 0;
        $directions = [];

        // Greedy Nearest Neighbor: Always pick the closest unvisited location
        while (count($unvisited) > 0) {
            $nearestIndex = null;
            $shortestDistance = PHP_FLOAT_MAX;

            // Find the nearest unvisited pickup
            foreach ($unvisited as $index => $pickup) {
                $distance = $this->haversineDistance(
                    $currentLocation['latitude'],
                    $currentLocation['longitude'],
                    $pickup['latitude'],
                    $pickup['longitude']
                );

                if ($distance < $shortestDistance) {
                    $shortestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            // Move to the nearest pickup
            $nextLocation = $unvisited[$nearestIndex];
            $route[] = $nextLocation;
            $totalDistance += $shortestDistance;

            // Add direction
            $bearing = $this->calculateBearing(
                $currentLocation['latitude'],
                $currentLocation['longitude'],
                $nextLocation['latitude'],
                $nextLocation['longitude']
            );

            $directions[] = [
                'from' => $currentLocation,
                'to' => $nextLocation,
                'distance' => round($shortestDistance, 2),
                'bearing' => $bearing,
                'direction' => $this->bearingToDirection($bearing),
                'step' => count($directions) + 1
            ];

            // Remove visited pickup and update current location
            unset($unvisited[$nearestIndex]);
            $unvisited = array_values($unvisited); // Re-index array
            $currentLocation = $nextLocation;
        }

        return [
            'route' => $route,
            'totalDistance' => round($totalDistance, 2),
            'directions' => $directions,
            'estimatedTime' => $this->estimateTime($totalDistance)
        ];
    }

    /**
     * Get optimized route (using Nearest Neighbor by default)
     */
    public function getOptimizedRoute(array $pickups, ?array $startPoint = null): array
    {
        return $this->optimizeRouteNearestNeighbor($pickups, $startPoint);
    }

    /**
     * Build Minimum Spanning Tree using Prim's Algorithm (Alternative method)
     * Kept for comparison purposes
     */
    public function buildMinimumSpanningTree(array $pickups, ?array $startPoint = null): array
    {
        $n = count($pickups);

        if ($n === 0) {
            return ['mst' => [], 'totalDistance' => 0, 'route' => []];
        }

        if ($n === 1) {
            return [
                'mst' => [],
                'totalDistance' => 0,
                'route' => [$pickups[0]]
            ];
        }

        // If start point is provided, add it as the first node
        if ($startPoint) {
            array_unshift($pickups, [
                'id' => 'start',
                'latitude' => $startPoint[0],
                'longitude' => $startPoint[1],
                'is_start' => true
            ]);
            $n++;
        }

        // Build distance matrix using Haversine formula
        $distances = [];
        for ($i = 0; $i < $n; $i++) {
            $distances[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    $distances[$i][$j] = 0;
                } else {
                    $distances[$i][$j] = $this->haversineDistance(
                        $pickups[$i]['latitude'],
                        $pickups[$i]['longitude'],
                        $pickups[$j]['latitude'],
                        $pickups[$j]['longitude']
                    );
                }
            }
        }

        // Prim's Algorithm
        $parent = array_fill(0, $n, -1);
        $key = array_fill(0, $n, PHP_FLOAT_MAX);
        $visited = array_fill(0, $n, false);

        $key[0] = 0;
        $parent[0] = -1;

        for ($count = 0; $count < $n - 1; $count++) {
            $u = $this->findMinimumKey($key, $visited);

            if ($u === -1) break;

            $visited[$u] = true;

            for ($v = 0; $v < $n; $v++) {
                if ($distances[$u][$v] && !$visited[$v] && $distances[$u][$v] < $key[$v]) {
                    $parent[$v] = $u;
                    $key[$v] = $distances[$u][$v];
                }
            }
        }

        // Build MST edges
        $mst = [];
        $totalDistance = 0;

        for ($i = 1; $i < $n; $i++) {
            if ($parent[$i] !== -1) {
                $mst[] = [
                    'from' => $pickups[$parent[$i]],
                    'to' => $pickups[$i],
                    'distance' => $distances[$parent[$i]][$i]
                ];
                $totalDistance += $distances[$parent[$i]][$i];
            }
        }

        // Convert MST to ordered route using DFS
        $route = $this->convertMSTToRoute($mst, $pickups, 0);

        return [
            'mst' => $mst,
            'totalDistance' => round($totalDistance, 2),
            'route' => $route
        ];
    }

    /**
     * Find the nearest unvisited node
     */
    private function findMinimumKey(array $key, array $visited): int
    {
        $min = PHP_FLOAT_MAX;
        $minIndex = -1;

        foreach ($key as $index => $value) {
            if (!$visited[$index] && $value < $min) {
                $min = $value;
                $minIndex = $index;
            }
        }

        return $minIndex;
    }

    /**
     * Convert MST to ordered route using Depth-First Search
     */
    private function convertMSTToRoute(array $mst, array $pickups, int $startIndex): array
    {
        // Build adjacency list from MST
        $graph = [];
        foreach ($pickups as $index => $pickup) {
            $graph[$index] = [];
        }

        foreach ($mst as $edge) {
            $fromIndex = array_search($edge['from'], $pickups);
            $toIndex = array_search($edge['to'], $pickups);

            $graph[$fromIndex][] = $toIndex;
            $graph[$toIndex][] = $fromIndex;
        }

        // DFS to create route
        $visited = [];
        $route = [];
        $this->dfs($startIndex, $graph, $visited, $route, $pickups);

        return $route;
    }

    /**
     * Depth-First Search helper
     */
    private function dfs(int $node, array &$graph, array &$visited, array &$route, array $pickups): void
    {
        $visited[$node] = true;
        $route[] = $pickups[$node];

        foreach ($graph[$node] as $neighbor) {
            if (!isset($visited[$neighbor])) {
                $this->dfs($neighbor, $graph, $visited, $route, $pickups);
            }
        }
    }

    /**
     * Calculate bearing between two points
     */
    private function calculateBearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $lonDiff = deg2rad($lon2 - $lon1);

        $y = sin($lonDiff) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($lonDiff);

        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);

        return fmod($bearing + 360, 360);
    }

    /**
     * Convert bearing to cardinal direction
     */
    private function bearingToDirection(float $bearing): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        $index = round($bearing / 45) % 8;
        return $directions[$index];
    }

    /**
     * Estimate travel time based on distance (assuming average speed of 30 km/h in city)
     */
    private function estimateTime(float $distanceKm): string
    {
        $averageSpeed = 30; // km/h
        $hours = $distanceKm / $averageSpeed;
        $minutes = round($hours * 60);

        if ($minutes < 60) {
            return $minutes . ' minutes';
        } else {
            $hrs = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hrs . ' hour' . ($hrs > 1 ? 's' : '') . ($mins > 0 ? ' ' . $mins . ' minutes' : '');
        }
    }

    /**
     * Compare different routing algorithms
     */
    public function compareAlgorithms(array $pickups, ?array $startPoint = null): array
    {
        $nearestNeighbor = $this->optimizeRouteNearestNeighbor($pickups, $startPoint);
        $mst = $this->buildMinimumSpanningTree($pickups, $startPoint);

        return [
            'nearest_neighbor' => [
                'name' => 'Nearest Neighbor (Greedy)',
                'description' => 'Always go to closest unvisited location',
                'total_distance' => $nearestNeighbor['totalDistance'],
                'estimated_time' => $nearestNeighbor['estimatedTime'],
                'route' => $nearestNeighbor['route']
            ],
            'minimum_spanning_tree' => [
                'name' => 'Minimum Spanning Tree (Prim)',
                'description' => 'Optimal network connection between all points',
                'total_distance' => $mst['totalDistance'],
                'route' => $mst['route']
            ],
            'recommendation' => $nearestNeighbor['totalDistance'] <= $mst['totalDistance']
                ? 'nearest_neighbor'
                : 'minimum_spanning_tree'
        ];
    }
}
