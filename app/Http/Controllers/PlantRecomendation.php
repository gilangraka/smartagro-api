<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MSeason;
use App\Models\PlantRecommendation;


class PlantRecomendation extends Controller
{
    public function index(Request $request)
    {
        try {
            $plant_recommendations = PlantRecommendation::all();

            if ($plant_recommendations->isEmpty()) {
                return response()->json(['error' => 'Plant recommendations not found'], 404);
            } else {
                return response()->json($plant_recommendations);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant recommendations not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $season = MSeason::find($request->season_id);

            if (!$season) {
                return response()->json(['error' => 'Season not found'], 404);
            } else{
                $plant_recommendation = new PlantRecommendation(
                    $request->all()
                );
                $plant_recommendation->name = $request->name;
                $plant_recommendation->imageUrl = $request->imageUrl;
                $plant_recommendation->season_id = $request->season_id;
                $plant_recommendation->save();

                return response()->json($plant_recommendation, 201);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant recommendation not created', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $plant_recommendation = PlantRecommendation::find($id);

            if (!$plant_recommendation) {
                return response()->json(['error' => 'Plant recommendation not found'], 404);
            } else{
                $plant_recommendation->name = $request->name;
                $plant_recommendation->imageUrl = $request->imageUrl;
                $plant_recommendation->save();

                return response()->json($plant_recommendation, 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant recommendation not updated', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $plant_recommendation = PlantRecommendation::find($id);

            if (!$plant_recommendation) {
                return response()->json(['error' => 'Plant recommendation not found'], 404);
            } else {
                $plant_recommendation->delete();

                return response()->json(['message' => 'Plant recommendation deleted'], 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant recommendation not deleted', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $season_id)
    {
        try {
            $season = MSeason::find($season_id);

            if (!$season) {
                return response()->json(['error' => 'Season not found'], 404);
            } else{
                if ($season->plant_recommendations->isEmpty()) {
                    return response()->json(['error' => 'Plant recommendations not found'], 404);
            } else {
                $plant_recommendations = $season->plant_recommendations()
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            return response()->json($plant_recommendations);
            }
        }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant recommendations not found', 'message' => $e->getMessage()], 404);
        }
    }
}
