<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussComment extends Model
{
    use HasFactory;
    protected $fillable = [
        'discus_id',
        'user_id',
        'comment',
    ];

    public function discuss()
    {
        return $this->belongsTo(Discuss::class, 'discus_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
