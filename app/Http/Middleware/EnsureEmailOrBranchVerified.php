<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailOrBranchVerified
{
    /**
     * Handle an incoming request.
     * Skip email verification for branches, require it for regular users.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is a branch, skip email verification
        if (Auth::guard('branches')->check()) {
            return $next($request);
        }

        // For regular users, check email verification
        $user = Auth::guard('web')->user();
        if ($user && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
