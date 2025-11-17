<?php

namespace App\Http\Controllers;

use App\Models\Waste;
use App\Services\RouteOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RouteOptimizationController extends Controller
{
    public function __construct(
        private RouteOptimizerService $optimizer
    ) {}

    /**
     * Optimize route from a start location.
     * Only consider wastes with status 'accepted' or 're-scheduled'.
     */
    public function optimizeFromLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lon' => 'required|numeric|between:-180,180',
            'shift' => 'nullable|string|in:morning,afternoon,evening',
            'date' => 'nullable|date',
        ]);

        // Only accept accepted and re-scheduled wastes (no 'pending')
        $query = Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('status', ['accepted', 're-scheduled'])
            ->with('user');

        if (isset($validated['shift'])) {
            $query->where('shift', $validated['shift']);
        }

        if (isset($validated['date'])) {
            $query->whereDate('date', $validated['date']);
        }

        $pickups = $query->get()->map(function ($waste) use ($validated) {
            // Calculate distance from start point (cast coords to float)
            $distance = $this->optimizer->haversineDistance(
                (float) $validated['start_lat'],
                (float) $validated['start_lon'],
                (float) $waste->latitude,
                (float) $waste->longitude
            );

            return [
                'id' => $waste->id,
                'latitude' => (float) $waste->latitude,
                'longitude' => (float) $waste->longitude,
                'waste_type' => $waste->waste_type,
                'weight' => $waste->weight,
                'status' => $waste->status,
                'user_name' => $waste->user->name ?? 'N/A',
                'date' => $waste->date,
                'shift' => $waste->shift,
                'distance_from_start' => round($distance, 2), // Distance in km
            ];
        })->toArray();

        if (count($pickups) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'No accepted or re-scheduled pickups found',
                'route' => null
            ], 200);
        }

        $startPoint = [(float)$validated['start_lat'], (float)$validated['start_lon']];

        try {
            $optimizedRoute = $this->optimizer->getOptimizedRoute($pickups, $startPoint);

            // Add distance_from_start for route points that don't have it
            foreach ($optimizedRoute['route'] as &$point) {
                if (isset($point['distance_from_start'])) {
                    continue;
                }
                if (($point['id'] ?? null) !== 'start') {
                    $point['distance_from_start'] = round($this->optimizer->haversineDistance(
                        $startPoint[0],
                        $startPoint[1],
                        (float)$point['latitude'],
                        (float)$point['longitude']
                    ), 2);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Route optimized successfully',
                'route' => $optimizedRoute,
                'start_location' => [
                    'latitude' => $startPoint[0],
                    'longitude' => $startPoint[1]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error optimizing route: ' . $e->getMessage(),
                'route' => null
            ], 500);
        }
    }

    /**
     * Get pending pickups with optional filters.
     * Note: "pending" is intentionally excluded from the returned set per requirement;
     * only 'accepted' and 're-scheduled' records are returned.
     */
    public function getPickups(Request $request): JsonResponse
    {
        $query = Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('status', ['accepted', 're-scheduled'])
            ->with('user');

        if ($request->has('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        // If start location is provided, calculate distances
        $startLat = $request->input('start_lat');
        $startLon = $request->input('start_lon');

        $pickups = $query->get()->map(function ($waste) use ($startLat, $startLon) {
            $data = [
                'id' => $waste->id,
                'latitude' => (float) $waste->latitude,
                'longitude' => (float) $waste->longitude,
                'waste_type' => $waste->waste_type,
                'weight' => $waste->weight,
                'status' => $waste->status,
                'user_name' => $waste->user->name ?? 'N/A',
                'date' => $waste->date,
                'shift' => $waste->shift,
            ];

            // Add distance if start location provided and valid
            if ($startLat !== null && $startLon !== null) {
                $data['distance'] = round($this->optimizer->haversineDistance(
                    (float) $startLat,
                    (float) $startLon,
                    (float) $waste->latitude,
                    (float) $waste->longitude
                ), 2);
            }

            return $data;
        });

        // Sort by distance if available
        if ($startLat !== null && $startLon !== null) {
            $pickups = $pickups->sortBy('distance')->values();
        }

        return response()->json([
            'success' => true,
            'data' => $pickups,
            'total' => $pickups->count()
        ]);
    }

    public function calculateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat1' => 'required|numeric|between:-90,90',
            'lon1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lon2' => 'required|numeric|between:-180,180',
        ]);

        $distance = $this->optimizer->haversineDistance(
            (float) $validated['lat1'],
            (float) $validated['lon1'],
            (float) $validated['lat2'],
            (float) $validated['lon2']
        );

        return response()->json([
            'success' => true,
            'distance_km' => round($distance, 2),
            'distance_meters' => round($distance * 1000, 0)
        ]);
    }
}
