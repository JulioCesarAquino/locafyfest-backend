<?php

namespace App\Modules\Review\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Review\Models\Review;
use App\Modules\Review\Requests\CreateReviewRequest;
use App\Modules\Review\Requests\UpdateReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $reviews = Review::query();

        if ($request->has('product_id')) {
            $reviews->where('product_id', $request->input('product_id'));
        }

        if ($request->has('user_id')) {
            $reviews->where('user_id', $request->input('user_id'));
        }

        return response()->json($reviews->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Certifique-se que o relacionamento 'reviews' exista no model User
        $review = $user->reviews()->create($request->validated());

        return response()->json($review, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review): JsonResponse
    {
        $this->authorize('view', $review);

        return response()->json($review);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return response()->json($review);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->json(null, 204);
    }
}
