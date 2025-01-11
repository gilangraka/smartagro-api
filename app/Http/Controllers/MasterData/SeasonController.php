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
        try {
            $current_date = date('Y-m-d');
            $season = MSeason::where('start_date', '<=', $current_date)
                             ->where('end_date', '>=', $current_date)
                             ->first();

            if (!$season) {
                return response()->json(['error' => 'Season not found for the current date'], 404);
            }

            return response()->json($season, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching the current season',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $seasons = MSeason::all();

            if ($seasons->isEmpty()) {
                return response()->json(['error' => 'No seasons found in the database'], 404);
            }

            return response()->json($seasons, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching the seasons',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $season = MSeason::find($id);

            if (!$season) {
                return response()->json(['error' => "Season with ID $id not found"], 404);
            }

            return response()->json($season, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching the season details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if (MSeason::where('name', $request->name)->exists()) {
                return response()->json(['error' => 'A season with this name already exists'], 409);
            }

            $season = MSeason::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return response()->json($season, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while creating the season',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_date' => 'required|date|before_or_equal:end_date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $season = MSeason::find($id);

            if (!$season) {
                return response()->json(['error' => "Season with ID $id not found"], 404);
            }

            $season->update([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return response()->json($season, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while updating the season',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $season = MSeason::find($id);

            if (!$season) {
                return response()->json(['error' => "Season with ID $id not found"], 404);
            }

            $season->delete();

            return response()->json(['message' => 'Season deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the season',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
