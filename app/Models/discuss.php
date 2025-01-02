<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Discuss extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'imageUrl',
    ];

    public function discuss()
    {
        return $this->belongsTo(Discuss::class, 'discuss_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function discussComments(){
        return $this->hasMany(DiscussComment::class, 'discus_id');
    }
}
