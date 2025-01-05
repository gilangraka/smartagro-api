<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prevention extends Model
{
    protected $fillable = [
        'disease_name',
        'prevention',
    ];

    public function treatment()
    {
        return $this->hasOne(Treatment::class);
    }
}
