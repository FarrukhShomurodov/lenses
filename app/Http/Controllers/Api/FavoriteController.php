<?php

namespace App\Http\Controllers\Telegram\Api;

use App\Models\BotUser;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController
{
    protected function getUser(Request $request): BotUser
    {
        return BotUser::firstOrCreate([
            'chat_id' => $request->chat_id,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $favorites = Favorite::query()
            ->where('user_id', $user->id)
            ->with('product.images')
            ->get();

        return response()->json(['favorites' => $favorites]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $product = Product::findOrFail($request->product_id);

        $fav = Favorite::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($fav) {
            $fav->delete();

            return response()->json(['favorite' => false]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['favorite' => true]);
    }

    public function check(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $exists = Favorite::query()
            ->where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->exists();

        return response()->json(['favorite' => $exists]);
    }
}
