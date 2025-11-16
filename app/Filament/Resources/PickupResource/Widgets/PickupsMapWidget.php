<?php

namespace App\Filament\Resources\PickupResource\Widgets;

use App\Models\Waste;
use Filament\Widgets\Widget;

class PickupsMapWidget extends Widget
{
    protected static string $view = 'filament.resources.pickup-resource.widgets.pickups-map-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1;
    
    // Add these properties to fix the error
    protected static bool $isLazy = false;
    
    public static function canView(): bool
    {
        return true;
    }

    public function getPickups()
    {
        return Waste::whereNotNull('latitude')
            ->whereNotNull('longitude')
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
}