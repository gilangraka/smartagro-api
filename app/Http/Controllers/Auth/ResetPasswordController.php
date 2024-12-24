<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\ResetCodePassword;
use App\Models\User;

class ResetPasswordController extends BaseController
{
    public function __invoke(ResetPasswordRequest $request)
    {
        $passwordReset = ResetCodePassword::firstWhere('code', $request->code);

        if ($passwordReset->isExpire()) return $this->sendError('code expired');

        $user = User::firstWhere('email', $passwordReset->email);

        $user->update($request->only('password'));

        $passwordReset->delete();

        return $this->sendResponse(null);
    }
}
