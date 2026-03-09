<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\BotUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Throwable;

class BotUserController
{
    public function index(Request $request): View
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $users = BotUser::query()
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.bot_users.index', compact('users', 'dateFrom', 'dateTo'));
    }

    public function toggleBlock(BotUser $user): JsonResponse
    {
        $wasBlocked = ! $user->is_active;

        $updateData = ['is_active' => ! $user->is_active];

        // При разблокировке — ставим step = done, чтобы главное меню работало
        if ($wasBlocked) {
            $updateData['step'] = 'done';
        }

        $user->update($updateData);

        // Отправить уведомление в Telegram при разблокировке
        if ($wasBlocked && $user->is_active) {
            try {
                $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

                app()->setLocale($user->lang ?? 'ru');

                $menu = Keyboard::make([
                    'keyboard' => [
                        [['text' => __('bot.menu.orders')]],
                        [['text' => __('bot.menu.profile')]],
                        [['text' => __('bot.menu.manager')]],
                        [['text' => __('bot.menu.shop')]],
                    ],
                    'resize_keyboard' => true,
                ]);

                $telegram->sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => __('bot.access_granted'),
                    'reply_markup' => $menu,
                ]);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'is_active' => $user->is_active,
        ]);
    }
}
