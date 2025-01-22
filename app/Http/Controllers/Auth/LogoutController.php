<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class LogoutController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->sendResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
