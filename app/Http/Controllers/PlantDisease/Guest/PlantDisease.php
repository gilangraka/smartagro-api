<?php

namespace App\Http\Controllers\PlantDisease\Guest;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class PlantDisease extends BaseController
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }
    
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $filePath = null;
        try {
            $validatedData = $request->validate([
                'image' => 'required|file|mimes:jpeg,jpg,png|max:5120',
                'address' => 'required|string',
            ]);

            $address = $validatedData['address'];

            $userAgent = $request->header('User-Agent');

        $response = $this->client->get('https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $address,
                'format' => 'json'
            ],
            'headers' => [
                'User-Agent' => $userAgent
            ]
        ]);

        $data = json_decode($response->getBody(), true);

            $latitude = $data[0]['lat'];
            $longitude = $data[0]['lon'];
            Log::info('Extracted Coordinates', ['latitude' => $latitude, 'longitude' => $longitude]);

            // Store image file
            $file = $request->file('image');
            $filePath = $file->store('plant_diseases', 'public');
            $fileUrl = asset('storage/' . $filePath);

            // Cache response based on coordinates and image
            $cacheKey = md5($file->getRealPath() . $latitude . $longitude);
            $responseData = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($file, $latitude, $longitude) {
                return Http::withHeaders([
                    'Api-Key' => env('PLANT_ID_API_KEY'),
                ])->attach('images', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
                  ->post('https://plant.id/api/v3/health_assessment?details=local_name,url,treatment,classification,common_names', [
                      'latitude' => $latitude,
                      'longitude' => $longitude,
                      'similar_images' => 'true'
                  ])->json();
            });

            Log::info('API Response', ['response' => $responseData]);

            if (!isset($responseData['result']['disease']['suggestions'][0])) {
                Log::error('Invalid API Response', ['response' => $responseData]);
                Storage::delete($filePath); 
                return $this->sendError('Failed to process response from Plant.ID API', 500);
            }

            // Extract disease information
            $disease = $responseData['result']['disease']['suggestions'][0]['name'] ?? null;
            $probability = $responseData['result']['disease']['suggestions'][0]['probability'] ?? null;
            // $redundant = $responseData['result']['disease']['suggestions'][0]['redundant'] ?? null;
            $treatment = $responseData['result']['disease']['suggestions'][0]['details']['treatment'] ?? [];
            $similar_image = $responseData['result']['disease']['suggestions'][0]['similar_images'][0]['url'] ?? null;
            $image_url = $responseData['input']['images'][0] ?? null;

            // Format treatment data
            $treatment_chemical = $treatment['chemical'] ? implode(' ', $treatment['chemical']) : null;
            $treatment_biological = $treatment['biological'] ? implode(' ', $treatment['biological']) : null;
            $treatment_prevention = $treatment['prevention'] ? implode(' ', $treatment['prevention']) : null;

            $combinedText = $treatment_chemical . "\n\n" . $treatment_biological . "\n\n" . $treatment_prevention;

            // Get translated treatment info
            $conversation = Http::withHeaders([
                'Api-Key' => env('PLANT_ID_API_KEY'),
            ])->post('https://plant.id/api/v3/identification/'.$responseData['access_token'].'/conversation', [
                "question" => $combinedText . " Translate this text into Indonesian, keeping the context related to plants and agriculture while maintaining proper capitalization.",
                "prompt" => "Ensure the translation is precise and avoids unnecessary explanations.",
                "temperature"=> 0.5,
                "app_name"=> "AgroLens"
            ]);

            if ($conversation->failed()) {
                Log::error('Plant.ID API Error', ['status' => $conversation->status()]);
                Storage::delete($filePath); 
                return $this->sendError('Failed to get response from Plant.ID API', $conversation->status());
            }

            // Extract and update translated treatment details
            $translatedText = explode("\n\n", $conversation['messages'][1]['content'] ?? '');
            $treatment_chemical = $translatedText[0] ?? null;
            $treatment_biological = $translatedText[1] ?? null;
            $treatment_prevention = $translatedText[2] ?? null;

            $treatment = [
                'disease_name' => $disease,
                'chemical_treatment' => $treatment_chemical,
                'biological_treatment' => $treatment_biological,
                'prevention_treatment' => $treatment_prevention,
            ];

            // Prepare history disease data
            $historyDisease = [
                'image' => $image_url,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'disease' => $disease,
                'probability' => $probability,
                'similar_images' => $similar_image, 
                'treatment_id' => $treatment,
                // 'is_redundant' => $redundant,
            ];

            // Clean up the uploaded file after processing
            Storage::delete($filePath);

            // Return success response
            return $this->sendResponse($historyDisease, 'Plant disease record created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', 422);
        } catch (\Exception $e) {
            Log::error('Unexpected Error', ['error' => $e->getMessage()]);
            if ($filePath) {
                Storage::delete($filePath); 
            }
            return $this->sendError('An unexpected error occurred. Please try again.', 500);
        }
    }
}
