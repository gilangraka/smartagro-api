<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\BaseController;
use App\Http\Requests\IndexRequest;
use App\Http\Requests\PostCategory\StorePostCategoryRequest;
use App\Http\Requests\PostCategory\UpdatePostCategoryRequest;
use App\Models\MPostCategory;
use Illuminate\Support\Str;

class PostCategoryController extends BaseController
{
    public function index(IndexRequest $request)
    {
        try {
            $params = $request->validated();
            $search = $params['q'] ?? null;
            $perPage = $params['per_page'] ?? 10;
            $orderBy = $params['order_by'] ?? 'created_at';
            $orderDirection = $params['order_direction'] ?? 'desc';

            $data = MPostCategory::select(['id', 'name', 'slug'])
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
            $data = MPostCategory::find($id);
            if (!$data) return $this->sendError('Post category not found!');

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function store(StorePostCategoryRequest $request)
    {
        try {
            $params = $request->validated();
            $slug = Str::slug($params['name']);

            $count = MPostCategory::where('slug', $slug)->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $params['slug'] = $slug;

            $data = new MPostCategory($params);
            $data->save();
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function update(UpdatePostCategoryRequest $request, $id)
    {
        try {
            $params = $request->validated();
            $data = MPostCategory::find($id);
            if (!$data) return $this->sendError('Post category not found!');

            if ($params['name'] != $data['name']) {
                $slug = Str::slug($params['name']);
                $count = MPostCategory::where('slug', $slug)->count();
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
            $data = MPostCategory::find($id);
            if (!$data) return $this->sendError('Post category not found!');

            $data->delete();
            return $this->sendResponse(null);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
