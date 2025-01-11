<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscussComment;
use Illuminate\Support\Facades\Auth;

class DiscussesCommentController extends BaseController
{

    /**
     * Get a paginated list of discussions..
     */
    public function index($discuss_id){
        try {
            $data = DiscussComment::with(['user:id,name'])
                ->where('discus_id', $discuss_id)
                ->orderBy('created_at', 'desc')
                ->paginate(5);

            return $this->sendResponse($data, 'Comments fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error fetching comments: ' . $e->getMessage(), 500);
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
