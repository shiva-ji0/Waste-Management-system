<?php

namespace App\Filament\Driver\Resources\PickupResource\Pages;

use App\Filament\Driver\Resources\PickupResource;
use App\Models\Waste;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPickups extends ListRecords
{

    protected static string $resource = \App\Filament\Driver\Resources\PickupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PickupResource\Widgets\PickupsMapWidget::class
        ];
    }

    public function getPickups(): array
    {
        return Waste::query()
            ->whereIn('status', ['accepted', 're-scheduled'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('user')
            ->get()
            ->map(function ($pickup) {
                return [
                    'id' => $pickup->id,
                    'latitude' => (float) $pickup->latitude,
                    'longitude' => (float) $pickup->longitude,
                    'waste_type' => $pickup->waste_type,
                    'weight' => $pickup->weight,
                    'user_name' => $pickup->user->name ?? 'Unknown',
                    'status' => $pickup->status,
                    'date' => $pickup->date->format('Y-m-d'),
                    'shift' => $pickup->shift,
                ];
            })
            ->toArray();
    }
}
