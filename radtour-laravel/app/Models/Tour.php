<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start', 'destination', 'outbound', 'return_route', 'stops', 'settings', 'distance_meters', 'duration_seconds'];

    protected function casts(): array
    {
        return [
            'start' => 'array',
            'destination' => 'array',
            'outbound' => 'array',
            'return_route' => 'array',
            'stops' => 'array',
            'settings' => 'array',
        ];
    }
}
