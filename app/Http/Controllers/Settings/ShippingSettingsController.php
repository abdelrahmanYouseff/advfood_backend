<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingSettingsController extends Controller
{
    /**
     * Show the shipping settings page.
     */
    public function index(Request $request): Response
    {
        $defaultProvider = AppSetting::get('default_shipping_provider', 'leajlak');

        return Inertia::render('settings/ShippingSettings', [
            'defaultShippingProvider' => $defaultProvider,
        ]);
    }

    /**
     * Update the shipping settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_shipping_provider' => ['required', 'in:leajlak,shadda'],
        ]);

        AppSetting::set('default_shipping_provider', $validated['default_shipping_provider']);

        return redirect()->route('shipping-settings.index')->with('status', 'Shipping settings updated successfully.');
    }
}
