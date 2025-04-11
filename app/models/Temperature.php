<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temperature extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_name',
        'temperature_value',
        'status',
        'timestamp',
    ];

    /**
     * Get temperature status based on value
     * 
     * @param float $temperature
     * @return string
     */
    public static function getStatus($temperature)
    {
        if ($temperature >= 53 && $temperature <= 70) {
            return 'kebakaran';
        } elseif ($temperature > 43) {
            return 'siaga';
        } else {
            return 'normal';
        }
    }
}