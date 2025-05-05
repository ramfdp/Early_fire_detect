<?php

namespace Database\Seeders;

use App\Models\Temperature;
use Illuminate\Database\Seeder;

class TemperatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $buildings = ['Wisma Krakatau', 'CM-1', 'CM-2', 'CM-3', 'Antartika'];
        
        foreach ($buildings as $building) {
            // Generate some historical data
            for ($i = 0; $i < 24; $i++) {
                $temperatureValue = rand(30, 45); // Most in normal to siaga range
                $status = Temperature::getStatus($temperatureValue);
                
                Temperature::create([
                    'building_name' => $building,
                    'temperature_value' => $temperatureValue,
                    'status' => $status,
                    'timestamp' => now()->subHours(24 - $i),
                    'created_at' => now()->subHours(24 - $i),
                    'updated_at' => now()->subHours(24 - $i)
                ]);
            }
            
            // Generate current data
            $temperatureValue = rand(30, 45);
            $status = Temperature::getStatus($temperatureValue);
            
            Temperature::create([
                'building_name' => $building,
                'temperature_value' => $temperatureValue,
                'status' => $status,
                'timestamp' => now(),
            ]);
        }
    }
}