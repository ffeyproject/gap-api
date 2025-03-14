<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Auth as Auths;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        // if ($this->auth->guard($guard)->guest()) {
        //     return response('Unauthorized.', 401);
        // }

        $token = $request->header('Authorization');

        if (!$token || $token == "") {
            return response()->json([
                'success'   => false,
                'message'   => 'Unauthorized',
            ], 200);
        }

        $token = str_replace('Bearer ', '', $token);
        $user = Auths::user();
        // if (Auth::check() || $user->verification_token==$token) {
        //     # code...
        // } else {
        //     if ($this->auth->guard($guard)->guest()) {
        //         return response('Unauthorized.', 401);
        //     }
        // }
        if ($user == null) {
            return response('token gak masuk.', 401);
        } else {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}