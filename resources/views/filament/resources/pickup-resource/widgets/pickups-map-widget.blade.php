<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>All Pickup Locations</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Legend -->
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <span>Pending</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span>Collected</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span>Cancelled</span>
                </div>
                <div class="ml-auto text-sm text-gray-600 dark:text-gray-400">
                    Total Locations: <strong>{{ count($this->getPickups()) }}</strong>
                </div>
            </div>

            <!-- Map Container -->
            <div id="pickups-map" style="height: 500px; width: 100%; border-radius: 8px;" class="shadow-sm"></div>
        </div>
    </x-filament::section>

    @once
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .custom-marker {
            background: transparent;
            border: none;
        }
        .leaflet-popup-content-wrapper {
            border-radius: 8px;
        }
        .leaflet-popup-content {
            margin: 12px;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map centered on Kathmandu
            const map = L.map('pickups-map').setView([27.7172, 85.3240], 12);

            // Add OpenStreetMap tiles
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Pickup data from PHP
            const pickups = @js($this->getPickups());

            // Custom icons based on status
            const getMarkerColor = (status) => {
                switch(status) {
                    case 'pending': return '#eab308';
                    case 'collected': return '#22c55e';
                    case 'cancelled': return '#ef4444';
                    default: return '#6b7280';
                }
            };

            const getWasteTypeIcon = (wasteType) => {
                switch(wasteType) {
                    case 'organic': return '🌿';
                    case 'recyclable': return '♻️';
                    case 'hazardous': return '☢️';
                    case 'electronic': return '🔌';
                    case 'general': return '🗑️';
                    default: return '📦';
                }
            };

            const createCustomIcon = (status, wasteType) => {
                const color = getMarkerColor(status);
                const icon = getWasteTypeIcon(wasteType);
                
                return L.divIcon({
                    className: 'custom-marker',
                    html: `
                        <div style="
                            background-color: ${color};
                            width: 32px;
                            height: 32px;
                            border-radius: 50% 50% 50% 0;
                            transform: rotate(-45deg);
                            border: 3px solid white;
                            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <div style="
                                transform: rotate(45deg);
                                font-size: 16px;
                            ">${icon}</div>
                        </div>
                    `,
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });
            };

            // Add markers for each pickup
            pickups.forEach(pickup => {
                const marker = L.marker(
                    [pickup.latitude, pickup.longitude],
                    { icon: createCustomIcon(pickup.status, pickup.waste_type) }
                ).addTo(map);

                // Create popup content
                const statusColor = getMarkerColor(pickup.status);
                const popupContent = `
                    <div style="min-width: 220px; font-family: system-ui, -apple-system, sans-serif;">
                        <h3 style="font-weight: 600; margin-bottom: 10px; font-size: 15px; color: #1f2937;">
                            ${getWasteTypeIcon(pickup.waste_type)} ${pickup.waste_type.charAt(0).toUpperCase() + pickup.waste_type.slice(1)} Waste
                        </h3>
                        <div style="font-size: 13px; line-height: 1.8; color: #4b5563;">
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #6b7280;">User:</span>
                                <strong>${pickup.user_name}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #6b7280;">Weight:</span>
                                <strong>${pickup.weight} kg</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #6b7280;">Date:</span>
                                <strong>${pickup.date || 'N/A'}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                <span style="color: #6b7280;">Shift:</span>
                                <strong style="text-transform: capitalize;">${pickup.shift || 'N/A'}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 4px 0; align-items: center;">
                                <span style="color: #6b7280;">Status:</span>
                                <span style="
                                    background-color: ${statusColor};
                                    color: white;
                                    padding: 2px 10px;
                                    border-radius: 12px;
                                    font-size: 11px;
                                    font-weight: 600;
                                    text-transform: uppercase;
                                ">${pickup.status}</span>
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                                <a href="/admin/pickups/${pickup.id}/edit" 
                                   style="
                                       color: #3b82f6; 
                                       text-decoration: none;
                                       font-weight: 500;
                                       display: inline-flex;
                                       align-items: center;
                                       gap: 4px;
                                   ">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Pickup
                                </a>
                            </div>
                        </div>
                    </div>
                `;

                marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'custom-popup'
                });

                // Optional: Open popup on hover
                marker.on('mouseover', function() {
                    this.openPopup();
                });
            });

            // Fit map to show all markers if there are any
            if (pickups.length > 0) {
                const bounds = L.latLngBounds(pickups.map(p => [p.latitude, p.longitude]));
                map.fitBounds(bounds, { 
                    padding: [50, 50],
                    maxZoom: 15
                });
            }
        });
    </script>
    @endpush
    @endonce
</x-filament-widgets::widget>