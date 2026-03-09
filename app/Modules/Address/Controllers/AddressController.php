<?php

namespace App\Modules\Address\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Address\Models\Address;
use App\Modules\Address\Requests\CreateAddressRequest;
use App\Modules\Address\Requests\UpdateAddressRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $addresses = $user->addresses()->paginate(10);

        return response()->json($addresses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAddressRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $address = $user->addresses()->create($request->validated());

        return response()->json($address, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Address $address): JsonResponse
    {
        $this->authorize('view', $address);

        return response()->json($address);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address->update($request->validated());

        return response()->json($address);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return response()->json(null, 204);
    }
}
