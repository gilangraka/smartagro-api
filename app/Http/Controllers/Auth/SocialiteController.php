<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends BaseController
{
    public function redirectToProvider($provider = 'google')
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider = 'google')
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = User::firstOrCreate(
                [
                    'email' => $socialUser->getEmail(),
                    'name'  => $socialUser->getName(),
                    'password' => bcrypt($socialUser->getEmail())
                ]
            );

            $token = $user->createToken('appToken')->plainTextToken;
            return $this->sendResponse(['token' => $token]);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
