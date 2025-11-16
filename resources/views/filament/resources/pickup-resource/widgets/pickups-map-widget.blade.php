<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Pickup Route Map</span>
                </div>
                
                <div class="flex items-center gap-4">
                    <button 
                        id="optimize-from-location" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-150 shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>
                        </svg>
                        <span id="optimize-btn-text">Optimize Route from My Location</span>
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Legend -->
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span>Optimized Route</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span>Start Point</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span>End Point</span>
                </div>
                <div class="ml-auto text-sm text-gray-600 dark:text-gray-400">
                    Total Locations: <strong>{{ count($this->getPickups()) }}</strong>
                </div>
            </div>

            <!-- Stats (shown after optimization) -->
            <div id="route-stats" style="display: none;" class="grid grid-cols-2 gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Total Distance</div>
                        <div id="total-distance" class="text-lg font-bold text-blue-600">-</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Estimated Time</div>
                        <div id="estimated-time" class="text-lg font-bold text-blue-600">-</div>
                    </div>
                </div>
            </div>

            <!-- Map Container -->
            <div id="pickups-map" style="height: 600px; width: 100%; border-radius: 8px;" class="shadow-sm"></div>
            
            <!-- Turn-by-turn Directions -->
            <div id="directions-container" style="display: none;">
                <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    Turn-by-Turn Directions
                </h3>
                <div id="directions-list" class="space-y-2 max-h-64 overflow-y-auto"></div>
            </div>
        </div>
    </x-filament::section>

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
        #optimize-from-location:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let currentLocationMarker;
        let routeLayer;
        let markersLayer;
        
        document.addEventListener('DOMContentLoaded', function() {
            map = L.map('pickups-map').setView([27.7172, 85.3240], 12);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            routeLayer = L.layerGroup().addTo(map);
            markersLayer = L.layerGroup().addTo(map);

            const pickups = @json($this->getPickups());

            if (pickups.length > 0) {
                displayPickups(pickups);
            }

            document.getElementById('optimize-from-location').addEventListener('click', function() {
                optimizeFromCurrentLocation();
            });
        });

        function optimizeFromCurrentLocation() {
            const btn = document.getElementById('optimize-from-location');
            const btnText = document.getElementById('optimize-btn-text');
            
            btn.disabled = true;
            btnText.textContent = 'Getting your location...';

            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                btn.disabled = false;
                btnText.textContent = 'Optimize Route from My Location';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    btnText.textContent = 'Optimizing route...';
                    
                    if (currentLocationMarker) {
                        map.removeLayer(currentLocationMarker);
                    }
                    
                    currentLocationMarker = L.marker([lat, lon], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: `
                                <div style="
                                    background-color: #10b981;
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    border: 4px solid white;
                                    box-shadow: 0 4px 10px rgba(0,0,0,0.4);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    animation: pulse 2s infinite;
                                ">
                                    <div style="font-size: 20px;">📍</div>
                                </div>
                                <style>
                                    @keyframes pulse {
                                        0%, 100% { transform: scale(1); }
                                        50% { transform: scale(1.1); }
                                    }
                                </style>
                            `,
                            iconSize: [40, 40],
                            iconAnchor: [20, 20]
                        })
                    }).addTo(map);
                    
                    currentLocationMarker.bindPopup('<strong>Your Current Location</strong>').openPopup();
                    
                    fetch('/admin/pickups/optimize-route', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            start_lat: lat,
                            start_lon: lon
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.route) {
                            displayRoute(data.route);
                            map.setView([lat, lon], 13);
                            
                            document.getElementById('route-stats').style.display = 'grid';
                            document.getElementById('total-distance').textContent = data.route.totalDistance + ' km';
                            document.getElementById('estimated-time').textContent = data.route.estimatedTime;
                            
                            btnText.textContent = '✓ Route Optimized!';
                            setTimeout(() => {
                                btnText.textContent = 'Optimize Route from My Location';
                            }, 3000);
                        } else {
                            alert(data.message || 'Could not optimize route');
                            btnText.textContent = 'Optimize Route from My Location';
                        }
                        btn.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error optimizing route. Please try again.');
                        btn.disabled = false;
                        btnText.textContent = 'Optimize Route from My Location';
                    });
                },
                function(error) {
                    console.error('Geolocation error:', error);
                    alert('Unable to get your location. Please enable location services.');
                    btn.disabled = false;
                    btnText.textContent = 'Optimize Route from My Location';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function displayPickups(pickups) {
            markersLayer.clearLayers();
            
            pickups.forEach((pickup, index) => {
                const marker = L.marker([pickup.latitude, pickup.longitude], {
                    icon: createCustomIcon('pending', pickup.waste_type)
                }).addTo(markersLayer);
                
                marker.bindPopup(createPickupPopup(pickup, index + 1, 0));
            });
            
            if (pickups.length > 0) {
                const bounds = L.latLngBounds(pickups.map(p => [p.latitude, p.longitude]));
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }

        function displayRoute(optimizedRoute) {
            routeLayer.clearLayers();
            markersLayer.clearLayers();
            
            const route = optimizedRoute.route;
            
            if (!route || route.length === 0) return;

            const routeCoordinates = route.map(point => [point.latitude, point.longitude]);
            
            const routeLine = L.polyline(routeCoordinates, {
                color: '#3b82f6',
                weight: 4,
                opacity: 0.7,
                smoothFactor: 1
            }).addTo(routeLayer);

            route.forEach((point, index) => {
                let markerColor, markerIcon;
                
                if (index === 0) {
                    markerColor = '#22c55e';
                    markerIcon = '🏁';
                } else if (index === route.length - 1) {
                    markerColor = '#ef4444';
                    markerIcon = '🏁';
                } else {
                    markerColor = '#3b82f6';
                    markerIcon = getWasteTypeIcon(point.waste_type);
                }

                const customIcon = createNumberedIcon(index + 1, markerColor, markerIcon);
                
                const marker = L.marker([point.latitude, point.longitude], {
                    icon: customIcon
                }).addTo(markersLayer);

                const distance = index > 0 && optimizedRoute.directions[index - 1] 
                    ? optimizedRoute.directions[index - 1].distance 
                    : 0;
                
                marker.bindPopup(createPickupPopup(point, index + 1, distance));
            });

            if (optimizedRoute.directions) {
                optimizedRoute.directions.forEach((direction) => {
                    const midPoint = [
                        (direction.from.latitude + direction.to.latitude) / 2,
                        (direction.from.longitude + direction.to.longitude) / 2
                    ];
                    
                    L.marker(midPoint, {
                        icon: L.divIcon({
                            className: 'distance-label',
                            html: `<div style="
                                background: white;
                                padding: 2px 6px;
                                border-radius: 4px;
                                font-size: 10px;
                                font-weight: bold;
                                color: #3b82f6;
                                border: 1px solid #3b82f6;
                                white-space: nowrap;
                            ">${direction.distance} km</div>`,
                            iconSize: [0, 0]
                        })
                    }).addTo(routeLayer);
                });
                
                displayDirections(optimizedRoute.directions);
            }

            map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
        }

        function displayDirections(directions) {
            const container = document.getElementById('directions-container');
            const list = document.getElementById('directions-list');
            
            list.innerHTML = '';
            
            directions.forEach(direction => {
                const div = document.createElement('div');
                div.className = 'flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg';
                div.innerHTML = `
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">
                        ${direction.step}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">
                            Head <strong>${direction.direction}</strong> for <strong>${direction.distance} km</strong>
                        </p>
                        ${direction.to.user_name ? `
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Pick up ${direction.to.waste_type} waste (${direction.to.weight} kg) from ${direction.to.user_name}
                        </p>
                        ` : ''}
                    </div>
                `;
                list.appendChild(div);
            });
            
            container.style.display = 'block';
        }

        function createPickupPopup(point, step, distance) {
            const isStart = step === 1;
            const statusColor = isStart ? '#22c55e' : '#3b82f6';
            
            return `
                <div style="min-width: 220px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <h3 style="font-weight: 600; margin: 0;">Stop #${step}</h3>
                        <span style="background: ${statusColor}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">
                            ${isStart ? 'START' : 'PICKUP'}
                        </span>
                    </div>
                    ${point.id !== 'start' && point.user_name ? `
                    <div style="font-size: 13px;">
                        <div><strong>Type:</strong> ${point.waste_type}</div>
                        <div><strong>User:</strong> ${point.user_name}</div>
                        <div><strong>Weight:</strong> ${point.weight} kg</div>
                        ${distance > 0 ? `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;"><strong>Distance:</strong> ${distance} km</div>` : ''}
                    </div>
                    ` : '<p>Your current location</p>'}
                </div>
            `;
        }

        function getWasteTypeIcon(wasteType) {
            const icons = {
                'organic': '🌿',
                'recyclable': '♻️',
                'hazardous': '☢️',
                'electronic': '🔌',
                'general': '🗑️'
            };
            return icons[wasteType] || '📦';
        }

        function createCustomIcon(status, wasteType) {
            const color = '#eab308';
            const icon = getWasteTypeIcon(wasteType);
            
            return L.divIcon({
                className: 'custom-marker',
                html: `<div style="background: ${color}; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 3px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"><div style="transform: rotate(45deg); font-size: 16px;">${icon}</div></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });
        }

        function createNumberedIcon(number, color, emoji) {
            return L.divIcon({
                className: 'custom-marker',
                html: `
                    <div style="position: relative;">
                        <div style="background: ${color}; width: 36px; height: 36px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 18px;">${emoji}</div>
                        <div style="position: absolute; bottom: -8px; right: -8px; background: white; color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid ${color}; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">${number}</div>
                    </div>
                `,
                iconSize: [36, 36],
                iconAnchor: [18, 18]
            });
        }
    </script>
    @endpush
</x-filament-widgets::widget>