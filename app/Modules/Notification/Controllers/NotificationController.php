<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notification\Models\Notification;
use App\Modules\Notification\Requests\CreateNotificationRequest;
use App\Modules\Notification\Requests\UpdateNotificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $notifications = Auth::user()->notifications()->paginate(10);

        return response()->json($notifications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateNotificationRequest $request): JsonResponse
    {
        $this->authorize('create', Notification::class);

        $notification = Notification::create($request->validated());

        return response()->json($notification, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        return response()->json($notification);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificationRequest $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification->update($request->validated());

        return response()->json($notification);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(null, 204);
    }
}
