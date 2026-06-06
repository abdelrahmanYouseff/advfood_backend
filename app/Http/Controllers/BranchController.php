<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\TwilioWhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index(): Response
    {
        $branches = Branch::orderBy('name')->get();

        \Log::info('📍 Branches page accessed', [
            'branches_count' => $branches->count(),
            'branches' => $branches->toArray(),
        ]);

        return Inertia::render('Branches', [
            'branches' => $branches,
        ]);
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(): Response
    {
        return Inertia::render('BranchCreate');
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:branches,email',
            'password' => 'required|string|min:8',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['password'] = bcrypt($validated['password']);

        Branch::create($validated);

        return redirect()->route('branches.index')
            ->with('success', 'تم إضافة الفرع بنجاح');
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): Response
    {
        return Inertia::render('BranchShow', [
            'branch' => $branch,
        ]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch): Response
    {
        return Inertia::render('BranchEdit', [
            'branch' => $branch,
        ]);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:branches,email,' . $branch->id,
            'password' => 'nullable|string|min:8',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', 'تم تحديث الفرع بنجاح');
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'تم حذف الفرع بنجاح');
    }

    /**
     * Toggle the branch status (active/inactive).
     */
    public function toggleStatus(Branch $branch)
    {
        $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->update(['status' => $newStatus]);

        \Log::info('🔄 Branch status toggled', [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'old_status' => $branch->status === 'active' ? 'inactive' : 'active',
            'new_status' => $newStatus,
        ]);

        return back()->with('success', $newStatus === 'active' 
            ? 'تم تفعيل الفرع بنجاح' 
            : 'تم تعطيل الفرع بنجاح'
        );
    }

    /**
     * Update WhatsApp alert phone numbers for a branch.
     */
    public function updateWhatsappAlertPhones(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_alert_phones' => ['nullable', 'array'],
            'whatsapp_alert_phones.*' => ['string', 'max:50'],
        ]);

        $phones = collect($validated['whatsapp_alert_phones'] ?? [])
            ->map(fn ($phone) => trim((string) $phone))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $branch->update(['whatsapp_alert_phones' => $phones !== [] ? $phones : null]);

        return back()->with('success', 'تم تحديث أرقام تنبيهات الواتساب بنجاح');
    }

    /**
     * Send a test WhatsApp template message to a branch alert phone.
     */
    public function sendTestWhatsappMessage(Request $request, Branch $branch, TwilioWhatsAppService $twilio): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $phones = $branch->whatsapp_alert_phones ?? [];

        if (! empty($validated['phone'])) {
            $phones = [trim($validated['phone'])];
        }

        if ($phones === []) {
            return back()->withErrors([
                'whatsapp_test' => 'أضف رقم واتساب واحد على الأقل قبل إرسال رسالة تجريبية.',
            ]);
        }

        $result = $twilio->sendTemplateToPhones($phones);

        if (! $result['success']) {
            $firstError = $result['failed'][0]['error'] ?? $result['error'] ?? 'Failed to send test message.';

            return back()->withErrors([
                'whatsapp_test' => $firstError,
            ]);
        }

        $count = count($result['sent']);

        return back()->with('success', "تم إرسال {$count} رسالة تجريبية عبر الواتساب بنجاح");
    }
}
