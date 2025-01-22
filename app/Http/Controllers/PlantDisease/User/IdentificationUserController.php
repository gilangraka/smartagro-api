<?php

namespace App\Http\Controllers\PlantDisease\User;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\PlantIdentification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;

class IdentificationUserController extends BaseController
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new Client();
    }
    
    public function index(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized. Please login first.'], 401);
            }
            elseif(Auth::user()->role == 'user'){
                $params = $request->validate([
                    'q' => 'nullable|string',
                    'per_page' => 'nullable|integer|min:1',
                    'order_direction' => 'nullable|in:asc,desc',
                ]);
        
                $search = $params['q'] ?? null;
                $perPage = $params['per_page'] ?? 10;
                $orderDirection = $params['order_direction'] ?? 'desc';
        
                $plant_identifications = PlantIdentification::where('user_id', Auth::id())
                    ->when($search, function ($query, $search) {
                        return $query->where('plant_name', 'like', "%$search%");
                    })
                    ->orderBy('created_at', $orderDirection)
                    ->paginate($perPage);
        
                return response()->json($plant_identifications, 200);
            } 
            else{
                $params = $request->validate([
                    'q' => 'nullable|string',
                    'per_page' => 'nullable|integer|min:1',
                    'order_direction' => 'nullable|in:asc,desc',
                ]);
    
                $search = $params['q'] ?? null;
                $perPage = $params['per_page'] ?? 10;
                $orderDirection = $params['order_direction'] ?? 'desc';
    
                $plant_identifications = PlantIdentification::when($search, function ($query, $search) {
                        return $query->where('plant_name', 'like', "%$search%");
                    })
                    ->orderBy('created_at', $orderDirection)
                    ->paginate($perPage);
    
                return response()->json($plant_identifications, 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch plant identifications', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized. Please login first.'], 401);
            }
            $plant_identification = PlantIdentification::find($id);

            if (!$plant_identification) {
                return response()->json(['error' => "Plant identification with ID $id not found"], 404);
            }

            return $this->sendResponse($plant_identification, 'Plant identification fetched successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch plant identification', 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', 422);
        } 

    }

    public function store(Request $request){
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized. Please login first.'], 401);
            }
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
                    'Api-Key' =>'ebJyUN9jBHhhRxolwKqQwqvPe8Wi3NbRRqvNASOaUYFydfJDgI',
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

            $conversation = Http::withHeaders([
                'Api-Key' => 'ebJyUN9jBHhhRxolwKqQwqvPe8Wi3NbRRqvNASOaUYFydfJDgI',
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


            $historyDisease = PlantIdentification::create([
                'user_id' => Auth::user()->id,
                'image' => $image_url,
                'lat' => $latitude,
                'long' => $longitude,
                'probability' => $probability,
                'plant_name' => $name,
                'similar_images' => $similar_image,
                'explaination' => $explanation,
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

    public function destroy(Request $request, $id)
    {
        try {
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized. Please login first.'], 401);
            }
            $plant_identification = PlantIdentification::find($id);

            if (!$plant_identification) {
                return response()->json(['error' => "Plant identification with ID $id not found"], 404);
            }

            $plant_identification->delete();

            return response()->json(['message' => 'Plant identification deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete plant identification', 'message' => $e->getMessage()], 500);
        }
    }
}
