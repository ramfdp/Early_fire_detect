@extends('layouts.default')

@section('title', 'Fire Detection Dashboard')

@push('css')
    <link href="/assets/plugins/jvectormap-next/jquery-jvectormap.css" rel="stylesheet" />
    <link href="/assets/plugins/datepickk/dist/datepickk.min.css" rel="stylesheet" />
    <link href="/assets/plugins/gritter/css/jquery.gritter.css" rel="stylesheet" />
    <link href="/assets/plugins/nvd3/build/nv.d3.css" rel="stylesheet" />
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
    </style>
@endpush

@push('scripts')
    <script src="/assets/plugins/d3/d3.min.js"></script>
    <script src="/assets/plugins/nvd3/build/nv.d3.js"></script>
    <script src="/assets/plugins/jvectormap-next/jquery-jvectormap.min.js"></script>
    <script src="/assets/plugins/jvectormap-content/world-mill.js"></script>
    <script src="/assets/plugins/datepickk/dist/datepickk.min.js"></script>
    <script src="/assets/plugins/gritter/js/jquery.gritter.js"></script>
    <script>
        // Check for fire alerts
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($buildingData as $building => $data)
                @if($data->status == 'kebakaran')
                    $.gritter.add({
                        title: 'PERINGATAN KEBAKARAN!',
                        text: 'Suhu di {{ $building }} mencapai {{ $data->temperature_value }}°C! Segera lakukan evakuasi!',
                        sticky: true,
                        time: '',
                        class_name: 'my-sticky-class gritter-danger'
                    });
                @elseif($data->status == 'siaga')
                    $.gritter.add({
                        title: 'Peringatan Siaga!',
                        text: 'Suhu di {{ $building }} mencapai {{ $data->temperature_value }}°C! Perhatikan kondisi sekitar!',
                        sticky: false,
                        time: '5000',
                        class_name: 'gritter-warning'
                    });
                @endif
            @endforeach
        });

        // Refresh data every 30 seconds
        setInterval(function() {
            fetch('/api/temperatures')
                .then(response => response.json())
                .then(data => {
                    Object.keys(data).forEach(building => {
                        const buildingData = data[building];
                        const card = document.getElementById(`building-${building.replace(/\s+/g, '-').toLowerCase()}`);
                        const tempDisplay = card.querySelector('.temperature-display');
                        const statusDisplay = card.querySelector('.status-label');
                        
                        // Update temperature
                        tempDisplay.textContent = `${buildingData.temperature}°C`;
                        
                        // Update status
                        statusDisplay.textContent = buildingData.status.toUpperCase();
                        
                        // Update card class
                        card.className = 'card building-card';
                        if (buildingData.status === 'normal') {
                            card.classList.add('status-normal');
                        } else if (buildingData.status === 'siaga') {
                            card.classList.add('status-siaga');
                            $.gritter.add({
                                title: 'Peringatan Siaga!',
                                text: `Suhu di ${building} mencapai ${buildingData.temperature}°C! Perhatikan kondisi sekitar!`,
                                sticky: false,
                                time: '5000',
                                class_name: 'gritter-warning'
                            });
                        } else if (buildingData.status === 'kebakaran') {
                            card.classList.add('status-kebakaran');
                            $.gritter.add({
                                title: 'PERINGATAN KEBAKARAN!',
                                text: `Suhu di ${building} mencapai ${buildingData.temperature}°C! Segera lakukan evakuasi!`,
                                sticky: true,
                                time: '',
                                class_name: 'my-sticky-class gritter-danger'
                            });
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        }, 30000);
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
    
    <!-- BEGIN row -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Monitor Suhu Gedung</h2>
                <div>
                    <a href="{{ route('generate.random.data') }}" class="btn btn-sm btn-primary">Generate Test Data</a>
                    <span class="ms-2">Last Update: {{ now()->format('d M Y H:i:s') }}</span>
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
    
    <!-- Manual temperature input form for testing -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Test Temperature Input</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('update.temperature') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="building_name">Pilih Gedung</label>
                                    <select class="form-control" name="building_name" id="building_name" required>
                                        <option value="">Pilih Gedung</option>
                                        <option value="Wisma Krakatau">Wisma Krakatau</option>
                                        <option value="CM-1">CM-1</option>
                                        <option value="CM-2">CM-2</option>
                                        <option value="CM-3">CM-3</option>
                                        <option value="Antartika">Antartika</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="temperature_value">Suhu (°C)</label>
                                    <input type="number" class="form-control" name="temperature_value" id="temperature_value" step="0.1" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-3">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection