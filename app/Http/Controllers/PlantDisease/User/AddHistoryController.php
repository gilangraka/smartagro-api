<?php

namespace App\Http\Controllers\PlantDisease\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\HistoryDisease;
use App\Http\Controllers\BaseController;
use App\Models\Biological;
use App\Models\Chemical;
use App\Models\Prevention;
use App\Models\Treatment;

class AddHistoryController extends BaseController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'imageInput' => 'required|file|mimes:jpeg,jpg,png|max:5120', // Max 5 MB
                'lat' => 'required|numeric',
                'long' => 'required|numeric',
            ]);

            $file = $request->file('imageInput');
            $filePath = $file->store('plant_diseases', 'public');
            $absoluteFilePath = storage_path('app/public/' . $filePath);
            $fileUrl = asset('storage/' . $filePath);

            $userId = Auth::id();
            if (!$userId) {
                Storage::delete($filePath); 
                return $this->sendError('User not authenticated', 401);
            }

            $response = Http::withHeaders([
                'Api-Key' => env('PLANT_ID_API_KEY'),
            ])->attach(
                'images', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName()
            )->post('https://plant.id/api/v3/health_assessment?details=local_name,url,treatment,classification,common_names', [
                'latitude' => $validatedData['lat'],
                'longitude' => $validatedData['long'],
                'similar_images' => 'true',
            ]);

            if ($response->failed()) {
                Log::error('Plant.ID API Error', ['status' => $response->status()]);
                Storage::delete($filePath); 
                return $this->sendError('Failed to get response from Plant.ID API', $response->status());
            }

            $responseData = $response->json();
            $accessToken = $responseData['access_token'] ?? null;
            $imageUrl = $responseData['input']['images'][0] ?? null;
            $disease = $responseData['result']['disease']['suggestions'][0]['name'] ?? null;
            $similar_image = $responseData['result']['disease']['suggestions'][0]['similar_images'][0]['url'] ?? null;
            $probability = $responseData['result']['disease']['suggestions'][0]['probability'] ?? null;
            $redundant = $responseData['result']['disease']['suggestions'][0]['redundant'] ?? null;

            $treatment = $responseData['result']['disease']['suggestions'][0]['details']['treatment'];

            $treatment_chemical = $treatment['chemical'] ?? null;
            if($treatment_chemical) {
                $treatment_chemical = implode(' ', $treatment_chemical);
                log::info($treatment_chemical);
            }
            $treatment_biological = $treatment['biological'] ?? null;
            if($treatment_biological) {
                $treatment_biological = implode(' ', $treatment_biological);
                log::info($treatment_biological);
            }
            $treatment_prevention = $treatment['prevention'] ?? null;
            if($treatment_prevention) {
                $treatment_prevention = implode(' ', $treatment_prevention);
                log::info($treatment_prevention);
            }

            $chemical = Chemical::create([
                'disease_name' => $disease,
                'treatment' => $treatment_chemical,
            ]);

            $biological = Biological::create([
                'disease_name' => $disease,
                'treatment' => $treatment_biological,
            ]);

            $prevention = Prevention::create([
                'disease_name' => $disease,
                'treatment' => $treatment_prevention,
            ]);

            $treatment = Treatment::create([
                'disease_name' => $disease,
                'chemical_id' => $chemical->id,
                'biological_id' => $biological->id,
                'prevention_id' => $prevention->id,
            ]);


            $historyDisease = HistoryDisease::create([
                'user_id' => $userId,
                'imageUrl' => $imageUrl,
                'lat' => $validatedData['lat'],
                'long' => $validatedData['long'],
                'disease' => $disease,
                'probability' => $probability,
                'similar_images' => $similar_image, 
                'treatment_id' => $treatment->id,
                'is_redundant' => $redundant,
            ]);

            Storage::delete($filePath);

            return $this->sendResponse($historyDisease, 'Plant disease record created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', 422);
        } catch (\Exception $e) {
            Log::error('Unexpected Error', ['error' => $e->getMessage()]);
            if (isset($filePath)) {
                Storage::delete($filePath); 
            }
            return $this->sendError('An unexpected error occurred. Please try again.', 500);
        }
    }
}
