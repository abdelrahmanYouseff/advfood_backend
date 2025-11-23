<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفواتير - AdvFood</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 28px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #555;
        }
        .invoices-section {
            margin-top: 30px;
        }
        .invoices-section h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .invoice-card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 15px;
            transition: box-shadow 0.3s;
        }
        .invoice-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
        }
        .invoice-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #4CAF50;
            color: white;
        }
        .status-pending {
            background-color: #ff9800;
            color: white;
        }
        .status-overdue {
            background-color: #f44336;
            color: white;
        }
        .invoice-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .invoice-total {
            text-align: left;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        .no-invoices {
            text-align: center;
            padding: 40px;
            color: #777;
            font-size: 18px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
        .summary {
            background-color: #e8f5e9;
            border-right: 4px solid #4CAF50;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-label {
            font-weight: 600;
        }
        .summary-value {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏪 نظام AdvFood</h1>
        </div>

        <div class="greeting">
            مرحباً <strong>{{ $user->name }}</strong>،
        </div>

        <p>تم تسجيل دخولك بنجاح إلى النظام. فيما يلي قائمة بالفواتير المتاحة في النظام:</p>

        @if($invoices && $invoices->count() > 0)
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">إجمالي الفواتير:</span>
                    <span class="summary-value">{{ $invoices->count() }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">إجمالي المبلغ:</span>
                    <span class="summary-value">{{ number_format($invoices->sum('total'), 2) }} ريال</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">الفواتير المدفوعة:</span>
                    <span class="summary-value">{{ $invoices->where('status', 'paid')->count() }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">الفواتير المعلقة:</span>
                    <span class="summary-value">{{ $invoices->where('status', 'pending')->count() }}</span>
                </div>
            </div>

            <div class="invoices-section">
                <h2>📋 قائمة الفواتير</h2>

                @foreach($invoices as $invoice)
                    <div class="invoice-card">
                        <div class="invoice-header">
                            <div class="invoice-number">
                                #{{ $invoice->invoice_number }}
                            </div>
                            <div class="invoice-status status-{{ $invoice->status === 'paid' ? 'paid' : ($invoice->status === 'pending' ? 'pending' : 'overdue') }}">
                                {{ $invoice->status === 'paid' ? 'مدفوعة' : ($invoice->status === 'pending' ? 'معلقة' : 'متأخرة') }}
                            </div>
                        </div>

                        <div class="invoice-details">
                            <div class="detail-item">
                                <span class="detail-label">رقم الطلب:</span>
                                <span class="detail-value">{{ $invoice->order_reference ?? 'N/A' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">المطعم:</span>
                                <span class="detail-value">{{ $invoice->restaurant->name ?? 'N/A' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">العميل:</span>
                                <span class="detail-value">{{ $invoice->user->name ?? 'N/A' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ الإنشاء:</span>
                                <span class="detail-value">{{ $invoice->created_at->format('Y-m-d') }}</span>
                            </div>
                        </div>

                        <div class="invoice-total">
                            <div class="detail-label">المبلغ الإجمالي:</div>
                            <div class="total-amount">{{ number_format($invoice->total, 2) }} ريال</div>
                        </div>

                        @if($invoice->notes)
                            <div style="margin-top: 10px; padding: 10px; background-color: #fff3cd; border-radius: 4px; font-size: 14px;">
                                <strong>ملاحظات:</strong> {{ $invoice->notes }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-invoices">
                <p>لا توجد فواتير متاحة حالياً.</p>
            </div>
        @endif

        <div class="footer">
            <p>هذا إيميل تلقائي من نظام AdvFood</p>
            <p>تاريخ الإرسال: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

