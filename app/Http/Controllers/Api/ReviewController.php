<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController
{
    private function user(Request $request): BotUser
    {
        return BotUser::query()->firstOrCreate([
            'chat_id' => $request->chat_id,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $reviews = Review::query()
            ->where('product_id', $request->product_id)
            ->with('user')
            ->latest()
            ->get();

        return response()->json(['reviews' => $reviews]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'nullable|string|max:500',
        ]);

        $user = $this->user($request);

        Review::query()->create([
            'product_id' => $request->product_id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'text' => $request->text,
        ]);

        return response()->json(['success' => true]);
    }
}
