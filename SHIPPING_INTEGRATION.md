# 🚚 تكامل شركة الشحن - مكتمل

## ✅ الحالة: مكتمل ويعمل

تم ربط طلبات `/rest-link` مع شركة الشحن اللوجستيك.

---

## 🔄 كيف يعمل؟

### 1. إنشاء الطلب
عند إنشاء طلب من `/rest-link`:
```php
$order = Order::create([
    'order_number' => 'ORD-20251001-A1B2C3',
    'shop_id' => $restaurant_id, // استخدام restaurant_id كـ shop_id
    'delivery_name' => 'أحمد محمد',
    'delivery_phone' => '0501234567',
    'delivery_address' => 'العنوان الكامل',
    'total' => 150.50,
    'payment_method' => 'online',
    'payment_status' => 'pending',
]);
```

### 2. بعد الدفع الناجح
في `TestNoonController@success`:
```php
// 1. تحديث حالة الدفع
$order->payment_status = 'paid';
$order->save();

// 2. إرسال للشحن
$shippingService = new ShippingService();
$shippingResult = $shippingService->createOrder($order);

// 3. تحديث Order بـ dsp_order_id
$order->dsp_order_id = $shippingResult['dsp_order_id'];
$order->shipping_status = $shippingResult['shipping_status'];
$order->save();
```

---

## 📊 البيانات المرسلة لشركة الشحن

```json
{
  "id": "ORD-20251001-A1B2C3",
  "shop_id": "1",
  "delivery_details": {
    "name": "أحمد محمد",
    "phone": "0501234567",
    "coordinate": {
      "latitude": null,
      "longitude": null
    },
    "address": "مبنى 123، الطابق الأول، شقة 5، شارع الملك فهد"
  },
  "order": {
    "payment_type": 0,
    "total": 150.50,
    "notes": "ملاحظات العميل"
  }
}
```

---

## 📋 payment_type

الـ `ShippingService` يحول `payment_method` إلى `payment_type`:

```php
'cash' => 1
'machine' => 10
'online' => 0
```

---

## 🗄️ جدول shipping_orders

يتم إنشاء سجل في `shipping_orders`:

```sql
- order_id
- shop_id
- dsp_order_id (من شركة الشحن)
- shipping_status (New Order, In Transit, Delivered, etc.)
- recipient_name
- recipient_phone
- recipient_address
- latitude
- longitude
- driver_name
- driver_phone
- driver_latitude
- driver_longitude
- total
- payment_type
- notes
```

---

## 🔄 التدفق الكامل

```
[عميل يطلب من rest-link]
         ↓
[إدخال بيانات + Continue]
         ↓
[حفظ في orders + order_items]
         ↓
[إنشاء طلب دفع Noon]
         ↓
[توجيه لصفحة Noon]
         ↓
[دفع ناجح]
         ↓
[تحديث payment_status = 'paid']
         ↓
[🚚 إرسال لشركة الشحن]
         ↓
[تحديث dsp_order_id + shipping_status]
         ↓
[عرض البوب اب للعميل]
```

---

## ⚙️ الإعدادات المطلوبة

في `.env`:
```env
SHIPPING_API_URL=https://api.shipping-company.com
SHIPPING_API_KEY=your_api_key
SHIPPING_SEND_AS_FORM=false
SHIPPING_CANCEL_METHOD=delete
```

في `config/services.php`:
```php
'shipping' => [
    'url' => env('SHIPPING_API_URL'),
    'key' => env('SHIPPING_API_KEY'),
    'endpoints' => [
        'create' => '/orders',
        'status' => '/orders/{id}',
        'cancel' => '/orders/{id}',
    ],
    'send_as_form' => env('SHIPPING_SEND_AS_FORM', false),
    'cancel_method' => env('SHIPPING_CANCEL_METHOD', 'delete'),
],
```

---

## 📝 ملاحظات مهمة

### 1. shop_id
- يتم استخدام `restaurant_id` كـ `shop_id`
- هذا يضمن ربط كل طلب بالمطعم الصحيح

### 2. Coordinates (اختياري)
- `latitude` و `longitude` اختياريين
- حالياً = `null` (يمكن إضافتهم لاحقاً)

### 3. معالجة الأخطاء
- إذا فشل إرسال الطلب للشحن، يتم تسجيل الخطأ في Log
- الطلب يظل محفوظ في النظام
- يمكن إعادة إرسال الطلب يدوياً

### 4. dsp_order_id
- رقم الطلب من شركة الشحن
- يُستخدم لتتبع حالة الشحن
- يُحفظ في جدول `orders` و `shipping_orders`

---

## 🔍 التتبع والمراقبة

### فحص السجلات:
```bash
tail -f storage/logs/laravel.log | grep -i shipping
```

### رسائل Log الناجحة:
```
Order sent to shipping company successfully
dsp_order_id: ORD-20251001-00020
```

### رسائل Log الفاشلة:
```
Failed to send order to shipping company
order_id: 123
```

---

## 🧪 الاختبار

### 1. اختبر إنشاء طلب:
```php
$shippingService = new ShippingService();
$result = $shippingService->createOrder($order);

if ($result) {
    echo "✅ تم الإرسال بنجاح";
    echo "dsp_order_id: " . $result['dsp_order_id'];
} else {
    echo "❌ فشل الإرسال";
}
```

### 2. اختبر حالة الطلب:
```php
$shippingService = new ShippingService();
$status = $shippingService->getOrderStatus($dspOrderId);
```

### 3. اختبر إلغاء الطلب:
```php
$shippingService = new ShippingService();
$cancelled = $shippingService->cancelOrder($dspOrderId);
```

---

## ✅ المميزات

1. **✅ تكامل تلقائي:** الطلب يُرسل تلقائياً بعد الدفع الناجح
2. **✅ معالجة أخطاء:** إذا فشل الإرسال، الطلب يظل محفوظ
3. **✅ تتبع كامل:** dsp_order_id و shipping_status
4. **✅ سجلات مفصلة:** جميع العمليات مسجلة في Log
5. **✅ جدول منفصل:** shipping_orders لتتبع الشحنات

---

## 🎯 النتيجة

الآن عند:
1. ✅ العميل يطلب من `/rest-link`
2. ✅ يدفع عبر Noon
3. ✅ الطلب يُحفظ في `orders`
4. ✅ الطلب يُرسل تلقائياً لشركة الشحن 🚚
5. ✅ يتم حفظ `dsp_order_id` للتتبع
6. ✅ البوب اب يظهر للعميل مع تفاصيل الطلب

---

**تاريخ التكامل:** 1 أكتوبر 2025  
**الحالة:** ✅ مكتمل ويعمل  
**النظام:** متكامل بالكامل (Noon + Shipping)

