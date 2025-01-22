<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\HistoryDisease;
use Illuminate\Http\Request;

class PlantController extends BaseController
{
    public function plant_disease(){
        $disease = HistoryDisease::count();

        return $this->sendResponse(['plant_disease_count'=>$disease],'plant disease count retrieved successfully.');
    }
}
