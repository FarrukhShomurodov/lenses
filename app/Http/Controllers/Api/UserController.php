<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    protected function getUser(Request $request): BotUser
    {
        return BotUser::firstOrCreate([
            'chat_id' => $request->chat_id,
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json([
            'user' => [
                'first_name' => $user->first_name,
                'second_name' => $user->second_name,
                'phone' => $user->phone,
                'photo_url' => $request->photo_url ?? null,
            ],
            'orders' => $orders,
        ]);
    }

    public function checkActive(Request $request)
    {
        $chatId = $request->header('X-CHAT-ID');

    //    if (! $chatId) {
    //        return response()->json(['active' => false]);
    //    }

        $user = BotUser::query()->where('chat_id', $chatId)->first();

        return response()->json([
            'active' => true,
            'exists' => (bool) $user,
        ]);

        if (! $user || ! $user->is_active) {
            return response()->view('webapp.blocked', [], 403);
        }

        return response()->json([
            'active' => (bool) ($user && $user->is_active),
            'exists' => (bool) $user,
        ]);
    }
}
