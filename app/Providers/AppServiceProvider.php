<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SupportChat;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('admin.partials.sidebar', function ($view) {
            if (Auth::check()) {
                $newChatsCount = SupportChat::where('status', 'new')->count();
                $view->with('newChatsCount', $newChatsCount);
            }
        });
    }
}
