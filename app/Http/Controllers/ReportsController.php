<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['order.orderItems', 'user', 'restaurant'])
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
            ->latest()
            ->get()
            ->map(function ($invoice) {
                $order = $invoice->order;
                return [
                    'id'               => $invoice->id,
                    'invoice_number'   => $invoice->invoice_number,
                    'order_reference'  => $invoice->order_reference,
                    'customer_name'    => $order?->delivery_name ?? $invoice->user?->name ?? 'غير معروف',
                    'customer_phone'   => $order?->delivery_phone,
                    'customer_address' => $order?->delivery_address,
                    'restaurant'       => $invoice->restaurant?->name ?? 'غير معروف',
                    'order_number'     => $order?->order_number,
                    'subtotal'         => (float) $invoice->subtotal,
                    'delivery_fee'     => (float) $invoice->delivery_fee,
                    'tax'              => (float) $invoice->tax,
                    'total'            => (float) $invoice->total,
                    'payment_method'   => $order?->payment_method ?? '-',
                    'order_status'     => $order?->status ?? '-',
                    'date'             => $invoice->created_at->format('Y-m-d H:i'),
                    'paid_at'          => $invoice->paid_at?->format('Y-m-d H:i'),
                    'is_collected'     => (bool) $invoice->is_collected,
                    'collected_at'     => $invoice->collected_at?->format('Y-m-d H:i'),
                    'collected_by'     => $invoice->collected_by,
                    'items'            => $order?->orderItems?->map(fn($item) => [
                        'name'     => $item->item_name,
                        'quantity' => $item->quantity,
                        'price'    => (float) $item->price,
                        'subtotal' => (float) $item->subtotal,
                    ]) ?? [],
                ];
            });

        return Inertia::render('Reports', [
            'invoices'     => $invoices,
            'auth_role'    => Auth::user()?->role,
        ]);
    }

    public function exportExcel()
    {
        $filename = 'تقرير-المبيعات-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new SalesReportExport(), $filename);
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
