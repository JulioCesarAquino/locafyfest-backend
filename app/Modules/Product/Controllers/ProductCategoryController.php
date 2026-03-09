<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Product\Requests\CreateProductCategoryRequest;
use App\Modules\Product\Requests\UpdateProductCategoryRequest;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::all();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateProductCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', ProductCategory::class);

        $category = ProductCategory::create($request->validated());

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('view', $productCategory);

        return response()->json($productCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('update', $productCategory);

        $productCategory->update($request->validated());

        return response()->json($productCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('delete', $productCategory);

        $productCategory->delete();

        return response()->json(null, 204);
    }
}
