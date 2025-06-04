@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Riwayat Suhu</h1>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Nama Gedung</th>
                <th>Suhu (°C)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($histories as $row)
            <tr>
                <td>{{ $row->timestamp }}</td>
                <td>{{ $row->building_name }}</td>
                <td>{{ $row->temperature_value }}</td>
                <td>
                    @if($row->status === 'bahaya')
                        <span class="badge bg-danger">{{ $row->status }}</span>
                    @elseif($row->status === 'siaga')
                        <span class="badge bg-warning text-dark">{{ $row->status }}</span>
                    @else
                        <span class="badge bg-success">{{ $row->status }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
