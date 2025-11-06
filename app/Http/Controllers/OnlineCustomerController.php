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

        $customers = OnlineCustomer::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('phone_number', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('street', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (OnlineCustomer $customer) {
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
            });

        return Inertia::render('OnlineCustomers', [
            'customers' => $customers,
            'filters' => $filters,
        ]);
    }
}


