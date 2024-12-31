<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            } else{
                $user = User::where('email', $request->email)->first();
                $token = $user->createToken('auth_token')->plainTextToken;

                if (!$token){
                    return response()->json(['error' => 'Unauthorized'], 401);
                } else {
                    return response()->json([
                        'data' => Auth::user(),
                        'token' => $token,
                        'token_type' => 'Bearer'
                    ]);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
