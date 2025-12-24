<?php

namespace App\Services;

use App\Contracts\ShippingServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Factory for creating shipping service instances
 * 
 * This factory returns the appropriate shipping service based on the provider name.
 */
class ShippingServiceFactory
{
    /**
     * Get the appropriate shipping service for the given provider
     *
     * @param string $provider The shipping provider name ('leajlak' or 'shadda')
     * @return ShippingServiceInterface
     * @throws \InvalidArgumentException If provider is not supported
     */
    public static function getService(string $provider): ShippingServiceInterface
    {
        Log::info('ShippingServiceFactory::getService called', [
            'provider' => $provider,
        ]);

        return match (strtolower($provider)) {
            'leajlak' => new ShippingService(),
            'shadda' => new ShaddaShippingService(),
            default => throw new \InvalidArgumentException("Unsupported shipping provider: {$provider}"),
        };
    }

    /**
     * Get the default shipping service based on app settings
     *
     * @return ShippingServiceInterface
     */
    public static function getDefaultService(): ShippingServiceInterface
    {
        $defaultProvider = \App\Models\AppSetting::get('default_shipping_provider', 'leajlak');
        return self::getService($defaultProvider);
    }
}

