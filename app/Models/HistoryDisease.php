<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryDisease extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'image',
        'lat',
        'long',
        'disease',
        // 'is_redundant',
        'probability',
        'similar_images',
        'treatment_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
