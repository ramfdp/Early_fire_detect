<?php

namespace App\Http\Controllers;

use App\Models\Temperature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class TemperatureController extends Controller
{
    /**
     * Display the dashboard with temperature data
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $buildings = ['Wisma Krakatau', 'CM-1', 'CM-2', 'CM-3', 'Antartika'];
        
        $buildingData = [];
        
        foreach ($buildings as $building) {
            // Get the latest temperature for each building
            $latestTemperature = Temperature::where('building_name', $building)
                ->orderBy('created_at', 'desc')
                ->first();
            
            // If no data exists yet, create sample data
            if (!$latestTemperature) {
                $tempValue = rand(25, 40); // Random normal temperature
                $latestTemperature = new Temperature([
                    'building_name' => $building,
                    'temperature_value' => $tempValue,
                    'status' => Temperature::getStatus($tempValue),
                    'timestamp' => now(),
                ]);
                $latestTemperature->save();
            }
            
            $buildingData[$building] = $latestTemperature;
        }
        
        // Get temperature trend for the last 24 hours
        $temperatureTrends = DB::table('temperatures')
            ->select(DB::raw('building_name, AVG(temperature_value) as avg_temp, HOUR(created_at) as hour'))
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('building_name', 'hour')
            ->orderBy('hour')
            ->get()
            ->groupBy('building_name');
        
            return view('dashboard-v2', [
                'buildingData' => $buildingData,
                'temperatureTrends' => $temperatureTrends
            ]);            
    }
    
    /**
     * Update temperature data for a building
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTemperature(Request $request)
    {
        $request->validate([
            'building_name' => 'required|string',
            'temperature_value' => 'required|numeric',
        ]);
        
        $building = $request->input('building_name');
        $temperatureValue = $request->input('temperature_value');
        $status = Temperature::getStatus($temperatureValue);
        
        Temperature::create([
            'building_name' => $building,
            'temperature_value' => $temperatureValue,
            'status' => $status,
            'timestamp' => now(),
        ]);
        
        return redirect()->route('dashboard')->with('message', 'Temperature updated successfully');
    }
    
    /**
     * API to get the latest temperature data for all buildings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestTemperatures()
    {
        $buildings = ['Wisma Krakatau', 'CM-1', 'CM-2', 'CM-3', 'Antartika'];
        
        $data = [];
        
        foreach ($buildings as $building) {
            $latestTemperature = Temperature::where('building_name', $building)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestTemperature) {
                $data[$building] = [
                    'temperature' => $latestTemperature->temperature_value,
                    'status' => $latestTemperature->status,
                    'timestamp' => $latestTemperature->timestamp,
                ];
            }
        }
        
        return response()->json($data);
    }
    
    /**
     * Generate random temperature data for testing
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateRandomData()
    {
        $buildings = ['Wisma Krakatau', 'CM-1', 'CM-2', 'CM-3', 'Antartika'];
        
        foreach ($buildings as $building) {
            $temperatureValue = rand(30, 60); // Random temperature between 30-60
            $status = Temperature::getStatus($temperatureValue);
            
            Temperature::create([
                'building_name' => $building,
                'temperature_value' => $temperatureValue,
                'status' => $status,
                'timestamp' => now(),
            ]);
        }
        return redirect()->route('dashboard')->with('message', 'Random data generated successfully');
    }
}