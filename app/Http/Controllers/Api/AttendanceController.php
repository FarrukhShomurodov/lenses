<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Http\Requests\AttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController
{
    protected $service;

    public function __construct(AttendanceService $service)
    {
        $this->service = $service;
    }

    public function qrScan(AttendanceRequest $request): JsonResponse
    {
        return $this->service->processQrScan((array)$request->validated());
    }
}
