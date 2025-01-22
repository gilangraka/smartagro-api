<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\discuss;
use App\Helpers\UploadHelper;
use App\Models\DiscussComment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class DiscussesController extends BaseController
{
    /**
     * Get a paginated list of discussions.
     */
    public function index(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'q' => 'nullable|string',
                'per_page' => 'nullable|integer',
                'order_by' => 'nullable|string',
                'order_direction' => 'nullable|in:asc,desc',
            ]);

            $search = $validatedData['q'] ?? null;
            $perPage = $validatedData['per_page'] ?? 10;
            $orderBy = $validatedData['order_by'] ?? 'created_at';
            $orderDirection = $validatedData['order_direction'] ?? 'desc';

            $user = $request->user() ?? null;

            $data = Discuss::with(['user:id,name'])
                ->withCount('discussComments')
                ->select(['id', 'title', 'slug', 'imageUrl'])
                ->when($search, fn($query) => $query->where('title', 'like', "%$search%"))
                ->orderBy($orderBy, $orderDirection)
                ->paginate($perPage);
            return $this->sendResponse($data, 'Discussions fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error fetching discussions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single discussion with comments.
     */
    public function show($slug)
    {
        try {
            $id = Discuss::where('slug', $slug)->value('id');
            $data = Discuss::with([
                'user:id,name',
                'discussComments:id,comment,user_id,updated_at',
                'discussComments.user:id,name'
            ])->find($id);

            if (!$data) {
                return $this->sendError('Discussion not found!', 404);
            }

            $data->comments_count = $data->discussComments->count();

            $data->makeHidden('user_id');
            $data->discussComments->each(function ($comment) {
                $comment->makeHidden('discus_id');
                $comment->makeHidden('user_id');
            });

            return $this->sendResponse($data, 'Discussion details fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error fetching discussion: ' . $e->getMessage(), 500);
        }
    }


    public function getDiscussByUserId($userId, Request $request)
    {
        try {
            $validatedData = $request->validate([
                'per_page' => 'nullable|integer',
                'order_by' => 'nullable|string',
                'order_direction' => 'nullable|in:asc,desc',
            ]);

            $perPage = $validatedData['per_page'] ?? 10;
            $orderBy = $validatedData['order_by'] ?? 'created_at';
            $orderDirection = $validatedData['order_direction'] ?? 'desc';

            $data = Discuss::with(['user:id,name', 'discussComments'])
                ->where('user_id', $userId)
                ->orderBy($orderBy, $orderDirection)
                ->paginate($perPage);

            if ($data->isEmpty()) {
                return $this->sendError('No discussions found for the given user ID.', 404);
            }

            return $this->sendResponse($data, 'Discussions fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error fetching discussions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a new discussion.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'imageUrl' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $validatedData['user_id'] = Auth::id();

            if (!$validatedData['user_id']) {
                return $this->sendError('Unauthorized. Please login first.', 401);
            }

            $validatedData['slug'] = $this->generateSlug($validatedData['title']);

            if ($request->hasFile('imageUrl')) {
                $validatedData['imageUrl'] = UploadHelper::uploadFile($request->file('imageUrl'), 'uploads/discusses');
            }

            $data = Discuss::create($validatedData);

            return $this->sendResponse($data, 'Discussion created successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error creating discussion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an existing discussion.
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'imageUrl' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $data = Discuss::find($id);
            if (!$data) {
                return $this->sendError('Discussion not found!', 404);
            }

            if ($request->hasFile('imageUrl')) {
                UploadHelper::deleteFile('uploads/discusses/' . $data->imageUrl);
                $validatedData['imageUrl'] = UploadHelper::uploadFile($request->file('imageUrl'), 'uploads/discusses');
            }

            if ($validatedData['title'] !== $data->title) {
                $validatedData['slug'] = $this->generateSlug($validatedData['title']);
            }

            $data->update($validatedData);

            return $this->sendResponse($data, 'Discussion updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error updating discussion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a discussion.
     */
    public function destroy($id)
    {
        try {
            $data = Discuss::find($id);
            if (!$data) {
                return $this->sendError('Discussion not found!', 404);
            }

            if ($data->imageUrl) {
                UploadHelper::deleteFile('uploads/discusses/' . $data->imageUrl);
            }

            $data->delete();

            return $this->sendResponse(null, 'Discussion deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error deleting discussion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate a unique slug for a discussion.
     */
    private function generateSlug($title)
    {
        $slug = Str::slug($title);
        $count = Discuss::where('slug', 'like', "$slug%")->count();
        return $count ? "$slug-" . ($count + 1) : $slug;
    }
}
