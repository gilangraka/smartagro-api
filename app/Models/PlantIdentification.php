<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantIdentification extends Model
{
    protected $table = 'plant_identifications';

    protected $fillable = [
        'user_id',
        'image',
        'plant_name',
        'probability',
        'similar_images',
        'lat',
        'long',
        'explaination',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
