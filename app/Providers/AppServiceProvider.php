<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        if (str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('admin.layouts.sidebar', function ($view) {
            if (Auth::check()) {
                $newChatsCount = SupportChat::whereIn('status', ['new', 'open'])->count();
                $view->with('newChatsCount', $newChatsCount);
            }
        });
    }
}
