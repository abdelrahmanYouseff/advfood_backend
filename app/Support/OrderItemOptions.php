<?php

namespace App\Support;

class OrderItemOptions
{
    /**
     * Normalize selected sub-items/options from various API payload shapes.
     *
     * Accepts keys: item_options, options, selected_options, sub_items, selections
     * Each entry: { name, quantity } or { item_name, qty } or plain string.
     *
     * @return array<int, array{name: string, quantity: int}>|null
     */
    public static function fromPayload(array $item): ?array
    {
        $raw = $item['item_options']
            ?? $item['options']
            ?? $item['selected_options']
            ?? $item['sub_items']
            ?? $item['selections']
            ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $normalized = [];

        foreach ($raw as $opt) {
            if (is_string($opt)) {
                $name = trim($opt);
                if ($name !== '') {
                    $normalized[] = ['name' => $name, 'quantity' => 1];
                }
                continue;
            }

            if (! is_array($opt)) {
                continue;
            }

            $name = $opt['name']
                ?? $opt['item_name']
                ?? $opt['title']
                ?? $opt['label']
                ?? null;

            if ($name === null || trim((string) $name) === '') {
                continue;
            }

            $quantity = (int) ($opt['quantity'] ?? $opt['qty'] ?? $opt['count'] ?? 1);

            $normalized[] = [
                'name'     => trim((string) $name),
                'quantity' => max(1, $quantity),
            ];
        }

        return $normalized === [] ? null : $normalized;
    }
}
