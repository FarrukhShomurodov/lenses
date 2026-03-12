<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OneCApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-1C-Token') ?? $request->query('token');

        if (!$token || $token !== config('services.one_c.api_token')) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => 'Неверный или отсутствующий токен 1С',
            ], 401);
        }

        return $next($request);
    }
}
