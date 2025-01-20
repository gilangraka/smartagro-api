<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Http\Requests\IndexRequest;
use App\Http\Requests\Post\CommentPostRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends BaseController
{
    public function index(IndexRequest $request)
    {
        try {
            $params = $request->validated();
            $search = $params['q'] ?? null;
            $perPage = $params['per_page'] ?? 10;
            $orderBy = $params['order_by'] ?? 'created_at';
            $orderDirection = $params['order_direction'] ?? 'desc';

            $data = Post::with(['user:id,name'])
                ->select(['id', 'user_id', 'title', 'slug', 'imageUrl', 'count'])
                ->when(
                    !is_null($search),
                    fn($q) => $q->where('name', 'like', "%$search%")
                )
                ->orderBy($orderBy, $orderDirection)
                ->paginate($perPage);

            return $this->sendResponse($data, '', true);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $data = Post::with([
                'user:id,name',
                'category:id,name',
                'postComments:id,comment,user_id,updated_at',
                'postComments.user:id,name'
            ])
                ->find($id);
            if (!$data) return $this->sendError('Post not found!');

            $data->count += 1;
            $data->save();

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function store(StorePostRequest $request)
    {
        try {
            $params = $request->validated();
            $params['user_id'] = Auth::id();

            $slug = Str::slug($params['title']);
            $count = Post::where('slug', $slug)->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $params['slug'] = $slug;

            $file = $request->file('imageUrl');
            $path = 'uploads/posts';
            $fileName = UploadHelper::uploadFile($file, $path);
            $params['imageUrl'] = $fileName;

            $data = new Post($params);
            $data->save();

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function update(UpdatePostRequest $request, $id)
    {
        try {
            $params = $request->validated();
            $data = Post::find($id);
            if (!$data) return $this->sendError('Post not found!');

            if ($request->hasFile('imageUrl')) {
                UploadHelper::deleteFile('uploads/posts/' . $data->imageUrl);
                $file = $request->file('imageUrl');
                $path = 'uploads/posts';
                $fileName = UploadHelper::uploadFile($file, $path);
                $params['imageUrl'] = $fileName;
            }

            if ($params['title'] != $data['title']) {
                $slug = Str::slug($params['title']);
                $count = Post::where('slug', $slug)->count();
                if ($count > 0) {
                    $slug .= '-' . ($count + 1);
                }
                $params['slug'] = $slug;
            }

            $data->update($params);
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $data = Post::find($id);
            if (!$data) return $this->sendError('Post not found!');

            $data->delete();
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function comment(CommentPostRequest $request)
    {
        try {
            $params = $request->validated();
            $params['user_id'] = Auth::id();

            $data = new PostComment($params);
            $data->save();

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
