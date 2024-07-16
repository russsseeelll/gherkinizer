<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemInfo extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'stack', 'features'];

    protected $casts = [
        'features' => 'array',
    ];
}
