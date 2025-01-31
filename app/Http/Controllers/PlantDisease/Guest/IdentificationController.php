<?php

namespace App\Http\Controllers\PlantDisease\Guest;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class IdentificationController extends BaseController
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
        try {
            $validatedData = $request->validate([
                'image' => 'required|file|mimes:jpeg,jpg,png|max:5120', // Max 5 MB
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

            $file = $request->file('image');
            $filePath = $file->store('plant_diseases', 'public');
            $fileUrl = asset('storage/' . $filePath);

            $cacheKey = md5($file->getRealPath() . $latitude . $longitude);

            $responseData = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($file, $latitude, $longitude) {
                return Http::withHeaders([
                    'Api-Key' => env('PLANT_ID_API_KEY'),
                ])->attach(
                    'images', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName()
                )->post('https://plant.id/api/v3/identification', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'similar_images' => 'true'
                ])->json();
            });

            Log::info('API Response', ['response' => $responseData]);

            if (!isset($responseData['result']['classification']['suggestions'][0])) {
                Log::error('Invalid API Response', ['response' => $responseData]);
                Storage::delete($filePath); 
                return $this->sendError('Failed to process response from Plant.ID API', 500);
            }

            $name = $responseData['result']['classification']['suggestions'][0]['name'] ?? null;
            $probability = $responseData['result']['classification']['suggestions'][0]['probability'] ?? null;

            $similar_image = $responseData['result']['classification']['suggestions'][0]['similar_images'][0]['url'] ?? null;
            $image_url = $responseData['input']['images'][0] ?? null;

            // Translate treatment to Indonesian
            $conversation = Http::withHeaders([
                'Api-Key' => env('PLANT_ID_API_KEY'),
            ])->post('https://plant.id/api/v3/identification/'.$responseData['access_token'].'/conversation', [
                "question" => $name . " Translate this text into Indonesian, keeping the context related to plants and agriculture while maintaining proper capitalization.",
                "prompt" => "Provide a simple explanation of the plant's name in Indonesian and include basic information about how to grow it, focusing on cultivation methods such as planting, watering, sunlight, and soil requirements, in a way that's easy for anyone to understand.",
                "temperature"=> 0.5,
                "app_name"=> "AgroLens"
            ]);

            // Handle translation error
            if ($conversation->failed()) {
                Log::error('Plant.ID API Error', ['status' => $conversation->status()]);
                Storage::delete($filePath); 
                return $this->sendError('Failed to get response from Plant.ID API', $conversation->status());
            }

            // Parse translated treatment text
            $translatedText = explode("\n\n", $conversation['messages'][1]['content'] ?? '');
            $explanation = $translatedText[0] ?? null;

            $historyDisease = [
                'image' => $image_url,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'probability' => $probability,
                'name' => $name,
                'similar_images' => $similar_image,
                'explanation' => $explanation,
            ];

            Storage::delete($filePath);
            Log::info('Plant disease record created successfully', ['data' => $historyDisease]);

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
