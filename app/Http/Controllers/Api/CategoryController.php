<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponse;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Requests\Category\CategoryUpdateRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

class CategoryController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $category = Category::all();
        return ApiResponse::sendResponse(new CategoryCollection($category), 'Successfully get all category');
    }

    public function store(CategoryRequest $request)
    {
        $this->authorize('create', Category::class);


        $category = Category::create([
            'name' => $request->name,
            'icon' => $request->icon,
        ]);

        return ApiResponse::sendResponse(new CategoryResource($category), 'Successfully create category', Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $this->authorize('view', Category::class);

        $category = Category::find($id);

        if (!$category) {
            return ApiResponse::sendResponse(null, 'Category not found', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::sendResponse(new CategoryResource($category), 'Successfully get category');
    }

    public function update(CategoryUpdateRequest $request, string $id)
    {
        $this->authorize('update', Category::class);

        $category = Category::find($id);

        if (!$category) {
            return ApiResponse::sendResponse(null, 'Category not found', Response::HTTP_NOT_FOUND);
        }

        $category->update([
            'name' => $request->name,
            'icon' => $request->icon,
        ]);

        return ApiResponse::sendResponse(new CategoryResource($category), 'Successfully update category');
    }

    public function destroy(string $id)
    {
        $this->authorize('delete', Category::class);

        $category = Category::find($id);

        if (!$category) {
            return ApiResponse::sendResponse(null, 'Category not found', Response::HTTP_NOT_FOUND);
        }

        $category->delete();

        return ApiResponse::sendResponse(null, 'Successfully delete category');
    }
}
