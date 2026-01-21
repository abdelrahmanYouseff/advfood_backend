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

        // Authenticate the user/branch
        $request->authenticate();

        // Get the authenticated user/branch BEFORE regenerating session
        $authenticatedUser = Auth::guard('web')->user();
        $authenticatedBranch = Auth::guard('branches')->user();
        $isBranch = $authenticatedBranch !== null;
        $remember = $request->boolean('remember');

        // Save the authenticated entity to re-login after regenerate
        $authenticatedEntity = $authenticatedBranch ?? $authenticatedUser;

        if (!$authenticatedEntity) {
            // This should not happen if authenticate() succeeded
            Log::error('❌ No authenticated user/branch found after authentication');
            return redirect()->route('login')->withErrors([
                'email' => 'Authentication failed. Please try again.',
            ]);
        }

        // Regenerate session AFTER authentication to prevent session fixation attacks
        $request->session()->regenerate();

        // Re-authenticate the user/branch after session regeneration
        // because regenerate() clears the session data including auth info
        if ($isBranch && $authenticatedBranch) {
            Auth::guard('branches')->login($authenticatedBranch, $remember);
            Log::info('🔄 Re-authenticated branch after session regenerate', [
                'branch_id' => $authenticatedBranch->id,
                'branch_name' => $authenticatedBranch->name,
            ]);
        } elseif ($authenticatedUser) {
            Auth::guard('web')->login($authenticatedUser, $remember);
            Log::info('🔄 Re-authenticated user after session regenerate', [
                'user_id' => $authenticatedUser->id,
                'user_name' => $authenticatedUser->name,
            ]);
        }

        // Check which guard authenticated the user (after re-authentication)
        $user = Auth::guard('web')->user();
        $branch = Auth::guard('branches')->user();

        $authenticated = $user ?? $branch;
        $isBranch = $branch !== null;

        // Verify authentication is still active after regenerate
        Log::info('🔍 Authentication check after regenerate', [
            'web_guard_check' => Auth::guard('web')->check(),
            'branches_guard_check' => Auth::guard('branches')->check(),
            'authenticated_id' => $authenticated?->id,
            'authenticated_name' => $authenticated?->name,
        ]);

        Log::info('✅ LOGIN SUCCESS', [
            'type' => $isBranch ? 'branch' : 'user',
            'id' => $authenticated?->id,
            'name' => $authenticated?->name,
            'email' => $authenticated?->email,
            'ip' => $request->ip(),
        ]);

        // إرسال إيميل بالفواتير بعد تسجيل الدخول (فقط للمستخدمين العاديين، ليس للفروع)
        if (!$isBranch && $user) {
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
        }

        // Use direct redirect to dashboard instead of intended()
        // because intended() may redirect to login if the user was already on login page
        Log::info('🔄 Redirecting to dashboard after login', [
            'type' => $isBranch ? 'branch' : 'user',
            'id' => $authenticated?->id,
        ]);

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        $branch = Auth::guard('branches')->user();

        $authenticated = $user ?? $branch;

        Log::info('🚪 LOGOUT', [
            'type' => $branch ? 'branch' : 'user',
            'id' => $authenticated?->id,
            'name' => $authenticated?->name,
            'ip' => $request->ip(),
        ]);

        // Logout from both guards
        Auth::guard('web')->logout();
        Auth::guard('branches')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
