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

        $botUser = $chat->user;
        $botUser->update(['step' => 'done']);

        $this->sendMainMenu($chat->user->chat_id, $botUser);

        return redirect()->route('support.index');
    }

    private function sendMainMenu($chatId, $user)
    {
        $menu = Keyboard::make([
            'keyboard' => [
                [['text' => $this->t($user, 'bot.menu.orders')]],
                [['text' => $this->t($user, 'bot.menu.profile')]],
                [['text' => $this->t($user, 'bot.menu.manager')]],
                [['text' => $this->t($user, 'bot.menu.shop')]],
            ],
            'resize_keyboard' => true,
        ]);

        $tg = new Api(env('TELEGRAM_BOT_TOKEN'));
        $tg->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->t($user, 'bot.thanks', ['name' => $user->first_name]),
            'reply_markup' => $menu,
        ]);
    }

    private function t($user, $key, $replace = [])
    {
        app()->setLocale($user->lang ?? 'ru');

        return __($key, $replace);
    }
}
