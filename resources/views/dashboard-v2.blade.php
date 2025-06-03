@extends('layouts.default')

@section('title', 'Fire Detection Dashboard')

@push('css')
     <link href="{{url_css()}}/assets/plugins/jvectormap-next/jquery-jvectormap.css" rel="stylesheet" />
     <link href="{{url_css()}}/assets/plugins/datepickk/dist/datepickk.min.css" rel="stylesheet" />
     <link href="{{url_css()}}/assets/plugins/gritter/css/jquery.gritter.css" rel="stylesheet" />
     <link href="{{url_css()}}/assets/plugins/nvd3/build/nv.d3.css" rel="stylesheet" />
     <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
     <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet" type="text/css" />
 @endpush

 @push('scripts')
  <script src="{{url_css()}}/assets/plugins/d3/d3.min.js"></script>
     <script src="{{url_css()}}/assets/plugins/nvd3/build/nv.d3.js"></script>
     <script src="{{url_css()}}/assets/plugins/jvectormap-next/jquery-jvectormap.min.js"></script>
     <script src="{{url_css()}}/assets/plugins/jvectormap-content/world-mill.js"></script>
     <script src="{{url_css()}}/assets/plugins/datepickk/dist/datepickk.min.js"></script>
     <script src="{{url_css()}}/assets/plugins/gritter/js/jquery.gritter.js"></script>
     <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script> 
     <script src="{{ asset('assets/js/map.js') }}"></script>
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