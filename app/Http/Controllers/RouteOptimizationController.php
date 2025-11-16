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

 
    public function optimizeFromLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lon' => 'required|numeric|between:-180,180',
            'shift' => 'nullable|string|in:morning,afternoon,evening',
            'date' => 'nullable|date',
        ]);

        // Build query for pickups
        $query = Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'pending')
            ->with('user');

        // Apply optional filters
        if (isset($validated['shift'])) {
            $query->where('shift', $validated['shift']);
        }

        if (isset($validated['date'])) {
            $query->whereDate('date', $validated['date']);
        }

        // Get pickups
        $pickups = $query->get()->map(function ($waste) {
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
            ];
        })->toArray();

        if (count($pickups) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'No pending pickups found',
                'route' => null
            ], 200);
        }

        // Optimize route from user's current location
        $startPoint = [$validated['start_lat'], $validated['start_lon']];
        
        try {
            $optimizedRoute = $this->optimizer->getOptimizedRoute($pickups, $startPoint);

            return response()->json([
                'success' => true,
                'message' => 'Route optimized successfully',
                'route' => $optimizedRoute
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
     * Get all pending pickups without optimization
     */
    public function getPendingPickups(Request $request): JsonResponse
    {
        $query = Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'pending')
            ->with('user');

        // Optional filters
        if ($request->has('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        $pickups = $query->get()->map(function ($waste) {
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
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $pickups
        ]);
    }
}