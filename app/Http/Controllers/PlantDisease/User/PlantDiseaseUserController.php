<?php

namespace App\Http\Controllers\PlantDisease\User;

use App\Http\Controllers\BaseController;
use App\Models\HistoryDisease;
use Illuminate\Http\Request;

class PlantDiseaseUserController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $params = $request->validate([
                'q' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1',
                'order_direction' => 'nullable|in:asc,desc',
            ]);

            $search = $params['q'] ?? null;
            $perPage = $params['per_page'] ?? 10;
            $orderDirection = $params['order_direction'] ?? 'desc';

            $plant_diseases = HistoryDisease::when($search, function ($query, $search) {
                    return $query->where('disease', 'like', "%$search%");
                })
                ->orderBy('created_at', $orderDirection)
                ->paginate($perPage);

            return response()->json($plant_diseases, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch plant diseases', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $plant_disease = HistoryDisease::find($id);

            if (!$plant_disease) {
                return response()->json(['error' => "Plant disease with ID $id not found"], 404);
            }

            return response()->json($plant_disease, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant disease not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $plant_disease = HistoryDisease::find($id);

            if (!$plant_disease) {
                return response()->json(['error' => 'Plant disease not found'], 404);
            } else{
                $plant_disease->update($request->all());

                return response()->json($plant_disease, 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant disease not updated', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $plant_disease = HistoryDisease::find($id);

            if (!$plant_disease) {
                return response()->json(['error' => 'Plant disease not found'], 404);
            } else{
                $plant_disease->delete();

                return response()->json(['message' => 'Plant disease deleted successfully'], 200);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Plant disease not deleted', 'message' => $e->getMessage()], 500);
        }
    }
}
