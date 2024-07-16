<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Output extends Model
{
    use HasFactory;

    protected $fillable = ['input_id', 'questions', 'answers', 'result'];

    protected $casts = [
        'questions' => 'array',
        'answers' => 'array',
    ];

    public function input()
    {
        return $this->belongsTo(Input::class);
    }
}
