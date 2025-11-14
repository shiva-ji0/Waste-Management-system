<?php

namespace App\Filament\Resources\WasteResource\Pages;

use App\Filament\Resources\WasteResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\WasteStatusMail;

class EditWaste extends EditRecord
{
    protected static string $resource = WasteResource::class;

    protected function afterSave(): void
    {
        // Send mail only if status is accepted, rejected, or re-scheduled
        if (in_array($this->record->status, ['accepted', 'rejected', 're-scheduled'])) {
            Mail::to($this->record->user->email)->send(new WasteStatusMail($this->record));

            Notification::make()
                ->title('Status Updated')
                ->body("Status updated to '{$this->record->status}', mail sent to {$this->record->user->email}.")
                ->success()
                ->send();
        }
    }
}
