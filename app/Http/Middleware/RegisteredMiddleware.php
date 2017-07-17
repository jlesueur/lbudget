<?php

namespace LBudget\Http\Middleware;

use Closure;

class RegisteredMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
		if (!$request->user()->init_done) {
			return redirect()->intended('register');
		}
        return $next($request);
    }
}
