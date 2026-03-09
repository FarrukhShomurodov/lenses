<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\OrdersExport;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Telegram\Bot\Api;

class OrderController
{
    /**
     * Сгенерировать PDF-накладную для заказа
     */
    public function invoicePdf(Order $order)
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.orders.invoice', compact('order'));
        return $pdf->download('invoice_order_' . $order->id . '.pdf');
    }
    
    public function index(Request $request): View
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $search = trim((string) $request->query('search', ''));

        $sortBy = in_array($request->query('sort_by'), [
            'id',
            'total',
            'status',
            'payment_status',
            'delivery_type',
            'created_at'
        ]) ? $request->query('sort_by') : 'id';

        $sortDir = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        $orders = Order::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('id', $search)
                        ->orWhere('delivery_phone', 'ILIKE', "%{$search}%")
                        ->orWhere('delivery_address', 'ILIKE', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'ILIKE', "%{$search}%")
                                ->orWhere('second_name', 'ILIKE', "%{$search}%")
                                ->orWhere('phone', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderBy($sortBy, $sortDir)
            ->get();

        return view('admin.orders.index', compact('orders', 'dateFrom', 'dateTo', 'search'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        try {
            $request->validate([
                'status' => 'required|in:new,in_process,done,canceled',
            ]);

            // Если заказ отменен — возвращаем товар на склад
            if ($request->status == 'canceled') {

                foreach ($order->items as $item) {

                    // Пропускаем Rx-заказы (без product_id) и товары без продукта
                    if (! $item->product_id || ! $item->product) {
                        continue;
                    }

                    $stock = $item->product->stock;

                    if (! $stock) {
                        continue; // если на товар нет Stock — пропускаем
                    }

                    $old = $stock->quantity;
                    $returnQty = $item->quantity;

                    // Увеличиваем склад
                    $stock->quantity += $returnQty;
                    $stock->save();

                    // Записываем историю
                    $stock->history()->create([
                        'type' => 'plus',
                        'quantity' => $returnQty,
                        'previous_quantity' => $old,
                        'difference' => $returnQty,
                        'updated_by' => Auth::id(),
                        'order_id' => $order->id,
                        'source' => 'order',
                        'user_id' => $order->user_id,
                    ]);
                }
            }

            // Обновляем статус заказа
            $order->update([
                'status' => $request->status,
            ]);

            // Отправляем уведомление в Telegram только при отмене заказа
            if ($request->status == 'canceled' && $order->user && $order->user->chat_id) {
                try {
                    $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
                    app()->setLocale($order->user->lang ?? 'ru');
                    
                    $message = __('bot.order_canceled', ['order_id' => $order->id]);
                    
                    $telegram->sendMessage([
                        'chat_id' => $order->user->chat_id,
                        'text' => $message,
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Ошибка отправки уведомления в Telegram для заказа #{$order->id}: {$e->getMessage()}");
                }
            }

            return response()->json([
                'success' => true,
                'status' => $order->status,
                'statusName' => $order->status_name,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Ошибка обновления статуса заказа: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export()
    {
        return Excel::download(new OrdersExport, 'orders.xlsx');
    }
}
