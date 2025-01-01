<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Log;

class LoginController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request)
    {
        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->sendError('Invalid login details', 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $user->tokens()->delete();
            $token = $user->createToken('appToken')->plainTextToken;
            
            return $this->sendResponse(['token' => $token]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError($e->getMessage(), 500);
        }
    }

}
