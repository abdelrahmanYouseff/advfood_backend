<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginInvoicesMail;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email = $request->validated()['email'] ?? $request->get('email');
        Log::info('🔐 LOGIN ATTEMPT', [
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        Log::info('✅ LOGIN SUCCESS', [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'ip' => $request->ip(),
        ]);

        // إرسال إيميل بالفواتير بعد تسجيل الدخول
        try {
            // جلب جميع الفواتير
            $invoices = Invoice::with(['user', 'restaurant', 'order'])
                ->latest()
                ->get();

            // إرسال الإيميل إلى acc@adv-line.sa
            Mail::to('acc@adv-line.sa')->send(new LoginInvoicesMail($user, $invoices));

            Log::info('📧 Email sent with invoices after login', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'recipient' => 'acc@adv-line.sa',
                'invoices_count' => $invoices->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to send login invoices email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Log::info('🚪 LOGOUT', [
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip' => $request->ip(),
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
