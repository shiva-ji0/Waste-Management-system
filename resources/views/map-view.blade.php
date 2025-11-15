`
<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex gap-4 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                <span class="text-sm">Pending</span>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-green-500"></div>
                <span class="text-sm">Collected</span>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-red-500"></div>
                <span class="text-sm">Cancelled</span>
            </div>
        </div>

        <div id="map" style="height: 70vh; width: 100%; border-radius: 8px;" class="shadow-lg"></div>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize map centered on Kathmandu
                const map = L.map('map').setView([27.7172, 85.3240], 12);

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
                        case 'pending': return 'orange';
                        case 'collected': return 'green';
                        case 'cancelled': return 'red';
                        default: return 'blue';
                    }
                };

                const createCustomIcon = (status) => {
                    const color = getMarkerColor(status);
                    return L.divIcon({
                        className: 'custom-marker',
                        html: `
                        <div style="
                            background-color: ${color};
                            width: 30px;
                            height: 30px;
                            border-radius: 50% 50% 50% 0;
                            transform: rotate(-45deg);
                            border: 3px solid white;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                        ">
                            <div style="
                                position: absolute;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%) rotate(45deg);
                                color: white;
                                font-size: 14px;
                                font-weight: bold;
                            ">📍</div>
                        </div>
                    `,
                        iconSize: [30, 30],
                        iconAnchor: [15, 30],
                        popupAnchor: [0, -30]
                    });
                };

                // Add markers for each pickup
                pickups.forEach(pickup => {
                    const marker = L.marker(
                        [pickup.latitude, pickup.longitude],
                        { icon: createCustomIcon(pickup.status) }
                    ).addTo(map);

                    // Create popup content
                    const popupContent = `
                    <div style="min-width: 200px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; font-size: 16px;">
                            ${pickup.waste_type.charAt(0).toUpperCase() + pickup.waste_type.slice(1)} Waste
                        </h3>
                        <div style="font-size: 14px; line-height: 1.6;">
                            <p><strong>User:</strong> ${pickup.user_name}</p>
                            <p><strong>Weight:</strong> ${pickup.weight} kg</p>
                            <p><strong>Date:</strong> ${pickup.date || 'N/A'}</p>
                            <p><strong>Shift:</strong> ${pickup.shift || 'N/A'}</p>
                            <p><strong>Status:</strong>
                                <span style="
                                    background-color: ${getMarkerColor(pickup.status)};
                                    color: white;
                                    padding: 2px 8px;
                                    border-radius: 4px;
                                    font-size: 12px;
                                ">${pickup.status}</span>
                            </p>
                            <p style="margin-top: 8px;">
                                <a href="/admin/pickups/${pickup.id}/edit"
                                   style="color: #3b82f6; text-decoration: underline;">
                                    Edit Pickup
                                </a>
                            </p>
                        </div>
                    </div>
                `;

                    marker.bindPopup(popupContent);
                });

                // Fit map to show all markers
                if (pickups.length > 0) {
                    const bounds = L.latLngBounds(pickups.map(p => [p.latitude, p.longitude]));
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            });
        </script>
    @endpush
</x-filament-panels::page>
