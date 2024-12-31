<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MSeason;
use Illuminate\Support\Facades\Validator;

class SeasonController extends Controller
{
    public function current_season(Request $request)
    {
        try{
            $current_date = date('Y-m-d');
            $season = MSeason::where('start_date', '<=', $current_date)->where('end_date', '>=', $current_date)->first();

            if (!$season) {
                return response()->json(['error' => 'Season not found'], 404);
            } else {
                return response()->json($season, 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Season not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function index(Request $request)
    {
        try{
            $seasons = MSeason::all();

            if ($seasons->isEmpty()) {
                return response()->json(['error' => 'Seasons not found'], 404);
            } else {
                return response()->json($seasons, 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Seasons not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function show(Request $request, $id)
    {
        try{
            $season = MSeason::find($id);

            if (!$season) {
                return response()->json(['error' => 'Season not found'], 404);
            } else {
                return response()->json($season, 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Season not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if (MSeason::where('name', $request->name)->exists()) {
                return response()->json(['error' => 'Season already exists'], 409);
            } else{
                $season = MSeason::create([
                    'name' => $request->name,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ]);

                return response()->json($season, 201);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Season creation failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            } else {
                $season = MSeason::find($id);

                if (!$season) {
                    return response()->json(['error' => 'Season not found'], 404);
                } else {
                    $season->name = $request->name;
                    $season->start_date = $request->start_date;
                    $season->end_date = $request->end_date;
                    $season->save();
                }

            }

            return response()->json($season, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Season update failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $season = MSeason::find($id);

            if (!$season) {
                return response()->json(['error' => 'Season not found'], 404);
            } else {
                $season->delete();

                return response()->json(['message' => 'Season deleted'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Season deletion failed', 'message' => $e->getMessage()], 500);
        }
    }
    
}
