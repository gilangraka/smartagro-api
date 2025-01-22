<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordController extends BaseController
{
    public function __invoke(ResetPasswordRequest $request)
    {
        $passwordReset = ResetCodePassword::where('code', $request->code)->first();
        if (!$passwordReset) return $this->sendError('Invalid code!', 404);

        $user = User::firstWhere('email', $passwordReset->email);

        $user->update($request->only('password'));

        $passwordReset->delete();

        return $this->sendResponse(null);
    }
}
