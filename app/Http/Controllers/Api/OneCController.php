<?php

namespace App\Http\Controllers\Api;

use App\Services\OneCService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OneCController
{
    public function __construct(
        private readonly OneCService $service,
    ) {}

    /**
     * GET /api/1c/product/{article}
     * 1С запрашивает данные о товаре
     */
    public function product(string $article): JsonResponse
    {
        $result = $this->service->getProductByArticle($article);

        if (!$result) {
            return response()->json([
                'success' => false,
                'error' => 'product_not_found',
                'message' => "Товар с артикулом '{$article}' не найден",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * POST /api/1c/sale
     * 1С сообщает о продаже — создаём заказ + списываем остаток
     */
    public function sale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.article' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'customer_phone' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'comment' => 'nullable|string',
            '1c_order_id' => 'nullable|string',
        ]);

        $result = $this->service->processSale($validated);

        if (!$result['success']) {
            $status = match ($result['error'] ?? '') {
                'product_not_found' => 404,
                'insufficient_stock' => 422,
                default => 500,
            };

            return response()->json($result, $status);
        }

        return response()->json($result, 201);
    }
}
