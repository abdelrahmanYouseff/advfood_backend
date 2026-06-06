<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\TwilioWhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwilioSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/TwilioSettings', [
            'accountSid' => AppSetting::get('twilio_account_sid', ''),
            'hasAuthToken' => ! empty(AppSetting::get('twilio_auth_token')),
            'whatsappFrom' => AppSetting::get('twilio_whatsapp_from', ''),
            'templateSid' => AppSetting::get('twilio_whatsapp_template_sid', 'HXf9b9978d7c4b3c44e8ef92275e33eff4'),
            'templateName' => AppSetting::get('twilio_whatsapp_template_name', 'new_kitchen_order_initiate'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_sid' => ['required', 'string', 'max:255'],
            'auth_token' => ['nullable', 'string', 'max:255'],
            'whatsapp_from' => ['required', 'string', 'max:50'],
            'template_sid' => ['required', 'string', 'max:255'],
            'template_name' => ['required', 'string', 'max:255'],
        ]);

        AppSetting::set('twilio_account_sid', $validated['account_sid']);
        AppSetting::set('twilio_whatsapp_from', $validated['whatsapp_from']);
        AppSetting::set('twilio_whatsapp_template_sid', $validated['template_sid']);
        AppSetting::set('twilio_whatsapp_template_name', $validated['template_name']);

        if (! empty($validated['auth_token'])) {
            AppSetting::set('twilio_auth_token', $validated['auth_token']);
        }

        return redirect()->route('twilio-settings.index')->with('success', 'Twilio settings updated successfully.');
    }

    public function sendTest(Request $request, TwilioWhatsAppService $twilio): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $result = $twilio->sendTemplateMessage($validated['phone']);

        if (! $result['success']) {
            return back()->withErrors([
                'test_phone' => $result['error'] ?? 'Failed to send test message.',
            ]);
        }

        return back()->with('success', 'Test WhatsApp message sent successfully (SID: '.$result['message_sid'].').');
    }
}
