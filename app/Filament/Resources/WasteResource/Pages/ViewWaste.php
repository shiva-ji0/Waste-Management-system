<?php

namespace App\Filament\Resources\WasteResource\Pages;

use App\Filament\Resources\WasteResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewWaste extends ViewRecord
{
    protected static string $resource = WasteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),      // Shows the Edit button
            Actions\DeleteAction::make(),    // Shows the Delete button
            
        ];
    }
}
