<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscussComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DiscussesCommentController extends BaseController
{

    /**
     * Get a paginated list of discussions..
     */
    public function index($discuss_id)
    {
        try {
            if (!is_numeric($discuss_id)) {
                return $this->sendError('Invalid discuss ID.', 400);
            }

            $data = DiscussComment::with(['user:id,name'])
                ->where('discus_id', $discuss_id)
                ->orderBy('created_at', 'desc')
                ->paginate(5);


            if ($data->isEmpty()) {
                return $this->sendResponse([], 'No comments found for the given discussion.');
            }

            return $this->sendResponse($data, 'Comments fetched successfully.');
        } catch (\Exception $e) {
            Log::error('Error fetching comments', [
                'discuss_id' => $discuss_id,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Error fetching comments. Please try again later.', 500);
        }
    }

    /**
     * Get a paginated list of comments by a specific user.
     */
    public function getCommentsByUserId($userId)
    {
        try {
            // Validasi apakah userId adalah angka
            if (!is_numeric($userId)) {
                return $this->sendError('Invalid user ID.', 400);
            }

            // Ambil data komentar berdasarkan userId
            $data = DiscussComment::with(['discuss:id,comment'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate(5);

            // Periksa apakah data kosong
            if ($data->isEmpty()) {
                return $this->sendResponse([], 'No comments found for the given user.');
            }

            // Kembalikan respons sukses dengan data
            return $this->sendResponse($data, 'Comments fetched successfully.');
        } catch (\Exception $e) {
            // Log error jika terjadi kesalahan
            Log::error('Error fetching comments by user ID', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            // Kembalikan respons error
            return $this->sendError('Error fetching comments. Please try again later.', 500);
        }
    }



    /**
     * Add a comment to a discussion.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'discus_id' => 'required|exists:discusses,id',
                'comment' => 'required|string',
            ]);

            $validatedData['user_id'] = Auth::id();

            if (!is_numeric($validatedData['discus_id'])) {
                return $this->sendError('Invalid discuss ID.', 400);
            }

            if (!$validatedData['user_id']) {
                return $this->sendError('Unauthorized. Please login first.', 401);
            }

            $data = DiscussComment::create($validatedData);

            return $this->sendResponse($data, 'Comment added successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error adding comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * update comment.
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'comment' => 'required|string',
            ]);

            $data = DiscussComment::find($id);
            if (!$data) {
                return $this->sendError('Comment not found!', 404);
            }

            $data->comment = $validatedData['comment'];
            $data->save();

            return $this->sendResponse($data, 'Comment updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error updating comment: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Delete a comment from a discussion.
     */
    public function destroy($id){
        try {
            $data = DiscussComment::find($id);
            if (!$data) {
                return $this->sendError('Comment not found!', 404);
            }

            $data->delete();

            return $this->sendResponse(null, 'Comment deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error deleting comment: ' . $e->getMessage(), 500);
        }
    }
}
