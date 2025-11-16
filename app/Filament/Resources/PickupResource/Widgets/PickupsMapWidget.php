<?php

namespace App\Filament\Resources\PickupResource\Widgets;

use App\Models\Waste;
use App\Services\RouteOptimizerService;
use Filament\Widgets\Widget;

class PickupsMapWidget extends Widget
{
    protected static string $view = 'filament.resources.pickup-resource.widgets.pickups-map-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1;
    
    protected static bool $isLazy = false;
    
    public static function canView(): bool
    {
        return true;
    }

    public function getPickups()
    {
        return Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('status', 'pending') // Only show pending pickups for route optimization
            ->with('user')
            ->get()
            ->map(function ($waste) {
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
    }

    public function getOptimizedRoute()
    {
        $pickups = $this->getPickups();
        
        if (count($pickups) < 2) {
            return null;
        }

        $optimizer = new RouteOptimizerService();
        
        // You can set a starting point (e.g., depot location)
        // $startPoint = [27.7172, 85.3240]; // Kathmandu center
        // return $optimizer->getOptimizedRoute($pickups, $startPoint);
        
        return $optimizer->getOptimizedRoute($pickups);
    }
}