<?php

namespace App\Modules\Favorite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Favorite\Requests\CreateFavoriteRequest;
use App\Modules\Favorite\Requests\DeleteFavoriteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $favorites = Auth::user()->favorites()->paginate(10);
        return response()->json($favorites);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFavoriteRequest $request): JsonResponse
    {
        $favorite = Auth::user()->favorites()->create($request->validated());
        return response()->json($favorite, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Favorite $favorite): JsonResponse
    {
        $this->authorize('delete', $favorite);
        $favorite->delete();
        return response()->json(null, 204);
    }
}


