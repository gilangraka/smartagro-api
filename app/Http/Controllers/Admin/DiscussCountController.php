<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Discuss;
use Illuminate\Http\Request;

class DiscussCountController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $Discuss = Discuss::count();

        return $this->sendResponse(['plant_disease_count'=>$Discuss],'discudd count retrieved successfully.');
    }
}
