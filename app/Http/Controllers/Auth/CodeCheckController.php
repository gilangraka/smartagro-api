<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\CodeCheckRequest;
use App\Models\ResetCodePassword;

class CodeCheckController extends BaseController
{
    public function __invoke(CodeCheckRequest $request)
    {
        $passwordReset = ResetCodePassword::firstWhere('code', $request->code);

        if ($passwordReset->isExpire()) return $this->sendError('code expired');

        return $this->sendResponse(['code' => $passwordReset->code]);
    }
}
