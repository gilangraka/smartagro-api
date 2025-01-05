<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biological extends Model
{
    protected $fillable = [
        'disease_name',
        'treatment',
    ];

    public function treatment()
    {
        return $this->hasOne(Treatment::class);
    }
}
