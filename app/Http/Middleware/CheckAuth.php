<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuth
{
    /**
     * Handle an incoming request.
     * Check authentication for both 'web' and 'branches' guards.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via either guard
        $user = Auth::guard('web')->user();
        $branch = Auth::guard('branches')->user();
        
        if (!$user && !$branch) {
            // Not authenticated via any guard, redirect to login
            return redirect()->route('login');
        }
        
        return $next($request);
    }
}
