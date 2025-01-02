<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;


class LoginController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                Log::warning('Failed login attempt', ['email' => $request->email]);
                return $this->sendError('Invalid email or password.', 401);
            }

            $user = Auth::user();

            $token = $user->createToken('AuthToken')->plainTextToken;

            Log::info('User logged in successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return $this->sendResponse([
                'user' => $user,
                'token' => $token,
            ], 'Login successful.');
        } 
        
        catch (ValidationException $e) {
            Log::error('Validation error during login', ['errors' => $e->errors()]);
            return $this->sendError($e->errors(), 422);
        } 
        
        catch (Exception $e) {
            Log::critical('Unexpected error during login', ['message' => $e->getMessage()]);
            return $this->sendError('An unexpected error occurred.', 500);
        }
    }

}
