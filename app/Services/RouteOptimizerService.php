<?php

namespace App\Services;

class RouteOptimizerService
{
    /**
     * Calculate Haversine distance between two coordinates in kilometers
     */
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
     * Build Minimum Spanning Tree using Prim's Algorithm with Haversine distance
     * 
     * @param array $pickups Array of pickups with latitude and longitude
     * @param array|null $startPoint Starting point [lat, lon], uses first pickup if null
     * @return array ['mst' => edges, 'totalDistance' => float, 'route' => ordered points]
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

        // Start from first node (index 0)
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
     * Get optimized route with turn-by-turn directions
     */
    public function getOptimizedRoute(array $pickups, ?array $startPoint = null): array
    {
        $result = $this->buildMinimumSpanningTree($pickups, $startPoint);
        
        // Add turn-by-turn directions
        $directions = [];
        $route = $result['route'];
        
        for ($i = 0; $i < count($route) - 1; $i++) {
            $from = $route[$i];
            $to = $route[$i + 1];
            
            $distance = $this->haversineDistance(
                $from['latitude'],
                $from['longitude'],
                $to['latitude'],
                $to['longitude']
            );
            
            $bearing = $this->calculateBearing(
                $from['latitude'],
                $from['longitude'],
                $to['latitude'],
                $to['longitude']
            );
            
            $directions[] = [
                'from' => $from,
                'to' => $to,
                'distance' => round($distance, 2),
                'bearing' => $bearing,
                'direction' => $this->bearingToDirection($bearing),
                'step' => $i + 1
            ];
        }
        
        return [
            'route' => $route,
            'mst' => $result['mst'],
            'totalDistance' => $result['totalDistance'],
            'directions' => $directions,
            'estimatedTime' => $this->estimateTime($result['totalDistance'])
        ];
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
}