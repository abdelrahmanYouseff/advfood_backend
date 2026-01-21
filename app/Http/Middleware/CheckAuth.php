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
        
        // Try to get branch, but handle case where 'branches' guard might not be defined yet
        $branch = null;
        try {
            $branch = Auth::guard('branches')->user();
        } catch (\InvalidArgumentException $e) {
            // Guard not defined yet - this can happen if config/auth.php hasn't been updated on server
            // Log the error but continue - we'll only check for regular user authentication
            \Log::warning('Branches guard not defined', [
                'error' => $e->getMessage(),
                'note' => 'Make sure config/auth.php has been updated on server with branches guard and provider',
            ]);
        }
        
        if (!$user && !$branch) {
            // Not authenticated via any guard, redirect to login
            return redirect()->route('login');
        }
        
        return $next($request);
    }
}
