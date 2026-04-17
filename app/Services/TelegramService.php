<?php

namespace App\Services;

use App\Models\BotUser;
use Telegram\Bot\Api;

class TelegramService
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    public function notifyAdminAccessRequest(BotUser $user): void
    {
        $adminChatId = env('TELEGRAM_ADMIN_CHAT_ID');
        if (! $adminChatId) {
            return;
        }

        $tgLink = $user->uname ? "@{$user->uname}" : "chat_id: {$user->chat_id}";

        $text = "📋 *Запрос на доступ*\n\n"
            . "👤 Имя: " . ($user->first_name ?: '—') . "\n"
            . "🏢 Фирма: " . ($user->company_name ?: '—') . "\n"
            . "📞 Телефон: " . ($user->phone ?: '—') . "\n"
            . "🔗 Telegram: {$tgLink}";

        $this->telegram->sendMessage([
            'chat_id' => $adminChatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[
                    [
                        'text' => '✅ Разблокировать',
                        'callback_data' => 'unblock_' . $user->id,
                    ],
                ]],
            ]),
        ]);
    }
}
