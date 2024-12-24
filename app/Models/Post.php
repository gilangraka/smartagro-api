<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Post extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'imageUrl'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function category()
    {
        return $this->belongsTo(MPostCategory::class, 'category_id');
    }
    public function postComments()
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }
}
