<?php

namespace App\Http\Controllers\PlantDisease\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class CheckTranslationController extends Controller
{
    /**
     * Handle the incoming request to check translation.
     */
    public function __invoke(Request $request)
    {
        Log::info('CheckTranslationController invoked');

        try {
            // Validasi input
            Log::info('Validating request data', ['request_data' => $request->all()]);
            $validatedData = $request->validate([
                'imageInput' => 'required|file|mimes:jpeg,jpg,png|max:5120', // Max 5 MB
                'lat' => 'required|numeric',
                'long' => 'required|numeric',
            ]);

            Log::info('Request data validated successfully', ['validated_data' => $validatedData]);

            // Simpan file yang diunggah
            $file = $request->file('imageInput');
            $filePath = $file->store('plant_diseases', 'public');
            $absoluteFilePath = storage_path('app/public/' . $filePath);
            Log::info('File uploaded successfully', ['file_path' => $filePath]);

            // Kirim permintaan ke Plant.ID API
            Log::info('Sending request to Plant.ID API');
            $response = Http::withHeaders([
                'Api-Key' => env('PLANT_ID_API_KEY'),
            ])->attach(
                'images', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName()
            )->post('https://plant.id/api/v3/health_assessment?details=local_name,url,treatment,classification,common_names', [
                'latitude' => $validatedData['lat'],
                'longitude' => $validatedData['long'],
                'similar_images' => 'true',
            ]);

            // Periksa jika respons gagal
            if ($response->failed()) {
                Log::error('Plant.ID API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                Storage::delete($filePath); // Hapus file
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get response from Plant.ID API',
                ], $response->status());
            }

            Log::info('Received response from Plant.ID API', ['response' => $response->json()]);

            $responseData = $response->json();
            $data = $responseData['result']['disease']['suggestions'][0] ?? null;
            $diseaseData = $responseData['result']['disease']['suggestions'][0] ?? null;
            $treatment = json_encode($data['details']['treatment']['chemical'] ?? []);
            log::info('Disease data', ['disease_data' => $treatment]);

            // Pastikan ada data penyakit
            if (!$diseaseData) {
                Log::warning('No disease suggestions returned from API', ['response' => $responseData]);
                Storage::delete($filePath); // Hapus file
                return response()->json([
                    'success' => false,
                    'message' => 'No disease suggestions returned from API.',
                ], 422);
            }

            
            $accessToken = $responseData['access_token'] ?? null;
            if (!$accessToken) {
                Log::error('Access Token Missing', ['response' => $responseData]);
                Storage::delete($filePath); // Hapus file
                return response()->json([
                    'success' => false,
                    'message' => 'Access token is missing from the API response.',
                ], 500);
            } else {
                Log::info('Access token retrieved successfully', ['access_token' => $accessToken]);
            }

            $conversation = Http::withHeaders([
                'Api-Key' => env('PLANT_ID_API_KEY'),
            ])->post('https://plant.id/api/v3/identification/'.$accessToken.'/conversation', [
                    "question"=>$treatment."translate in indonesia",
                    "prompt"=> "Give answer all CAPS.",
                    "temperature"=> 0.5,
                    "app_name"=> "MyAppBot"
            ]);

            $conversationData = $conversation->json();

            return response()->json([
                'success' => true,
                'message' => 'Access token retrieved successfully.',
                'access_token' => $conversationData,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $httpException) {
            Log::error('HTTP Client Error', [
                'message' => $httpException->getMessage(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'HTTP Client Error. Please try again later.',
            ], 500);
        } catch (Exception $e) {
            Log::error('Unexpected Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ], 500);
        } finally {
            if (isset($filePath) && Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info('Temporary file deleted', ['file_path' => $filePath]);
            }
        }
    }
}
