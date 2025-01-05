<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected $fillable = [
        'disease_name',
        'chemical_id',
        'biological_id',
        'prevention_id',
    ];

    public function chemical()
    {
        return $this->belongsTo(Chemical::class);
    }

    public function biological()
    {
        return $this->belongsTo(Biological::class);
    }

    public function prevention()
    {
        return $this->belongsTo(Prevention::class);
    }

    public function historyDisease()
    {
        return $this->hasMany(HistoryDisease::class);
    }
}
