<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected $fillable = [
        'chemical_treatment',
        'biological_treatment',
        'prevention_treatment',
    ];

    public function historyDisease()
    {
        return $this->hasMany(HistoryDisease::class);
    }
}
