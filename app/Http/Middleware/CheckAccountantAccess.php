<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountantAccess
{
    /**
     * السماح لمستخدم accountant بالوصول فقط إلى:
     * - /invoices  (الفواتير)
     * - /reports   (التقارير)
     * أي مسار آخر يُعاد توجيهه إلى /invoices
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->role === 'accountant') {
            $allowed = [
                'invoices',
                'reports',
            ];

            $path = ltrim($request->path(), '/');

            $isAllowed = collect($allowed)->contains(fn($prefix) => str_starts_with($path, $prefix));

            if (!$isAllowed) {
                return redirect('/invoices');
            }
        }

        return $next($request);
    }
}
