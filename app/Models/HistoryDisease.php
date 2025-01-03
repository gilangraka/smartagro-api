<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryDisease extends Model
{
    protected $fillable = [
        'user_id',
        'imageUrl',
        'lat',
        'long',
        'disease',
        'is_redundant',
        'probability',
        'treatment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
