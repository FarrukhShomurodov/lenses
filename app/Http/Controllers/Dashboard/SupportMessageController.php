<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

class SupportMessageController
{
    public function send(Request $request, SupportChat $chat): RedirectResponse
    {
        $request->validate(['text' => 'required|string']);

        $adminId = auth()->user()->id;

        // Save in DB
        $msg = SupportMessage::query()->create([
            'chat_id' => $chat->id,
            'admin_id' => $adminId,
            'is_from_user' => false,
            'text' => $request->text,
        ]);

        // Send to Telegram user
        $tg = new Api(env('TELEGRAM_BOT_TOKEN'));
        $tg->sendMessage([
            'chat_id' => $chat->user->chat_id,
            'text' => $msg->text,
        ]);

        return redirect()->route('support.index');
    }

    public function close(SupportChat $chat): RedirectResponse
    {
    $chat->update(['status' => 'closed']);

        $menu = Keyboard::make([
            'keyboard' => [
                [['text' => '🧾 Все заказы']],
                [['text' => '⚙ Настройки профиля']],
                [['text' => '💬 Связаться с менеджером']],
                [['text' => '🛍 Открыть магазин']],
            ],
            'resize_keyboard' => true,
        ]);

        $tg = new Api(env('TELEGRAM_BOT_TOKEN'));
        $tg->sendMessage([
            'chat_id' => $chat->user->chat_id,
            'text' => 'Так как от вас не поступило ответа, мы вынуждены завершить диалог. Чтобы вновь начать переписку с оператором, нажмите «Связаться с менеджером».',
            'reply_markup' => $menu,
        ]);

        $botUser = $chat->user;
        $botUser->update(['step' => 'done']);

        return redirect()->route('support.index');
    }
}
