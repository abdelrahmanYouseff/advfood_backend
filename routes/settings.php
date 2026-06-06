<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ShippingSettingsController;
use App\Http\Controllers\Settings\TwilioSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/shipping', [ShippingSettingsController::class, 'index'])->name('shipping-settings.index');
    Route::patch('settings/shipping', [ShippingSettingsController::class, 'update'])->name('shipping-settings.update');

    Route::get('settings/twilio', [TwilioSettingsController::class, 'index'])->name('twilio-settings.index');
    Route::patch('settings/twilio', [TwilioSettingsController::class, 'update'])->name('twilio-settings.update');
    Route::post('settings/twilio/test-message', [TwilioSettingsController::class, 'sendTest'])
        ->middleware('throttle:6,1')
        ->name('twilio-settings.test-message');
});
