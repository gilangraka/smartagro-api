<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MSeason extends Model
{
    protected $table = 'm_seasons';
    protected $fillable = ['name', 'start_date', 'end_date'];
    protected $hidden = ['created_at', 'updated_at'];

    public function recommendation(){
        return $this->hasMany(PlantRecommendation::class, 'season_id');
    }
}
