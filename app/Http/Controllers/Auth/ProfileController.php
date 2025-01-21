<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch user profile', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            // Validasi input
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'bio' => 'nullable|string|max:500',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Update name dan bio
            $user->name = $validatedData['name'];
            $user->bio = $validatedData['bio'] ?? $user->bio;

            $imageUrl = null;
            if ($request->hasFile('image')) {
                if ($request->hasFile('image')) {
                    if ($user->image) {
                        Storage::delete($user->image);
                    }
                $image = $request->file('image');
                $filePath = $image->store('profile_images', 'public');
                $imageUrl = asset('storage/' . $filePath);
                }
            }

            $user->image = $imageUrl ?? $user->image;

            $user->save();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error("Profile update error: " . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating the profile',
            ], 500);
        }
    }
}
