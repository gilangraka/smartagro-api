<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\Post;

class PostCountController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $Post = Post::count();

        return $this->sendResponse(['plant_disease_count'=>$Post],'post count retrieved successfully.');
    }
}
