<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class OrderController
{
    /**
     * Создание Rx-заказа (с рецептом)
     */
    public function createRx(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $request->validate([
            'payment_type' => 'required|in:cash,payme',
            'delivery_type' => 'required|in:pickup,delivery',
            'rx' => 'required|array',
            'rx.*.sph' => 'nullable|numeric',
            'rx.*.cyl' => 'nullable|numeric',
            'rx.*.axis' => 'nullable|integer',
            'rx.*.add' => 'nullable|numeric',
            'rx.*.prism' => 'nullable|numeric',
        ]);

        if ($request->delivery_type === 'delivery') {
            $request->validate([
                'delivery_address' => 'required',
                'delivery_phone' => 'required',
            ]);
        }

        // === Создаём заказ ===
        $order = Order::create([
            'user_id' => $user->id,
            'status' => Order::STATUS_NEW,
            'payment_type' => $request->payment_type,
            'payment_status' => Order::PAYMENT_PENDING,
            'total' => 0, // Можно рассчитать цену, если нужно
            'delivery_type' => $request->delivery_type,
            'delivery_address' => $request->delivery_address,
            'delivery_phone' => $request->delivery_phone,
        ]);

        // === Rx-заказ: отдельная позиция для каждого глаза (OD и OS) ===
        if (is_array($request->rx) && count($request->rx) > 0) {
            foreach ($request->rx as $eye => $rx) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => null, // Нет продукта, это индивидуальный заказ
                    'price' => 0, // Можно добавить расчёт цены
                    'quantity' => 1,
                    'rx_sph' => $rx['sph'] ?? null,
                    'rx_cyl' => $rx['cyl'] ?? null,
                    'rx_axis' => $rx['axis'] ?? null,
                    'rx_add' => $rx['add'] ?? null,
                    'rx_prism' => $rx['prism'] ?? null,
                ]);
            }
        } else {
            // fallback: если rx не массив или пустой
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => null,
                'price' => 0,
                'quantity' => 1,
                'rx_sph' => null,
                'rx_cyl' => null,
                'rx_axis' => null,
                'rx_add' => null,
                'rx_prism' => null,
            ]);
        }

        // === Сообщение пользователю ===
        $msg = $this->t($user, 'order.created_title') . "\n\n";
        $msg .= $this->t($user, 'order.number', ['id' => $order->id]) . "\n";
        $msg .= $this->t($user, 'order.payment', [
            'type' => $this->t($user, 'order.payment_' . $order->payment_type),
        ]) . "\n";
        $msg .= $this->t($user, 'order.delivery', [
            'type' => $this->t($user, 'order.delivery_' . $order->delivery_type),
        ]) . "\n";
        if ($order->delivery_type === 'delivery') {
            $msg .= $this->t($user, 'order.address', [
                'address' => $order->delivery_address,
            ]) . "\n";
            $msg .= $this->t($user, 'order.phone', [
                'phone' => $order->delivery_phone,
            ]) . "\n";
        }
        $msg .= "\n" . $this->t($user, 'order.thanks');

        $this->telegram->sendMessage([
            'chat_id' => $user->chat_id,
            'text' => $msg,
            'parse_mode' => 'Markdown',
        ]);

        // Локация при самовывозе
        if ($order->delivery_type === 'pickup') {
            $this->telegram->sendLocation([
                'chat_id' => $user->chat_id,
                'latitude' => 41.2995,
                'longitude' => 69.2401,
            ]);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_total_price' => $order->total,
        ]);
    }
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    private function getUser(Request $request): BotUser
    {
        return BotUser::query()->firstOrCreate([
            'chat_id' => $request->chat_id,
        ]);
    }

    private function t(BotUser $user, string $key, array $replace = []): string
    {
        app()->setLocale($user->lang ?? 'ru');

        return __($key, $replace);
    }

    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $request->validate([
            'payment_type' => 'required|in:cash,payme',
            'delivery_type' => 'required|in:pickup,delivery',
        ]);

        if ($request->delivery_type === 'delivery') {
            $request->validate([
                'delivery_address' => 'required',
                'delivery_phone' => 'required',
            ]);
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load('items.product');

        $pricing = $cart->pricingSummary();

        if ($cart->items->count() === 0) {
            return response()->json([
                'success' => false,
                'msg' => $this->t($user, 'order.cart_empty'),
            ]);
        }

        // === Создаём заказ ===
        $order = Order::create([
            'user_id' => $user->id,
            'status' => Order::STATUS_NEW,
            'payment_type' => $request->payment_type,
            'payment_status' => Order::PAYMENT_PENDING,
            'total' => $pricing['total'],
            'delivery_type' => $request->delivery_type,
            'delivery_address' => $request->delivery_address,
            'delivery_phone' => $request->delivery_phone,
        ]);

        // === Позиции заказа + списание стока ===
        foreach ($cart->items as $item) {

            $itemSummary = $pricing['items'][$item->id] ?? null;
            $finalUnitPrice = $itemSummary['final_unit_price'] ?? $item->price;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'price' => $finalUnitPrice,
                'quantity' => $item->quantity,
                'rx_sph' => $item->rx_sph,
                'rx_cyl' => $item->rx_cyl,
                'rx_axis' => $item->rx_axis,
                'rx_add' => $item->rx_add,
                'rx_prism' => $item->rx_prism,
            ]);

            $stock = $item->product->stock;

            if ($stock) {
                $old = $stock->quantity;
                $new = max(0, $old - $item->quantity);

                $stock->update(['quantity' => $new]);

                StockHistory::create([
                    'stock_id' => $stock->id,
                    'type' => 'minus',
                    'quantity' => $new,
                    'previous_quantity' => $old,
                    'difference' => $new - $old,
                    'updated_by' => null,
                    'user_id' => $user->id,
                    'source' => 'order',
                    'order_id' => $order->id,
                ]);
            }
        }

        // === Очистка корзины ===
        $cart->items()->delete();

        // === Сообщение пользователю ===
        $total = number_format($order->total, 0, '.', ' ');

        $msg = $this->t($user, 'order.created_title') . "\n\n";
        $msg .= $this->t($user, 'order.number', ['id' => $order->id]) . "\n";
        $msg .= $this->t($user, 'order.total', ['total' => $total]) . "\n";
        $msg .= $this->t($user, 'order.payment', [
            'type' => $this->t($user, 'order.payment_' . $order->payment_type),
        ]) . "\n";

        $msg .= $this->t($user, 'order.delivery', [
            'type' => $this->t($user, 'order.delivery_' . $order->delivery_type),
        ]) . "\n";

        if ($order->delivery_type === 'delivery') {
            $msg .= $this->t($user, 'order.address', [
                'address' => $order->delivery_address,
            ]) . "\n";

            $msg .= $this->t($user, 'order.phone', [
                'phone' => $order->delivery_phone,
            ]) . "\n";
        }

        $msg .= "\n" . $this->t($user, 'order.thanks');

        $this->telegram->sendMessage([
            'chat_id' => $user->chat_id,
            'text' => $msg,
            'parse_mode' => 'Markdown',
        ]);

        // Локация при самовывозе
        if ($order->delivery_type === 'pickup') {
            $this->telegram->sendLocation([
                'chat_id' => $user->chat_id,
                'latitude' => 41.2995,
                'longitude' => 69.2401,
            ]);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_total_price' => $order->total,
        ]);
    }
}
