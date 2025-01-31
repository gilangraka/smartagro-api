<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

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

    public static $snakeAttributes = false;

    public function getImageUrlAttribute($value)
    {
        return "https://smartagro-api.sightway.my.id/storage/uploads/discusses/{$value}";
    }

    public function discuss()
    {
        return $this->belongsTo(Discuss::class, 'discuss_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function discussComments()
    {
        return $this->hasMany(DiscussComment::class, 'discus_id');
    }

    public function likes()
    {
        return $this->hasMany(DiscussLike::class, 'discuss_id');
    }

    public function likedByAuthUser()
    {
        return $this->hasOne(DiscussLike::class)->where('user_id', Auth::id());
    }
}
