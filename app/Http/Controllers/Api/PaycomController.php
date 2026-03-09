<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Services\PaycomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaycomController
{
    protected PaycomService $paycom;

    public function __construct(PaycomService $paycom)
    {
        $this->paycom = $paycom;
    }

    public function handleRequest(Request $request): JsonResponse
    {
        $method = $request->input('method');
        $params = $request->input('params');

        switch ($method) {
            case 'CheckPerformTransaction':
                return response()->json($this->paycom->checkPerformTransaction($params));
            case 'CreateTransaction':
                return response()->json($this->paycom->createTransaction($params));
            case 'PerformTransaction':
                return response()->json($this->paycom->performTransaction($params));
            case 'CancelTransaction':
                return response()->json($this->paycom->cancelTransaction($params));
            case 'CheckTransaction':
                return response()->json($this->paycom->checkTransaction($params));
            case 'GetStatement':
                return response()->json($this->paycom->getStatement($params));
            default:
                return response()->json(['error' => 'Unknown method'], 400);
        }
    }
}
