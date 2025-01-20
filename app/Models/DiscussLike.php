<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscussLike extends Model
{
    protected $fillable = ['discuss_id', 'user_id'];
}
