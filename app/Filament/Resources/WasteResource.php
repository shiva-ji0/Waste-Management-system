<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WasteResource\Pages;
use App\Models\Waste;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class WasteResource extends Resource
{
    protected static ?string $model = Waste::class;
    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'Wastes';
    protected static ?string $pluralModelLabel = 'Wastes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('waste_type')
                ->label('Waste Type')
                ->options([
                    'recyclable' => 'Recyclable',
                    'non-recyclable' => 'Non-Recyclable',
                ])
                ->required(),

            Forms\Components\TextInput::make('user_id')
                ->label('User ID')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('weight')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\DatePicker::make('date')
                ->required()
                ->minDate(now()->toDateString()),

            Forms\Components\Select::make('shift')
                ->options([
                    '9AM-12PM' => '9AM - 12 PM',
                    '12PM-3PM' => '12 PM - 3 PM',
                    '3PM-6PM' => '3 PM - 6 PM',
                ])
                ->required(),

            Forms\Components\TextInput::make('latitude')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('longitude')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('address')
                ->required(),

            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'accepted' => 'Accepted',
                    'rejected' => 'Rejected',
                    're-scheduled' => 'Re-scheduled',
                    'completed' => 'Completed',
                ])
                ->default('pending')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $validStatuses = ['pending', 'accepted', 'rejected', 're-scheduled', 'completed'];

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('waste_type')->searchable(),
                Tables\Columns\TextColumn::make('weight'),
                Tables\Columns\TextColumn::make('date')->sortable(),
                Tables\Columns\TextColumn::make('shift'),
                Tables\Columns\TextColumn::make('address')->limit(20),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'accepted',
                        'danger'  => 'rejected',
                        'warning' => 're-scheduled',
                        'gray'    => 'completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('changeStatus')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('New Status')
                            ->options(array_combine($validStatuses, $validStatuses))
                            ->required(),
                    ])
                    ->action(function ($record, $data) use ($validStatuses) {
                        $status = strtolower(trim($data['status']));
                        if (!in_array($status, $validStatuses)) {
                            Notification::make()
                                ->title('Invalid Status')
                                ->danger()
                                ->body('The selected status is invalid.')
                                ->send();
                            return;
                        }
                        $record->update(['status' => $status]);
                        Notification::make()
                            ->title('Status Updated')
                            ->success()
                            ->body('The pickup status has been updated successfully.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWastes::route('/'),
            'create' => Pages\CreateWaste::route('/create'),
            'edit' => Pages\EditWaste::route('/{record}/edit'),
        ];
    }
}

