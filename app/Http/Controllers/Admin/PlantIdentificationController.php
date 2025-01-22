<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\PlantIdentification;
use Illuminate\Http\Request;

class PlantIdentificationController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $identification = PlantIdentification::count();

        return $this->sendResponse(['plant_disease_count'=>$identification],'plant identification count retrieved successfully.');
    }
}
