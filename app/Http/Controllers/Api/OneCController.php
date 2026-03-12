<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Services\OneCService;
use Illuminate\Http\JsonResponse;

class OneCController
{
    public function __construct(
        private readonly OneCService $service,
    ) {}

    /**
     * Получение данных о товаре по артикулу для 1С
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
     * Отправка заказа в 1С
     */
    public function sendOrder(int $orderId): JsonResponse
    {
        $order = Order::with(['items.product', 'user'])->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'order_not_found',
                'message' => "Заказ #{$orderId} не найден",
            ], 404);
        }

        if ($order->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'empty_order',
                'message' => "Заказ #{$orderId} не содержит позиций",
            ], 422);
        }

        $result = $this->service->sendOrder($order);

        if (!$result['success']) {
            $status = match ($result['error'] ?? '') {
                '1c_connection_error' => 503,
                '1c_error' => $result['status_code'] ?? 502,
                default => 500,
            };

            return response()->json($result, $status);
        }

        return response()->json($result);
    }
}
