<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends BaseController
{
    public function index()
    {
        // Count the number of users
        $userCount = User::count();

        return $this->sendResponse(['userCount' => $userCount], 'User count retrieved successfully.');
    }
}
