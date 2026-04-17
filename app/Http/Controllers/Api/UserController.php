<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Order;
use App\Services\TelegramService;
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

    public function checkActive(Request $request): JsonResponse
    {
        $chatId = $request->header('X-CHAT-ID');

        if (! $chatId) {
            return response()->json([
                'status' => 'need_company',
                'active' => false,
                'exists' => false,
            ]);
        }

        $user = BotUser::query()->where('chat_id', $chatId)->first();

        if (! $user) {
            return response()->json([
                'status' => 'need_company',
                'active' => false,
                'exists' => false,
            ]);
        }

        if ($user->is_active && $user->step === 'done') {
            return response()->json([
                'status' => 'active',
                'active' => true,
                'exists' => true,
            ]);
        }

        if ($user->step === 'pending_approval') {
            return response()->json([
                'status' => 'pending',
                'active' => false,
                'exists' => true,
            ]);
        }

        return response()->json([
            'status' => 'need_company',
            'active' => false,
            'exists' => true,
            'prefill' => [
                'first_name' => $user->first_name,
                'phone' => $user->phone,
                'company_name' => $user->company_name,
            ],
        ]);
    }

    public function requestAccess(Request $request, TelegramService $telegram): JsonResponse
    {
        $validated = $request->validate([
            'chat_id' => ['required'],
            'company_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
            'uname' => ['nullable', 'string', 'max:100'],
        ]);

        $user = BotUser::firstOrNew(['chat_id' => $validated['chat_id']]);

        if ($user->exists && $user->is_active && $user->step === 'done') {
            return response()->json([
                'success' => true,
                'status' => 'active',
            ]);
        }

        $user->fill([
            'company_name' => $validated['company_name'],
            'first_name' => $validated['first_name'] ?? $user->first_name,
            'phone' => $validated['phone'] ?? $user->phone,
            'uname' => $validated['uname'] ?? $user->uname,
            'is_active' => false,
            'step' => 'pending_approval',
        ]);

        $user->save();

        $telegram->notifyAdminAccessRequest($user);

        return response()->json([
            'success' => true,
            'status' => 'pending',
        ]);
    }
}
