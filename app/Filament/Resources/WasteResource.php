<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WasteResource\Pages;
use App\Models\Waste;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WasteResource extends Resource
{
    protected static ?string $model = Waste::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('waste_type')
                ->options([
                    'recyclable' => 'Recyclable',
                    'non-recyclable' => 'Non-Recyclable',
                ])
                ->default('recycle')
                ->required(),

            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required(),

            Forms\Components\TextInput::make('weight')
                ->required()
                ->minValue(0)
                ->rule('min:0')
                ->numeric(),

            Forms\Components\DatePicker::make('date')
            ->minDate(today())
                ->required(),

            Forms\Components\Select::make('shift')
                ->options([
                    '9AM-12PM' => '9:00 AM - 12:00 PM',
                    '12PM-3PM' => '12:00 PM - 3:00 PM',
                    '3PM-6PM' => '3:00 PM - 6:00 PM',
                ])
                ->required(),

             Forms\Components\Select::make('status')
            ->label('Status')
            ->options([
                'pending' => 'Pending',
                'accepted' => 'Accepted',
                'rejected' => 'Rejected',
                're-scheduled' => 'Re-scheduled',
            ])
            ->default('pending')
            ->required()
            ->disableOptionWhen(function (?string $value, callable $get) {
                $currentStatus = $get('status');
                if ($currentStatus === 'accepted') {
                    return $value !== 're-scheduled';
                }
                return false;
            })
            ->disabled(fn (callable $get) => $get('status') === 'rejected'),


            Forms\Components\TextInput::make('latitude')
                ->label('Latitude')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('longitude')
                ->label('Longitude')
                ->numeric()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('waste_type')
                    ->label('Waste')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User Email'),

                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Pickup Date')
                    ->date('F j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift')
                    ->label('Pickup Shift')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        're-scheduled' => 'info',
                        'completed' =>'turquoise'
                    }),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('Lat')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('longitude')
                    ->label('Lng')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('Waste Request Details')
                    ->modalSubmitAction(fn ($action) => $action->label('Close'))
                    ->modalWidth('xl')
                    ->form(fn ($record) => [
                        Forms\Components\TextInput::make('waste_type')
                            ->label('Waste Type')
                            ->default($record->waste_type)
                            ->disabled(),

                        Forms\Components\TextInput::make('user_name')
                            ->label('User Name')
                            ->default($record->user->name)
                            ->disabled(),

                        Forms\Components\TextInput::make('user_email')
                            ->label('User Email')
                            ->default($record->user->email)
                            ->disabled(),

                        Forms\Components\TextInput::make('weight')
                            ->label('Weight')
                            ->default($record->weight)
                            ->disabled(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Pickup Date')
                            ->default($record->date)
                            ->disabled(),

                        Forms\Components\TextInput::make('shift')
                            ->label('Shift')
                            ->default($record->shift)
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->default($record->status)
                            ->disabled(),

                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->default($record->latitude)
                            ->disabled(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->default($record->longitude)
                            ->disabled(),
                    ])
                    ->extraModalFooterActions([
                        Tables\Actions\EditAction::make()
                            ->label('Edit')
                            ->button()
                            ->color('success'),
                    ]),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
