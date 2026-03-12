<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneCService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.one_c.base_url', ''), '/');
    }

    /**
     * Получить данные товара по артикулу
     */
    public function getProductByArticle(string $article): ?array
    {
        $product = Product::with(['stock', 'category', 'images'])
            ->where('article', $article)
            ->first();

        if (!$product) {
            return null;
        }

        return [
            'id' => $product->id,
            'article' => $product->article,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name,
            'category_id' => $product->category_id,
            'price' => (float) $product->price,
            'discount_percent' => (int) $product->discount_percent,
            'price_with_discount' => round($product->price * (1 - $product->discount_percent / 100), 2),
            'stock_quantity' => $product->stock?->quantity ?? 0,
            'is_active' => (bool) $product->is_active,
            'manufacturer' => $product->manufacturer,
            'model' => $product->model,
            'coating' => $product->coating,
            'index' => $product->index,
            'sph' => $product->sph,
            'cyl' => $product->cyl,
            'axis' => $product->axis,
            'family' => $product->family,
            'color' => $product->color,
            'option' => $product->option,
            'description' => $product->description,
            'images' => $product->images->pluck('url')->toArray(),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Отправить заказ в 1С после оплаты
     */
    public function sendOrder(Order $order): array
    {
        $order->load(['items.product', 'user']);

        $payload = $this->buildOrderPayload($order);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$this->baseUrl}/orders", $payload);

            if ($response->successful()) {
                $body = $response->json();

                Log::info('1C: Заказ успешно отправлен', [
                    'order_id' => $order->id,
                    '1c_order_id' => $body['order_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message' => 'Заказ успешно передан в 1С',
                    'data' => [
                        'order_id' => $order->id,
                        '1c_order_id' => $body['order_id'] ?? null,
                        '1c_response' => $body,
                    ],
                ];
            }

            Log::error('1C: Ошибка при отправке заказа', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => '1c_error',
                'message' => '1С вернул ошибку: ' . $response->status(),
                'status_code' => $response->status(),
                'details' => $response->json() ?? $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('1C: Не удалось подключиться', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => '1c_connection_error',
                'message' => 'Не удалось подключиться к 1С: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Собрать payload заказа для 1С
     */
    private function buildOrderPayload(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            $data = [
                'product_id' => $item->product_id,
                'article' => $item->product?->article,
                'name' => $item->product?->name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'total' => (float) ($item->price * $item->quantity),
            ];

            // Rx-данные для рецептурных линз
            if ($item->rx_sph || $item->rx_cyl || $item->rx_axis || $item->rx_add || $item->rx_prism) {
                $data['rx'] = [
                    'sph' => $item->rx_sph,
                    'cyl' => $item->rx_cyl,
                    'axis' => $item->rx_axis,
                    'add' => $item->rx_add,
                    'prism' => $item->rx_prism,
                ];
            }

            return $data;
        })->toArray();

        return [
            'order_id' => $order->id,
            'created_at' => $order->created_at?->toIso8601String(),
            'customer' => [
                'user_id' => $order->user_id,
                'name' => $order->user?->name ?? $order->user?->first_name,
                'phone' => $order->delivery_phone ?? $order->user?->phone,
            ],
            'payment' => [
                'type' => $order->payment_type,
                'status' => $order->payment_status,
                'total' => (float) $order->total,
            ],
            'delivery' => [
                'type' => $order->delivery_type,
                'address' => $order->delivery_address,
                'phone' => $order->delivery_phone,
            ],
            'items' => $items,
        ];
    }
}
