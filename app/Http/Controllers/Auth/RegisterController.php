<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class RegisterController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'username' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'username' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            $token = $user->createToken('authToken')->plainTextToken;

            return $this->sendResponse(['user' => $user, 'token' => $token], 'User registered successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('An unexpected error occurred.', 500);
        }
    }
}