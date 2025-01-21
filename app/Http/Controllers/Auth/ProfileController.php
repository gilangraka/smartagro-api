<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            return $this->sendResponse($user, 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
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
                    $filename = 'profile_' . preg_replace('/\s+/', '_', strtolower($user->email)) . '.' . $image->getClientOriginalExtension();
                    $filePath = $image->storeAs('profile_images', $filename, 'public');
                    $imageUrl = asset('storage/' . $filePath);
                }
            }

            $user->image = $imageUrl ?? $user->image;

            $user->save();

            return $this->sendResponse($user, 'Profile updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError($e->errors(), 400);

        } catch (\Exception $e) {
            Log::error("Profile update error: " . $e->getMessage());
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
