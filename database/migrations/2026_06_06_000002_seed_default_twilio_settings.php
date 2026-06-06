<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (AppSetting::get('twilio_whatsapp_template_sid') === null) {
            AppSetting::set('twilio_whatsapp_template_sid', 'HXf9b9978d7c4b3c44e8ef92275e33eff4');
        }

        if (AppSetting::get('twilio_whatsapp_template_name') === null) {
            AppSetting::set('twilio_whatsapp_template_name', 'new_kitchen_order_initiate');
        }
    }

    public function down(): void
    {
        // Keep settings on rollback — credentials may already be configured.
    }
};
