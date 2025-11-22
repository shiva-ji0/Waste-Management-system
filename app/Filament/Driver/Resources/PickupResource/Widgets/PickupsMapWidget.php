<?php

namespace App\Filament\Driver\Resources\PickupResource\Widgets;


use App\Models\Waste;
use App\Services\RouteOptimizerService;
use Filament\Widgets\Widget;

class PickupsMapWidget extends Widget
{
    protected static string $view = 'filament.resources.pickup-resource.widgets.pickups-map-widget';

    protected int | string | array $columnSpan = 'full';

    // Change this from -1 to a positive number to move it below the table
    protected static ?int $sort = 10;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return true;
    }

    /**
     * Get only accepted and re-scheduled pickups (NO PENDING!)
     */
    public function getPickups()
    {
        return Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('status', ['accepted', 're-scheduled'])
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

        return $optimizer->getOptimizedRoute($pickups);
    }
}
