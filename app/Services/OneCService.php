<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OneCService
{
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
     * Обработать продажу от 1С: создать Order + списать остатки
     */
    public function processSale(array $data): array
    {
        $products = [];
        foreach ($data['items'] as $item) {
            $product = Product::with('stock')->where('article', $item['article'])->first();

            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'product_not_found',
                    'message' => "Товар с артикулом '{$item['article']}' не найден",
                ];
            }

            $stockQty = $product->stock?->quantity ?? 0;
            if ($stockQty < $item['quantity']) {
                return [
                    'success' => false,
                    'error' => 'insufficient_stock',
                    'message' => "Недостаточно остатка для '{$item['article']}': доступно {$stockQty}, запрошено {$item['quantity']}",
                ];
            }

            $products[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ];
        }

        try {
            return DB::transaction(function () use ($data, $products) {
                $total = collect($products)->sum(fn($p) => $p['price'] * $p['quantity']);

                $order = Order::create([
                    'user_id' => null,
                    'total' => $total,
                    'status' => Order::STATUS_DONE,
                    'payment_type' => 'cash',
                    'payment_status' => Order::PAYMENT_PAID,
                    'delivery_type' => null,
                    'delivery_address' => $data['comment'] ?? null,
                    'delivery_phone' => $data['customer_phone'] ?? null,
                ]);

                foreach ($products as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                    ]);

                    $stock = $item['product']->stock;
                    $previousQty = $stock->quantity;
                    $stock->decrement('quantity', $item['quantity']);

                    StockHistory::create([
                        'stock_id' => $stock->id,
                        'type' => 'minus',
                        'quantity' => $stock->quantity,
                        'previous_quantity' => $previousQty,
                        'difference' => $item['quantity'],
                        'order_id' => $order->id,
                        'source' => '1c',
                    ]);
                }

                Log::info('1C: Продажа обработана', [
                    'order_id' => $order->id,
                    '1c_order_id' => $data['1c_order_id'] ?? null,
                    'items_count' => count($products),
                    'total' => $total,
                ]);

                return [
                    'success' => true,
                    'message' => 'Продажа обработана, заказ создан',
                    'data' => [
                        'order_id' => $order->id,
                        'total' => $total,
                        'items_count' => count($products),
                    ],
                ];
            });
        } catch (\Exception $e) {
            Log::error('1C: Ошибка при обработке продажи', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'processing_error',
                'message' => 'Ошибка при обработке продажи: ' . $e->getMessage(),
            ];
        }
    }
}
