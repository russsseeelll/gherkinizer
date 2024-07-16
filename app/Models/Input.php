<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Input extends Model
{
    use HasFactory;

    protected $fillable = ['system', 'title', 'description', 'additional_inputs'];

    protected $casts = [
        'additional_inputs' => 'array',
    ];

    public function outputs()
    {
        return $this->hasMany(Output::class);
    }
}
