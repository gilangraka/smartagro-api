<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RegisterController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            Log::info('RegisterController: ' . $validatedData['name'] . ' is trying to register.');

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

            $token = $user->createToken('authToken')->plainTextToken;

            

            return $this->sendResponse(['user' => $user, 'token' => $token], 'User registered successfully.');
        } catch (BadRequestHttpException $e) {
            Log::error('RegisterController: ' . $e->getMessage());
            return $this->sendError($e->getMessage(), 400);
        } catch (ValidationException $e) {
            Log::error('RegisterController: ' . $e->getMessage());
            return $this->sendError($e->errors(), 422);
        } catch (Exception $e) {
            Log::error('RegisterController: ' . $e->getMessage());
            return $this->sendError('An unexpected error occurred.', 500);
        } 

    }
}