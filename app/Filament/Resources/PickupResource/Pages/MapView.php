<?php

namespace App\Filament\Resources\PickupResource\Pages;

use App\Filament\Resources\PickupResource;
use App\Models\Waste;
use Filament\Resources\Pages\Page;

class MapView extends Page
{
    protected static string $resource = PickupResource::class;

    protected static string $view = 'filament.resources.pickup-resource.pages.map-view';

    protected static ?string $title = 'Pickups Map';

    protected static ?string $navigationLabel = 'Map View';

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
                    'date' => $waste->date?->format('Y-m-d'),
                    'shift' => $waste->shift,
                ];
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to List')
                ->icon('heroicon-o-arrow-left')
                ->url(PickupResource::getUrl('index')),
        ];
    }
}
