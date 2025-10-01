# دليل إعداد نظام الشحن - Shipping Integration Setup

## نظرة عامة
النظام يرسل **تلقائياً** جميع الطلبات الجديدة إلى API شركة الشحن بعد نجاح الدفع.

## آلية العمل

### 1. عند إنشاء طلب جديد
```
العميل يدفع عبر Noon Payment
    ↓
عند نجاح الدفع
    ↓
تحديث حالة الطلب إلى "confirmed"
    ↓
إرسال بيانات الطلب تلقائياً لشركة الشحن
    ↓
حفظ dsp_order_id و shipping_status
```

### 2. البيانات المُرسلة لشركة الشحن
```php
{
    "id": "ORD-20240101-ABC123",           // رقم الطلب
    "shop_id": "restaurant_id",            // معرف المطعم
    "delivery_details": {
        "name": "اسم العميل",
        "phone": "رقم الهاتف",
        "coordinate": {
            "latitude": 24.7136,
            "longitude": 46.6753
        },
        "address": "العنوان الكامل"
    },
    "order": {
        "payment_type": 1,                 // 1=كاش, 10=آلة دفع
        "total": 150.00,
        "notes": "ملاحظات خاصة"
    }
}
```

## إعداد متغيرات البيئة (.env)

أضف المتغيرات التالية في ملف `.env`:

```env
# Shipping Company API Configuration
SHIPPING_API_URL=https://api.shipping-company.com
SHIPPING_API_KEY=your_api_key_here

# Shipping API Endpoints (اختياري - افتراضياً)
SHIPPING_API_ENDPOINT_CREATE=/orders
SHIPPING_API_ENDPOINT_STATUS=/orders/{id}
SHIPPING_API_ENDPOINT_CANCEL=/orders/{id}

# طريقة الإلغاء (delete أو post)
SHIPPING_API_CANCEL_METHOD=delete

# إرسال البيانات كـ form data بدلاً من JSON
SHIPPING_API_SEND_AS_FORM=false
```

## التحقق من عمل النظام

### 1. فحص الـ Logs
```bash
tail -f storage/logs/laravel.log
```

بعد نجاح طلب جديد، ستجد:
```
✅ Order sent to shipping company successfully
order_id: 123
order_number: ORD-20240101-ABC123
dsp_order_id: DSP-20240101-00020
shipping_status: New Order
customer_name: أحمد محمد
customer_phone: +966501234567
customer_address: مبنى 10، الطابق 3، شقة 5، شارع الملك فهد
```

### 2. فحص قاعدة البيانات
```sql
-- عرض الطلبات مع معلومات الشحن
SELECT 
    id,
    order_number,
    dsp_order_id,
    shipping_status,
    payment_status,
    delivery_name,
    delivery_phone,
    created_at
FROM orders 
WHERE payment_status = 'paid'
ORDER BY created_at DESC;
```

### 3. عرض سجلات الشحن
```sql
SELECT * FROM shipping_orders ORDER BY created_at DESC LIMIT 10;
```

## معالجة الأخطاء

### إذا لم يتم إرسال الطلب
1. تحقق من متغيرات البيئة:
```bash
php artisan config:cache
php artisan config:clear
```

2. تحقق من الـ Logs:
```bash
grep "shipping" storage/logs/laravel.log
```

3. اختبار API شركة الشحن يدوياً:
```bash
curl -X POST https://api.shipping-company.com/orders \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "TEST-001",
    "shop_id": "1",
    "delivery_details": {
      "name": "Test User",
      "phone": "+966500000000",
      "address": "Test Address"
    },
    "order": {
      "payment_type": 1,
      "total": 100.00
    }
  }'
```

## الرموز والحالات

### Payment Status
- `pending` - في انتظار الدفع
- `paid` - تم الدفع ✅
- `failed` - فشل الدفع

### Order Status
- `pending` - قيد الانتظار
- `confirmed` - تم التأكيد ✅ (يتم الإرسال للشحن هنا)
- `preparing` - قيد التحضير
- `ready` - جاهز
- `delivering` - قيد التوصيل
- `delivered` - تم التوصيل
- `cancelled` - ملغي

### Shipping Status
- `New Order` - طلب جديد (تم الإرسال لشركة الشحن)
- `Assigned` - تم تعيين سائق
- `Picked Up` - تم الاستلام من المطعم
- `On The Way` - في الطريق
- `Delivered` - تم التوصيل
- `cancelled` - ملغي

## الملفات المهمة

```
app/
  Services/
    ShippingService.php          # خدمة الشحن الرئيسية
  Http/Controllers/
    TestNoonController.php       # معالج نجاح الدفع (يرسل للشحن)
config/
  services.php                   # إعدادات API الشحن
database/
  migrations/
    *_create_shipping_orders_table.php  # جدول سجلات الشحن
```

## API Reference

### إنشاء طلب شحن جديد
```php
$shippingService = new \App\Services\ShippingService();
$result = $shippingService->createOrder($order);

// Returns
[
    'order_id' => 123,
    'shop_id' => '1',
    'dsp_order_id' => 'DSP-20240101-00020',
    'shipping_status' => 'New Order',
    'recipient_name' => 'اسم العميل',
    'recipient_phone' => '+966501234567',
    'recipient_address' => 'العنوان',
    'total' => 150.00,
    ...
]
```

### الحصول على حالة الطلب
```php
$shippingService = new \App\Services\ShippingService();
$status = $shippingService->getOrderStatus($dspOrderId);
```

### إلغاء طلب شحن
```php
$shippingService = new \App\Services\ShippingService();
$result = $shippingService->cancelOrder($dspOrderId);
```

## Webhook من شركة الشحن

إذا كانت شركة الشحن ترسل webhooks لتحديث حالة الطلب:

```php
// في routes/api.php
Route::post('/webhook/shipping', [ShippingController::class, 'webhook']);

// في Controller
public function webhook(Request $request)
{
    $shippingService = new \App\Services\ShippingService();
    $shippingService->handleWebhook($request);
    return response()->json(['success' => true]);
}
```

## الدعم الفني

في حالة وجود مشاكل:
1. تحقق من الـ logs في `storage/logs/laravel.log`
2. تأكد من صحة API credentials
3. تواصل مع شركة الشحن للتأكد من API endpoints

---

**ملاحظة مهمة:** 
- النظام يرسل الطلبات **تلقائياً** بعد نجاح الدفع فقط
- إذا فشل الدفع، لن يتم إرسال الطلب لشركة الشحن
- يتم حفظ جميع محاولات الإرسال في الـ logs

