<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use Illuminate\Support\Carbon;

class TokenAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Cari user berdasarkan token
        $user = User::where('fcm_token', $token)
                    ->where('token_expired', '>', Carbon::now()) // Pastikan token belum kadaluarsa
                    ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Simpan user ke request
        $request->merge(['user' => $user]);

        return $next($request);
    }
}