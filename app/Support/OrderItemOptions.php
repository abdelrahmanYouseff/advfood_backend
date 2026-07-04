<?php

namespace App\Support;

class OrderItemOptions
{
    /**
     * Normalize selected sub-items/options from various API payload shapes.
     *
     * @return array<int, array{name: string, quantity: int}>|null
     */
    public static function fromPayload(array $item): ?array
    {
        $raw = $item['item_options']
            ?? $item['itemOptions']
            ?? $item['options']
            ?? $item['selected_options']
            ?? $item['selectedOptions']
            ?? $item['sub_items']
            ?? $item['subItems']
            ?? $item['selections']
            ?? $item['selected_items']
            ?? $item['selectedItems']
            ?? $item['box_items']
            ?? $item['boxItems']
            ?? $item['modifiers']
            ?? $item['modifier_items']
            ?? $item['addons']
            ?? $item['extras']
            ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }

        return self::normalize($raw);
    }

    /**
     * Normalize stored JSON / mixed shapes for API responses and the dashboard.
     *
     * @return array<int, array{name: string, quantity: int}>|null
     */
    public static function normalize(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $raw = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        foreach (['items', 'options', 'selections', 'selected_items', 'selectedItems', 'box_items', 'boxItems'] as $wrapper) {
            if (isset($raw[$wrapper]) && is_array($raw[$wrapper])) {
                $raw = $raw[$wrapper];
                break;
            }
        }

        $normalized = self::normalizeList($raw);

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int|string, mixed>  $raw
     * @return array<int, array{name: string, quantity: int}>
     */
    private static function normalizeList(array $raw): array
    {
        $normalized = [];

        // {"شيباتا بيستو": 2, "شيباتا بيض": 1}
        if (! array_is_list($raw)) {
            foreach ($raw as $key => $value) {
                if (is_string($key) && ! is_numeric($key)) {
                    if (is_numeric($value)) {
                        $name = trim($key);
                        if ($name !== '') {
                            $normalized[] = [
                                'name'     => $name,
                                'quantity' => max(1, (int) $value),
                            ];
                        }
                        continue;
                    }

                    if (is_array($value)) {
                        $entry = self::parseOptionEntry($value);
                        if ($entry !== null) {
                            $normalized[] = $entry;
                        }
                    }
                }
            }

            if ($normalized !== []) {
                return $normalized;
            }
        }

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

            $entry = self::parseOptionEntry($opt);
            if ($entry !== null) {
                $normalized[] = $entry;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $opt
     * @return array{name: string, quantity: int}|null
     */
    private static function parseOptionEntry(array $opt): ?array
    {
        $nestedItem = is_array($opt['item'] ?? null) ? $opt['item'] : null;

        $product = is_array($opt['product'] ?? null) ? $opt['product'] : null;

        $name = $opt['name']
            ?? $opt['item_name']
            ?? $opt['product_name']
            ?? $opt['productName']
            ?? $opt['title']
            ?? $opt['label']
            ?? $opt['arabic_name']
            ?? $opt['arabicName']
            ?? $opt['name_ar']
            ?? $opt['nameAr']
            ?? ($nestedItem['name'] ?? null)
            ?? ($nestedItem['item_name'] ?? null)
            ?? ($nestedItem['product_name'] ?? null)
            ?? ($product['name'] ?? null)
            ?? ($product['name_ar'] ?? null)
            ?? ($product['arabic_name'] ?? null);

        if ($name === null || trim((string) $name) === '') {
            return null;
        }

        $quantity = (int) (
            $opt['quantity']
            ?? $opt['qty']
            ?? $opt['count']
            ?? $opt['amount']
            ?? ($nestedItem['quantity'] ?? null)
            ?? ($nestedItem['qty'] ?? null)
            ?? 1
        );

        return [
            'name'     => trim((string) $name),
            'quantity' => max(1, $quantity),
        ];
    }
}
