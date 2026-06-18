<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    public function title(): string
    {
        return 'تقرير المبيعات';
    }

    public function collection()
    {
        return Invoice::with(['order', 'user', 'restaurant'])
            ->whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'اسم العميل',
            'رقم الفاتورة',
            'رقم الطلب',
            'اسم المطعم',
            'قيمة المنتجات',
            'رسوم التوصيل',
            'الضريبة',
            'المبلغ الإجمالي',
            'طريقة الدفع',
            'حالة الطلب',
            'حالة التحصيل',
            'تاريخ الفاتورة',
            'تاريخ التحصيل',
            'اسم الموظف',
        ];
    }

    public function map($invoice): array
    {
        $order = $invoice->order;

        $paymentMap = ['online' => 'دفع إلكتروني', 'cash' => 'نقدي', 'card' => 'بطاقة'];
        $statusMap  = [
            'pending'    => 'معلق',
            'confirmed'  => 'مؤكد',
            'preparing'  => 'قيد التحضير',
            'ready'      => 'جاهز',
            'delivering' => 'جاري التوصيل',
            'delivered'  => 'مُسلَّم',
            'cancelled'  => 'ملغي',
        ];

        return [
            $order?->delivery_name ?? $invoice->user?->name ?? 'غير معروف',
            $invoice->invoice_number,
            $order?->order_number ?? '-',
            $invoice->restaurant?->name ?? 'غير معروف',
            (float) $invoice->subtotal,
            (float) $invoice->delivery_fee,
            (float) $invoice->tax,
            (float) $invoice->total,
            $paymentMap[$order?->payment_method] ?? $order?->payment_method ?? '-',
            $statusMap[$order?->status] ?? $order?->status ?? '-',
            $invoice->is_collected ? 'محصّلة' : 'غير محصّلة',
            $invoice->created_at->format('Y-m-d H:i'),
            $invoice->collected_at?->format('Y-m-d H:i') ?? '-',
            $invoice->collected_by ?? '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,  // اسم العميل
            'B' => 20,  // رقم الفاتورة
            'C' => 20,  // رقم الطلب
            'D' => 20,  // اسم المطعم
            'E' => 16,  // قيمة المنتجات
            'F' => 16,  // رسوم التوصيل
            'G' => 14,  // الضريبة
            'H' => 16,  // الإجمالي
            'I' => 16,  // طريقة الدفع
            'J' => 16,  // حالة الطلب
            'K' => 16,  // حالة التحصيل
            'L' => 20,  // تاريخ الفاتورة
            'M' => 20,  // تاريخ التحصيل
            'N' => 20,  // اسم الموظف
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        // RTL direction
        $sheet->setRightToLeft(true);

        // Header row style
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'], // emerald-500
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D1FAE5'],
                ],
            ],
        ]);

        // Row height for header
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows style
        if ($lastRow > 1) {
            $sheet->getStyle("A2:N{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);

            // Zebra striping
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F9FAFB'],
                        ],
                    ]);
                }
                $sheet->getRowDimension($row)->setRowHeight(22);
            }

            // Highlight "محصّلة" cells in column K
            for ($row = 2; $row <= $lastRow; $row++) {
                $cellVal = $sheet->getCell("K{$row}")->getValue();
                if ($cellVal === 'محصّلة') {
                    $sheet->getStyle("K{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '065F46'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
                    ]);
                } else {
                    $sheet->getStyle("K{$row}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '92400E']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    ]);
                }
            }
        }

        return [];
    }
}
