<?php

namespace App\Http\Controllers;

use App\Models\OnlineCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnlineCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search']);

        $customers = $this->baseQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (OnlineCustomer $customer) => $this->transformCustomer($customer));

        return Inertia::render('OnlineCustomers', [
            'customers' => $customers,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "online-customers_{$timestamp}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Full Name',
                'Phone Number',
                'Restaurant',
                'Street',
                'Building No',
                'Floor',
                'Apartment Number',
                'Note',
                'Source',
                'Status',
                'Created At',
                'Order ID',
                'Link Order ID',
            ]);

            $this->baseQuery($request)
                ->latest()
                ->chunkById(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $customer) {
                        fputcsv($handle, [
                            $customer->id,
                            $customer->full_name,
                            $customer->phone_number,
                            $customer->restaurant?->name,
                            $customer->street,
                            $customer->building_no,
                            $customer->floor,
                            $customer->apartment_number,
                            $customer->note,
                            $customer->source,
                            $customer->latest_status,
                            optional($customer->created_at)->toDateTimeString(),
                            $customer->order_id,
                            $customer->link_order_id,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    protected function baseQuery(Request $request)
    {
        return OnlineCustomer::query()
            ->with('restaurant')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('phone_number', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('street', 'like', "%{$search}%");
                });
            });
    }

    protected function transformCustomer(OnlineCustomer $customer): array
    {
                return [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'phone_number' => $customer->phone_number,
                    'street' => $customer->street,
                    'building_no' => $customer->building_no,
                    'floor' => $customer->floor,
                    'apartment_number' => $customer->apartment_number,
                    'note' => $customer->note,
                    'source' => $customer->source,
                    'latest_status' => $customer->latest_status,
                    'restaurant' => $customer->restaurant?->name,
                    'created_at' => $customer->created_at?->toDateTimeString(),
                    'order_id' => $customer->order_id,
                    'link_order_id' => $customer->link_order_id,
                ];
    }
}


