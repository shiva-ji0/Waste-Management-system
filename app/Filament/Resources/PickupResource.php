<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PickupResource\Pages;
use App\Models\Waste;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Dotswan\MapPicker\Fields\Map;

class PickupResource extends Resource
{
    protected static ?string $model = Waste::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Pickups';

    protected static ?string $modelLabel = 'Pickup';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('waste_type')
                    ->options([
                        'organic' => 'Organic',
                        'recyclable' => 'Recyclable',
                        'hazardous' => 'Hazardous',
                        'electronic' => 'Electronic',
                        'general' => 'General',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->suffix('kg')
                    ->required(),

                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),

                Forms\Components\Select::make('shift')
                    ->options([
                        'morning' => 'Morning',
                        'afternoon' => 'Afternoon',
                        'evening' => 'Evening',
                    ])
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'collected' => 'Collected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),

                Map::make('location')
                    ->label('Pickup Location')
                    ->columnSpanFull()
                    ->defaultLocation(latitude: 27.7172, longitude: 85.3240) // Kathmandu default
                    ->afterStateUpdated(function (callable $set, ?array $state): void {
                        $set('latitude', $state['lat']);
                        $set('longitude', $state['lng']);
                    })
                    ->afterStateHydrated(function ($state, $record, callable $set): void {
                        if ($record) {
                            $set('location', [
                                'lat' => $record->latitude,
                                'lng' => $record->longitude
                            ]);
                        }
                    })
                    ->extraStyles([
                        'min-height: 50vh',
                        'border-radius: 8px'
                    ])
                    ->liveLocation(true, true, 5000)
                    ->showMarker()
                    ->markerColor("#ef4444")
                    ->showFullscreenControl()
                    ->showZoomControl()
                    ->draggable()
                    ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                    ->zoom(15)
                    ->detectRetina()
                    ->showMyLocationButton(),

                Forms\Components\TextInput::make('latitude')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(),

                Forms\Components\TextInput::make('longitude')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('waste_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'organic' => 'success',
                        'recyclable' => 'info',
                        'hazardous' => 'danger',
                        'electronic' => 'warning',
                        'general' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('weight')
                    ->suffix(' kg')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'collected' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->formatStateUsing(fn ($record) =>
                    $record->latitude && $record->longitude
                        ? "📍 {$record->latitude}, {$record->longitude}"
                        : 'N/A'
                    )
                    ->url(fn ($record) =>
                    $record->latitude && $record->longitude
                        ? "https://www.google.com/maps?q={$record->latitude},{$record->longitude}"
                        : null
                    )
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'collected' => 'Collected',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('waste_type')
                    ->options([
                        'organic' => 'Organic',
                        'recyclable' => 'Recyclable',
                        'hazardous' => 'Hazardous',
                        'electronic' => 'Electronic',
                        'general' => 'General',
                    ]),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('viewOnMap')
                    ->label('View on Map')
                    ->icon('heroicon-o-map')
                    ->url(fn ($record) =>
                    "https://www.google.com/maps?q={$record->latitude},{$record->longitude}"
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('markAsCollected')
                        ->label('Mark as Collected')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'collected']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPickups::route('/'),
            'create' => Pages\CreatePickup::route('/create'),
            'edit' => Pages\EditPickup::route('/{record}/edit'),
            'map' => Pages\MapView::route('/map'),
        ];
    }
}
