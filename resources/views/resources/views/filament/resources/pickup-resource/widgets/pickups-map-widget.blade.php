<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
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
                    Total Locations: <strong>{{ count($pickups) }}</strong>
                </div>
            </div>

            <div id="pickups-map"
                 style="height: 500px; width: 100%; border-radius: 8px;"
                 class="shadow-sm">
            </div>
        </div>
    </x-filament::section>

    @once
        @push('styles')
            <link rel="stylesheet"
                  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <style>
                .custom-marker {
                    background: transparent;
                    border: none;
                }
            </style>
        @endpush

        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

{{--            <script>--}}
{{--                document.addEventListener("livewire:navigated", () => {--}}
{{--                    const mapContainer = document.getElementById("pickups-map");--}}

{{--                    if (!mapContainer || mapContainer._leaflet_id) {--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    const map = L.map('pickups-map').setView([27.7172, 85.3240], 12);--}}

{{--                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {--}}
{{--                        maxZoom: 19,--}}
{{--                        attribution: '© OpenStreetMap'--}}
{{--                    }).addTo(map);--}}

{{--                    const pickups = @js($pickups);--}}

{{--                    const getColor = (status) => ({--}}
{{--                        pending: '#eab308',--}}
{{--                        collected: '#22c55e',--}}
{{--                        cancelled: '#ef4444'--}}
{{--                    }[status] || '#6b7280');--}}

{{--                    const getIconSymbol = (type) => ({--}}
{{--                        organic: '🌿',--}}
{{--                        recyclable: '♻️',--}}
{{--                        hazardous: '☢️',--}}
{{--                        electronic: '🔌',--}}
{{--                        general: '🗑️'--}}
{{--                    }[type] || '📦');--}}

{{--                    const createIcon = (status, type) =>--}}
{{--                        L.divIcon({--}}
{{--                            className: 'custom-marker',--}}
{{--                            html: `--}}
{{--                                <div style="--}}
{{--                                    background-color: ${getColor(status)};--}}
{{--                                    width: 32px;--}}
{{--                                    height: 32px;--}}
{{--                                    border-radius: 50% 50% 50% 0;--}}
{{--                                    transform: rotate(-45deg);--}}
{{--                                    border: 3px solid white;--}}
{{--                                    box-shadow: 0 3px 8px rgba(0,0,0,0.3);--}}
{{--                                    display: flex;--}}
{{--                                    align-items: center;--}}
{{--                                    justify-content: center;--}}
{{--                                ">--}}
{{--                                    <div style="transform: rotate(45deg); font-size: 16px;">--}}
{{--                                        ${getIconSymbol(type)}--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            `,--}}
{{--                            iconSize: [32, 32],--}}
{{--                            iconAnchor: [16, 32],--}}
{{--                            popupAnchor: [0, -32]--}}
{{--                        });--}}

{{--                    pickups.forEach(pickup => {--}}
{{--                        const marker = L.marker(--}}
{{--                            [pickup.latitude, pickup.longitude],--}}
{{--                            {icon: createIcon(pickup.status, pickup.waste_type)}--}}
{{--                        ).addTo(map);--}}

{{--                        marker.bindPopup(`--}}
{{--                            <div style="min-width: 220px;">--}}
{{--                                <h3 style="font-weight:600; margin-bottom:10px;">--}}
{{--                                    ${getIconSymbol(pickup.waste_type)}--}}
{{--                                    ${pickup.waste_type}--}}
{{--                                </h3>--}}
{{--                                <p>User: <strong>${pickup.user_name}</strong></p>--}}
{{--                                <p>Weight: <strong>${pickup.weight} kg</strong></p>--}}
{{--                                <p>Date: <strong>${pickup.date}</strong></p>--}}
{{--                                <p>Shift: <strong>${pickup.shift}</strong></p>--}}
{{--                                <p>Status: <strong>${pickup.status}</strong></p>--}}
{{--                                <hr style="margin: 8px 0;">--}}
{{--                                <a href="/admin/pickups/${pickup.id}/edit"--}}
{{--                                   style="color:#3b82f6; font-weight:600;">--}}
{{--                                   Edit Pickup--}}
{{--                                </a>--}}
{{--                            </div>--}}
{{--                        `);--}}
{{--                    });--}}

{{--                    if (pickups.length > 0) {--}}
{{--                        const bounds = L.latLngBounds(--}}
{{--                            pickups.map(p => [p.latitude, p.longitude])--}}
{{--                        );--}}

{{--                        map.fitBounds(bounds, { padding: [50, 50] });--}}
{{--                    }--}}
{{--                });--}}
{{--            </script>--}}
                <script>
                    function initPickupMap() {
                        const mapContainer = document.getElementById("pickups-map");

                        // Prevent double-initialization
                        if (!mapContainer || mapContainer._leaflet_id) {
                            return;
                        }

                        const map = L.map('pickups-map').setView([27.7172, 85.3240], 12);

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap'
                        }).addTo(map);

                        const pickups = @js($pickups);

                        const getColor = (status) => ({
                            pending: '#eab308',
                            collected: '#22c55e',
                            cancelled: '#ef4444'
                        }[status] || '#6b7280');

                        const getIconSymbol = (type) => ({
                            organic: '🌿',
                            recyclable: '♻️',
                            hazardous: '☢️',
                            electronic: '🔌',
                            general: '🗑️'
                        }[type] || '📦');

                        const createIcon = (status, type) =>
                            L.divIcon({
                                className: 'custom-marker',
                                html: `
                    <div style="
                        background-color: ${getColor(status)};
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
                        <div style="transform: rotate(45deg); font-size: 16px;">
                            ${getIconSymbol(type)}
                        </div>
                    </div>
                `,
                                iconSize: [32, 32],
                                iconAnchor: [16, 32],
                                popupAnchor: [0, -32]
                            });

                        pickups.forEach(pickup => {
                            const marker = L.marker(
                                [pickup.latitude, pickup.longitude],
                                { icon: createIcon(pickup.status, pickup.waste_type) }
                            ).addTo(map);

                            marker.bindPopup(`
                <div style="min-width: 220px;">
                    <h3 style="font-weight:600; margin-bottom:10px;">
                        ${getIconSymbol(pickup.waste_type)}
                        ${pickup.waste_type}
                    </h3>
                    <p>User: <strong>${pickup.user_name}</strong></p>
                    <p>Weight: <strong>${pickup.weight} kg</strong></p>
                    <p>Date: <strong>${pickup.date}</strong></p>
                    <p>Shift: <strong>${pickup.shift}</strong></p>
                    <p>Status: <strong>${pickup.status}</strong></p>
                    <hr style="margin: 8px 0;">
                    <a href="/admin/pickups/${pickup.id}/edit"
                       style="color:#3b82f6; font-weight:600;">
                       Edit Pickup
                    </a>
                </div>
            `);
                        });

                        if (pickups.length > 0) {
                            const bounds = L.latLngBounds(
                                pickups.map(p => [p.latitude, p.longitude])
                            );
                            map.fitBounds(bounds, { padding: [50, 50] });
                        }
                    }

                    // Run on page load
                    document.addEventListener("DOMContentLoaded", initPickupMap);

                    // Run when Filament SPA navigation happens
                    document.addEventListener("livewire:navigated", initPickupMap);
                </script>

            @endpush
    @endonce
</x-filament-widgets::widget>
