<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $fillable = [
        'building_name',
        'temperature',
        'status',
        'recorded_at'
    ];

    public $timestamps = true;
}
