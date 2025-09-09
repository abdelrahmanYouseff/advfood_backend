<?php

namespace App\Http\Controllers;

use App\Models\LinkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LinkOrderController extends Controller
{
    public function index()
    {
        $orders = LinkOrder::with('restaurant')
            ->latest()
            ->paginate(15);

        return Inertia::render('LinkOrders', [
            'orders' => $orders
        ]);
    }

    public function show(LinkOrder $linkOrder)
    {
        $linkOrder->load('restaurant');

        return Inertia::render('LinkOrderShow', [
            'order' => $linkOrder
        ]);
    }

    public function updateStatus(Request $request, LinkOrder $linkOrder)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled'
        ]);

        $linkOrder->update([
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }
}
