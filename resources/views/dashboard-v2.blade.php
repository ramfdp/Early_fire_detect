@extends('layouts.default')

@section('title', 'Fire Detection Dashboard')

@push('css')
    <link href="/assets/plugins/jvectormap-next/jquery-jvectormap.css" rel="stylesheet" />
    <link href="/assets/plugins/datepickk/dist/datepickk.min.css" rel="stylesheet" />
    <link href="/assets/plugins/gritter/css/jquery.gritter.css" rel="stylesheet" />
    <link href="/assets/plugins/nvd3/build/nv.d3.css" rel="stylesheet" />
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
    <style>
        .building-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        .status-normal {
            background-color: #32a932;
            color: white;
        }
        .status-siaga {
            background-color: #f59c1a;
            color: white;
        }
        .status-kebakaran {
            background-color: #ff3e3e;
            color: white;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .temperature-display {
            font-size: 32px;
            font-weight: bold;
        }
        .status-label {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 18px;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
        }
        .user-table th,
        .user-table td {
            vertical-align: middle;
        }
        #fire-map {
            height: 500px; /* Increased height */
            width: 100%;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            z-index: 1; /* Ensure map is above other elements */
        }

        .leaflet-control-zoom {
            box-shadow: 0 1px 5px rgba(0,0,0,0.4);
        }

        .fire-marker-normal, .fire-marker-siaga, .fire-marker-kebakaran {
            width: 20px !important;
            height: 20px !important;
            margin-left: -10px !important;
            margin-top: -10px !important;
            border-radius: 50%;
            border: 2px solid #fff;
            text-align: center;
            color: white;
            box-shadow: 0 0 4px rgba(0,0,0,0.5);
        }
        
        .fire-marker-kebakaran {
            animation: map-blink 1s infinite;
        }
        
        @keyframes map-blink {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .map-legend {
            background: white;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
        .map-legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .map-legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
    </style>
@endpush

@push('scripts')
    <script src="/assets/plugins/d3/d3.min.js"></script>
    <script src="/assets/plugins/nvd3/build/nv.d3.js"></script>
    <script src="/assets/plugins/jvectormap-next/jquery-jvectormap.min.js"></script>
    <script src="/assets/plugins/jvectormap-content/world-mill.js"></script>
    <script src="/assets/plugins/datepickk/dist/datepickk.min.js"></script>
    <script src="/assets/plugins/gritter/js/jquery.gritter.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Define building locations with precise coordinates (fixed)

        const buildingLocations = {
            'Wisma Krakatau': [-6.014580, 106.063235], // Jl. KH. Yasin Beji No.6, Kebondalem
            'CM-1': [-6.016420, 106.058432],           // Jl. Jenderal Sudirman No.10, Kebondalem
            'CM-2': [-6.018923, 106.016231],           // XXFF+7VP, Tegalratu, Ciwandan
            'CM-3': [-5.998242, 106.030167],           // X2WG+9GQ, Ramanuju, Gerogol
            'Antartika': [-6.011235, 106.057421]       // 2259+W2H, Rw. Arum, Purwakarta
        };

        // Map initialization
        let fireMap;
        let buildingMarkers = {};

        // Function to generate random temperature values
        function generateRandomTemperature(min, max) {
            return (Math.random() * (max - min) + min).toFixed(1);
        }

        // Function to determine status based on temperature
        function getTemperatureStatus(temp) {
            temp = parseFloat(temp);
            if (temp < 43) return 'normal';
            if (temp >= 43 && temp < 53) return 'siaga';
            return 'kebakaran';
        }

        // Initialize the map
        function initMap() {
            // Create map with proper zoom level and center coordinates
            fireMap = L.map('fire-map', {
                minZoom: 13,  // Prevent zooming out too far
                maxZoom: 18,  // Allow reasonable zoom in
                zoomControl: true
            }).setView([-6.0144, 106.0577], 14);  // Adjusted zoom level
            
            // Add tile layer with better loading parameters
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                subdomains: 'abc',
                tileSize: 256,
                updateWhenIdle: true,
                keepBuffer: 2
            }).addTo(fireMap);
            
            // Add building markers
            addBuildingMarkers();
            
            // Important: fit map to show all markers
            const locations = Object.values(buildingLocations);
            const bounds = L.latLngBounds(locations);
            fireMap.fitBounds(bounds.pad(0.1));  // Add 10% padding around markers
            
            // Add legend
            addMapLegend();
        }

        // Separate function to add markers
        function addBuildingMarkers() {
            Object.keys(buildingLocations).forEach(building => {
                const markerIcon = createMarkerIcon('normal');
                const marker = L.marker(buildingLocations[building], {
                    icon: markerIcon,
                    draggable: false  // Prevent accidental dragging
                }).addTo(fireMap);
                
                marker.bindPopup(`<b>${building}</b><br>Status: Normal<br>Temperature: Checking...`);
                buildingMarkers[building] = marker;
            });
        }

        // Create custom marker icon based on status
        function createMarkerIcon(status) {
            let iconClass = 'fire-marker-normal';
            if (status === 'siaga') {
                iconClass = 'fire-marker-siaga';
            } else if (status === 'kebakaran') {
                iconClass = 'fire-marker-kebakaran';
            }
            
            return L.divIcon({
                className: iconClass,
                html: `<div style="width: 20px; height: 20px;"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });
        }

        // Add map legend
        function addMapLegend() {
            const legend = L.control({position: 'bottomright'});
            
            legend.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'map-legend');
                div.innerHTML = `
                    <div class="map-legend-title"><b>Status Suhu</b></div>
                    <div class="map-legend-item">
                        <div class="map-legend-color" style="background-color: #32a932;"></div>
                        <div>Normal (< 43°C)</div>
                    </div>
                    <div class="map-legend-item">
                        <div class="map-legend-color" style="background-color: #f59c1a;"></div>
                        <div>Siaga (43°C - 52°C)</div>
                    </div>
                    <div class="map-legend-item">
                        <div class="map-legend-color" style="background-color: #ff3e3e;"></div>
                        <div>Kebakaran (> 53°C)</div>
                    </div>
                `;
                return div;
            };
            
            legend.addTo(fireMap);
        }

        // Update map markers based on temperature status
        function updateMapMarkers() {
            Object.keys(buildingLocations).forEach(building => {
                const card = document.getElementById(`building-${building.toLowerCase().replace(/\s+/g, '-')}`);
                if (card && buildingMarkers[building]) {
                    const statusElement = card.querySelector('.status-label');
                    const tempElement = card.querySelector('.temperature-display');
                    
                    if (statusElement && tempElement) {
                        const status = statusElement.textContent.toLowerCase();
                        const temp = tempElement.textContent;
                        
                        // Update marker icon but don't move the marker
                        const newIcon = createMarkerIcon(status);
                        buildingMarkers[building].setIcon(newIcon);
                        
                        // Update popup content
                        buildingMarkers[building].getPopup().setContent(`
                            <b>${building}</b><br>
                            Status: ${status.toUpperCase()}<br>
                            Temperature: ${temp}
                        `);
                        
                        // Only open popup if kebakaran status and not already open
                        if (status === 'kebakaran' && !buildingMarkers[building].getPopup().isOpen()) {
                            buildingMarkers[building].openPopup();
                        }
                    }
                }
            });
        }

        // Check for fire alerts on initial load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            initMap();
            
            @foreach($buildingData as $building => $data)
                @if($data->status == 'kebakaran')
                    $.gritter.add({
                        title: 'PERINGATAN KEBAKARAN!',
                        text: 'Suhu di {{ $building }} mencapai {{ $data->temperature_value }}°C! Segera lakukan evakuasi!',
                        sticky: false,
                        time: '3000',
                        class_name: 'my-sticky-class gritter-danger'
                    });
                @elseif($data->status == 'siaga')
                    $.gritter.add({
                        title: 'Peringatan Siaga!',
                        text: 'Suhu di {{ $building }} mencapai {{ $data->temperature_value }}°C! Perhatikan kondisi sekitar!',
                        sticky: false,
                        time: '3000',
                        class_name: 'gritter-warning'
                    });
                @endif
            @endforeach
            
            // Update map markers based on initial data
            updateMapMarkers();

            // Start auto-update after initial page load
            setTimeout(updateBuildingTemperatures, 60000); // First auto update after 1 minute
            
            // Fix map rendering when switching tabs
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                if (e.target.getAttribute('href') === '#fire-map-tab') {
                    setTimeout(function() {
                        fireMap.invalidateSize();
                        
                        // Re-fit bounds to show all markers
                        const locations = Object.values(buildingLocations);
                        const bounds = L.latLngBounds(locations);
                        fireMap.fitBounds(bounds.pad(0.1));
                    }, 50);
                }
            });
        });

        // Function to update building temperatures with random values
        function updateBuildingTemperatures() {
            const buildings = document.querySelectorAll('.building-card');
            
            buildings.forEach(card => {
                const buildingTitle = card.querySelector('.card-title').textContent;
                const buildingId = card.id;
                
                // Generate random temperature between 38°C and 59°C
                const temperature = generateRandomTemperature(38, 59);
                const status = getTemperatureStatus(temperature);
                
                // Update temperature display
                const tempDisplay = card.querySelector('.temperature-display');
                tempDisplay.textContent = `${temperature}°C`;
                
                // Update status display
                const statusDisplay = card.querySelector('.status-label');
                statusDisplay.textContent = status.toUpperCase();
                
                // Update timestamp
                const timestampDisplay = card.querySelector('.text-white.mt-3');
                const now = new Date();
                const formattedTime = `${String(now.getDate()).padStart(2, '0')} ${getMonthName(now.getMonth())} ${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
                timestampDisplay.textContent = `Updated: ${formattedTime}`;
                
                // Update card class for status color
                card.className = 'card building-card';
                if (status === 'normal') {
                    card.classList.add('status-normal');
                } else if (status === 'siaga') {
                    card.classList.add('status-siaga');
                    $.gritter.add({
                        title: 'Peringatan Siaga!',
                        text: `Suhu di ${buildingTitle} mencapai ${temperature}°C! Perhatikan kondisi sekitar!`,
                        sticky: false,
                        time: '3000',
                        class_name: 'gritter-warning'
                    });
                } else if (status === 'kebakaran') {
                    card.classList.add('status-kebakaran');
                    $.gritter.add({
                        title: 'PERINGATAN KEBAKARAN!',
                        text: `Suhu di ${buildingTitle} mencapai ${temperature}°C! Segera lakukan evakuasi!`,
                        sticky: false,
                        time: '3000',
                        class_name: 'my-sticky-class gritter-danger'
                    });
                }
            });
            
            // Update map markers
            updateMapMarkers();
            
            // Update global last update timestamp
            const lastUpdateElement = document.querySelector('.last-update-time');
            if (lastUpdateElement) {
                const now = new Date();
                const formattedTime = `${String(now.getDate()).padStart(2, '0')} ${getMonthName(now.getMonth())} ${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
                lastUpdateElement.textContent = formattedTime;
            }
            
            // Schedule next update
            setTimeout(updateBuildingTemperatures, 60000); // Next update after 1 minute
        }

        // Helper function to get month name
        function getMonthName(monthIndex) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[monthIndex];
        }
        
        $(document).ready(function() {
            // Edit user
            $(document).on('click', '.edit-user-btn', function() {
                const userId = $(this).data('id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const role = $(this).data('role');
                
                $('#editUserId').val(userId);
                $('#editName').val(name);
                $('#editEmail').val(email);
                $('#editRole').val(role);
                
                $('#editUserModal').modal('show');
            });
            
            // Show new user form
            $('#addNewUserBtn').click(function() {
                $('#addUserModal').modal('show');
            });
        });
    </script>
@endpush

@section('content')
    <!-- BEGIN breadcrumb -->
    <ol class="breadcrumb float-xl-end">
        <li class="breadcrumb-item"><a href="javascript:;">Home</a></li>
        <li class="breadcrumb-item active">Fire Detection</li>
    </ol>
    <!-- END breadcrumb -->
    
    <!-- BEGIN page-header -->
    <h1 class="page-header">Early Fire Detection System</h1>
    <!-- END page-header -->
    
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    
    <!-- BEGIN tabs -->
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs nav-tabs-v2">
                <li class="nav-item">
                    <a href="#fire-detection-tab" data-bs-toggle="tab" class="nav-link active">
                        <span class="d-sm-none">Fire Detection</span>
                        <span class="d-sm-block d-none">Fire Detection Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#fire-map-tab" data-bs-toggle="tab" class="nav-link">
                        <span class="d-sm-none">Map</span>
                        <span class="d-sm-block d-none">Fire Location Map</span>
                    </a>
                </li>
                @if(auth()->check() && auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="#user-management-tab" data-bs-toggle="tab" class="nav-link">
                        <span class="d-sm-none">User Management</span>
                        <span class="d-sm-block d-none">User Management</span>
                    </a>
                </li>
                @endif
            </ul>
            <div class="tab-content p-0">
                <!-- Fire Detection Tab -->
                <div class="tab-pane fade active show" id="fire-detection-tab">
                    <!-- BEGIN row -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2>Monitor Suhu Gedung</h2>
                                <div>
                                    <span>Last Update: <span class="last-update-time">{{ now()->format('d M Y H:i:s') }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END row -->
                    
                    <!-- BEGIN row -->
                    <div class="row">
                        @foreach($buildingData as $building => $data)
                            <div class="col-xl-4 col-md-6">
                                <div id="building-{{ \Illuminate\Support\Str::slug($building) }}" class="card building-card status-{{ $data->status }}">
                                    <div class="card-body text-center">
                                        <h4 class="card-title">{{ $building }}</h4>
                                        <div class="temperature-display">{{ $data->temperature_value }}°C</div>
                                        <div class="status-label mt-2">{{ strtoupper($data->status) }}</div>
                                        <div class="text-white mt-3">
                                            Updated: {{ $data->timestamp }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- END row -->
                    
                    <!-- BEGIN row -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Keterangan Status</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-success text-white px-3 py-2 me-2">NORMAL</div>
                                                <div>Suhu dibawah 43°C</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-warning text-white px-3 py-2 me-2">SIAGA</div>
                                                <div>Suhu antara 43°C - 52°C</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-danger text-white px-3 py-2 me-2">KEBAKARAN</div>
                                                <div>Suhu antara 53°C - 70°C</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END row -->
                </div>
                
                <!-- Fire Map Tab -->
                <div class="tab-pane fade" id="fire-map-tab">
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2>Peta Lokasi Pemantauan Suhu</h2>
                                <div>
                                    <span>Last Update: <span class="last-update-time">{{ now()->format('d M Y H:i:s') }}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div id="fire-map"></div>
                                    <div class="mt-3">
                                        <p class="text-muted">Peta menampilkan lokasi titik pemantauan suhu. Warna pada peta menunjukkan status suhu terkini pada masing-masing gedung.</p>
                                        <p class="text-muted">Klik pada titik untuk melihat detail status dan suhu.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Management Tab will be rendered here if user is admin -->
                @if(auth()->check() && auth()->user()->isAdmin())
                <div class="tab-pane fade" id="user-management-tab">
                    <!-- User management content -->
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection