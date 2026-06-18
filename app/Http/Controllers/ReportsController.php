<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['order', 'user', 'restaurant'])
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
            ->latest()
            ->get()
            ->map(function ($invoice) {
                $order = $invoice->order;
                return [
                    'id'               => $invoice->id,
                    'invoice_number'   => $invoice->invoice_number,
                    'customer_name'    => $order?->delivery_name ?? $invoice->user?->name ?? 'غير معروف',
                    'restaurant'       => $invoice->restaurant?->name ?? 'غير معروف',
                    'total'            => (float) $invoice->total,
                    'payment_method'   => $order?->payment_method ?? '-',
                    'order_status'     => $order?->status ?? '-',
                    'date'             => $invoice->created_at->format('Y-m-d H:i'),
                    'is_collected'     => (bool) $invoice->is_collected,
                    'collected_at'     => $invoice->collected_at?->format('Y-m-d H:i'),
                    'collected_by'     => $invoice->collected_by,
                ];
            });

        return Inertia::render('Reports', [
            'invoices'     => $invoices,
            'auth_role'    => Auth::user()?->role,
        ]);
    }

    public function toggleCollection(Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);

        $user = Auth::user();
        $employeeName = $user?->name ?? 'موظف';

        if ($invoice->is_collected) {
            // إلغاء الترحيل
            $invoice->update([
                'is_collected' => false,
                'collected_at' => null,
                'collected_by' => null,
            ]);
        } else {
            // تأكيد الترحيل
            $invoice->update([
                'is_collected' => true,
                'collected_at' => now(),
                'collected_by' => $employeeName,
            ]);
        }

        return back();
    }
}
