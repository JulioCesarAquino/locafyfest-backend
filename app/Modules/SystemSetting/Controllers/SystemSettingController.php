<?php

namespace App\Modules\SystemSetting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SystemSetting\Models\SystemSetting;
use App\Modules\SystemSetting\Requests\UpdateSystemSettingRequest;
use Illuminate\Http\JsonResponse;

class SystemSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        $settings = SystemSetting::all();

        return response()->json($settings);
    }

    /**
     * Display the specified resource.
     */
    public function show(SystemSetting $systemSetting): JsonResponse
    {
        $this->authorize('view', $systemSetting);

        return response()->json($systemSetting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $systemSetting): JsonResponse
    {
        $this->authorize('update', $systemSetting);

        $systemSetting->update($request->validated());

        return response()->json($systemSetting);
    }
}
