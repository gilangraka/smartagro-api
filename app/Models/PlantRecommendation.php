<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantRecommendation extends Model
{
    protected $table = 'plant_recommendations';
    protected $fillable = ['plant_id', 'season_id', 'recommendation'];
    protected $hidden = ['created_at', 'updated_at'];

    public function season(){
        return $this->belongsTo(MSeason::class, 'season_id');
    }
}
