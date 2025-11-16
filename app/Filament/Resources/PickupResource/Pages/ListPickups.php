<?php

namespace App\Filament\Resources\PickupResource\Pages;

use App\Filament\Resources\PickupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;

class ListPickups extends ListRecords
{
    protected static string $resource = PickupResource::class;

    // Received ordered IDs to apply to the table query
    public array $optimizedOrder = [];

    // Preserve existing optimization route & start location (used for distances/sequence)
    public ?array $sortedRoute = null;
    public ?array $startLocation = null;

    // Listen for events from JS
    protected $listeners = [
        'reorderTable' => 'applyReorder',
        // Keep the existing On attribute handler for compatibility with your previous code:
        'table-sorted-by-distance' => 'handleTableSortedByDistance',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('clearOptimization')
                ->label('Clear Route Optimization')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->sortedRoute !== null || !empty($this->optimizedOrder))
                ->action(function () {
                    $this->sortedRoute = null;
                    $this->startLocation = null;
                    $this->optimizedOrder = [];

                    Notification::make()
                        ->title('Route Optimization Cleared')
                        ->body('Table returned to default sorting')
                        ->info()
                        ->send();

                    $this->resetTable();
                })
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PickupResource\Widgets\PickupsMapWidget::class,
        ];
    }

    /**
     * Apply ordered ids emitted from frontend.
     */
    public function applyReorder($orderedIds): void
    {
        // ensure it's an array
        if (!is_array($orderedIds)) {
            // if JSON string, try decode
            $decoded = json_decode($orderedIds, true);
            $orderedIds = is_array($decoded) ? $decoded : [$orderedIds];
        }

        $this->optimizedOrder = array_values(array_filter($orderedIds, fn($id) => $id !== null && $id !== ''));

        // Force refresh table
        $this->resetTable();

        Notification::make()
            ->title('Table Reordered')
            ->body('Table updated to show the optimized route order.')
            ->success()
            ->send();
    }

    #[On('table-sorted-by-distance')]
    public function handleTableSortedByDistance($route, $startLocation): void
    {
        $this->sortedRoute = $route;
        $this->startLocation = $startLocation;

        // If backend also returned ordered ids, extract them and use optimizedOrder
        try {
            $orderedIds = collect($route)
                ->filter(fn($item) => isset($item['id']) && $item['id'] !== 'start')
                ->pluck('id')
                ->values()
                ->toArray();

            if (!empty($orderedIds)) {
                $this->optimizedOrder = $orderedIds;
            }
        } catch (\Throwable $e) {
            // ignore extraction errors
        }

        $pickupCount = collect($route)->filter(fn($item) => $item['id'] !== 'start')->count();

        Notification::make()
            ->title('Route Optimized Successfully')
            ->body("Table sorted by optimized route with {$pickupCount} pickups")
            ->success()
            ->duration(5000)
            ->send();

        // Force refresh table
        $this->resetTable();
    }

    /**
     * Build the table query: if optimizedOrder exists, apply ordering using a CASE expression.
     */
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (!empty($this->optimizedOrder)) {
            // Ensure ids are integers to avoid SQL injection risk in this context.
            $orderedIds = array_map('intval', $this->optimizedOrder);

            // Filter the query to include only those ids (so pagination shows only them in correct order),
            // optionally you might want to remove whereIn and just order all rows; here we limit to those IDs.
            $query->whereIn('id', $orderedIds);

            // Build CASE expression to preserve ordering
            $orderCase = 'CASE id ';
            foreach ($orderedIds as $index => $id) {
                $orderCase .= "WHEN {$id} THEN {$index} ";
            }
            $orderCase .= 'END';

            $query->orderByRaw($orderCase);
        }

        return $query;
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function paginateTableQuery(Builder $query): \Illuminate\Contracts\Pagination\Paginator
    {
        $paginator = parent::paginateTableQuery($query);

        if ($this->sortedRoute && $this->startLocation) {
            $paginator->through(function ($record) {
                if ($record->latitude && $record->longitude) {
                    $record->calculated_distance = $this->calculateDistance(
                        $this->startLocation['lat'],
                        $this->startLocation['lon'],
                        $record->latitude,
                        $record->longitude
                    );
                } else {
                    $record->calculated_distance = null;
                }

                $sequence = collect($this->sortedRoute)
                    ->search(fn($item) => isset($item['id']) && $item['id'] == $record->id);

                $record->route_sequence = $sequence !== false ? $sequence : null;

                return $record;
            });
        }

        return $paginator;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    public function updatedTableFilters(): void
    {
        parent::updatedTableFilters();

        $this->sortedRoute = null;
        $this->startLocation = null;
        $this->optimizedOrder = [];
    }

    protected function getTableDescription(): ?string
    {
        if ($this->sortedRoute !== null && $this->startLocation !== null) {
            $pickupCount = collect($this->sortedRoute)->filter(fn($item) => $item['id'] !== 'start')->count();
            return "🚚 Showing optimized route with {$pickupCount} pickups sorted by distance from your location";
        }

        return null;
    }
}
