<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Mail\SendCodeResetPassword;
use App\Models\ResetCodePassword;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends BaseController
{
    public function __invoke(ForgotPasswordRequest $request)
    {
        ResetCodePassword::where('email', $request->email)->delete();

        $data = [
            'email' => request()->email,
            'code' => mt_rand(100000, 999999),
            'created_at' => now()
        ];
        $codeData = ResetCodePassword::create($data);

        Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));

        return $this->sendResponse(null);
    }
}
