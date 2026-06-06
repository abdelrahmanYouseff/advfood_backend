<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioWhatsAppService
{
    public function isConfigured(): bool
    {
        return ! empty($this->accountSid())
            && ! empty($this->authToken())
            && ! empty($this->whatsappFrom())
            && ! empty($this->templateSid());
    }

    public function accountSid(): ?string
    {
        return AppSetting::get('twilio_account_sid');
    }

    public function authToken(): ?string
    {
        return AppSetting::get('twilio_auth_token');
    }

    public function whatsappFrom(): ?string
    {
        return AppSetting::get('twilio_whatsapp_from');
    }

    public function templateSid(): ?string
    {
        return AppSetting::get('twilio_whatsapp_template_sid');
    }

    public function templateName(): ?string
    {
        return AppSetting::get('twilio_whatsapp_template_name');
    }

    /**
     * Send the kitchen order alert template to branch alert phones when eligible.
     *
     * @return array{success: bool, skipped?: bool, reason?: string, sent?: array<int, array{phone: string, message_sid: string}>, failed?: array<int, array{phone: string, error: string}>, error?: string}
     */
    public function notifyKitchenOrderCreated(Order $order): array
    {
        if (! $this->isConfigured()) {
            Log::info('Skipping kitchen WhatsApp alert — Twilio not configured', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'twilio_not_configured',
            ];
        }

        if (! $this->isKitchenEligible($order)) {
            Log::info('Skipping kitchen WhatsApp alert — order not kitchen-eligible', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'scheduled_for' => $order->scheduled_for,
            ]);

            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'not_kitchen_eligible',
            ];
        }

        $branch = $order->relationLoaded('branch')
            ? $order->branch
            : ($order->branch_id ? Branch::find($order->branch_id) : null);

        $phones = $branch?->whatsapp_alert_phones ?? [];

        if ($phones === []) {
            Log::info('Skipping kitchen WhatsApp alert — branch has no alert phones', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'branch_id' => $order->branch_id,
            ]);

            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'no_alert_phones',
            ];
        }

        $result = $this->sendTemplateToPhones(
            $phones,
            $this->buildKitchenOrderContentVariables($order)
        );

        Log::info('Kitchen WhatsApp alert dispatch finished', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'branch_id' => $order->branch_id,
            'phones_count' => count($phones),
            'sent_count' => count($result['sent'] ?? []),
            'failed_count' => count($result['failed'] ?? []),
        ]);

        return $result;
    }

    public function isKitchenEligible(Order $order): bool
    {
        if ($order->payment_status !== 'paid') {
            return false;
        }

        if ($order->scheduled_for !== null && $order->scheduled_for->isFuture()) {
            return false;
        }

        $status = strtolower((string) ($order->status ?? ''));
        $shippingStatus = strtolower((string) ($order->shipping_status ?? ''));

        if (in_array($status, ['delivered', 'cancelled'], true)) {
            return false;
        }

        if (in_array($shippingStatus, ['delivered', 'cancelled'], true)) {
            return false;
        }

        if ($order->delivered_at !== null) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function buildKitchenOrderContentVariables(Order $order): array
    {
        return [
            '1' => (string) ($order->order_number ?? $order->id),
        ];
    }

    /**
     * Send a WhatsApp content template message.
     *
     * @param  array<string, string>  $contentVariables
     * @return array{success: bool, message_sid?: string, error?: string}
     */
    public function sendTemplateMessage(string $toPhone, array $contentVariables = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Twilio WhatsApp is not fully configured. Save Account SID, Auth Token, WhatsApp From number, and template SID in Settings → Twilio.',
            ];
        }

        try {
            $client = new Client($this->accountSid(), $this->authToken());

            $payload = [
                'from' => $this->formatWhatsAppAddress($this->whatsappFrom()),
                'contentSid' => $this->templateSid(),
            ];

            if ($contentVariables !== []) {
                $payload['contentVariables'] = json_encode($contentVariables);
            }

            $message = $client->messages->create(
                $this->formatWhatsAppAddress($toPhone),
                $payload
            );

            Log::info('WhatsApp template message sent via Twilio', [
                'to' => $toPhone,
                'template_sid' => $this->templateSid(),
                'template_name' => $this->templateName(),
                'message_sid' => $message->sid,
            ]);

            return [
                'success' => true,
                'message_sid' => $message->sid,
            ];
        } catch (TwilioException $e) {
            Log::error('Twilio WhatsApp message failed', [
                'to' => $toPhone,
                'template_sid' => $this->templateSid(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send the kitchen order alert template to multiple phone numbers.
     *
     * @param  array<int, string>  $phones
     * @param  array<string, string>  $contentVariables
     * @return array{success: bool, sent: array<int, array{phone: string, message_sid: string}>, failed: array<int, array{phone: string, error: string}>, error?: string}
     */
    public function sendTemplateToPhones(array $phones, array $contentVariables = []): array
    {
        $phones = array_values(array_filter(array_map(
            fn ($phone) => trim((string) $phone),
            $phones
        )));

        if ($phones === []) {
            return [
                'success' => false,
                'sent' => [],
                'failed' => [],
                'error' => 'No phone numbers provided.',
            ];
        }

        $sent = [];
        $failed = [];

        foreach ($phones as $phone) {
            $result = $this->sendTemplateMessage($phone, $contentVariables);

            if ($result['success']) {
                $sent[] = [
                    'phone' => $phone,
                    'message_sid' => $result['message_sid'],
                ];
            } else {
                $failed[] = [
                    'phone' => $phone,
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        return [
            'success' => $failed === [],
            'sent' => $sent,
            'failed' => $failed,
            'error' => $failed !== [] ? 'One or more messages failed to send.' : null,
        ];
    }

    public function formatWhatsAppAddress(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with(strtolower($phone), 'whatsapp:')) {
            return $phone;
        }

        $digits = preg_replace('/[^\d+]/', '', $phone) ?? $phone;

        if ($digits !== '' && ! str_starts_with($digits, '+')) {
            $digits = '+'.$digits;
        }

        return 'whatsapp:'.$digits;
    }
}
