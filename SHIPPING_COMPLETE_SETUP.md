# 🚚 دليل إعداد نظام الشحن الكامل

## ✅ ما تم إنجازه

1. ✅ إضافة حقل `shop_id` لجدول المطاعم
2. ✅ النظام يرسل تلقائياً كل طلب مدفوع لشركة الشحن
3. ✅ إنشاء أمر لإعادة إرسال الطلبات القديمة
4. ✅ تسجيل شامل في logs لتتبع العمليات

---

## 🔧 الإعداد المطلوب

### 1. إعداد shop_id لكل مطعم

**المشكلة:** شركة الشحن تحتاج `shop_id` صحيح لكل مطعم.

**الحل:**
```bash
# اتصل بشركة الشحن للحصول على shop_id لكل مطعم
# ثم قم بتحديثه في لوحة التحكم أو عبر tinker:

php artisan tinker
```

```php
// داخل tinker
use App\Models\Restaurant;

// تحديث shop_id لمطعم معين
Restaurant::where('id', 14)->update(['shop_id' => 'SHOP_ID_FROM_SHIPPING_COMPANY']);

// مثال:
Restaurant::where('name', 'Tant Bakiza')->update(['shop_id' => '821017371']);
```

### 2. التحقق من بيانات الشحن في `.env`

```env
# Shipping API Configuration
SHIPPING_API_URL=https://staging.4ulogistic.com/api/partner/
SHIPPING_API_KEY=your_actual_api_key_here
```

---

## 🔄 آلية العمل التلقائية

### عند إنشاء طلب جديد:

```
1. العميل يملأ البيانات ويضيف المنتجات
   ↓
2. يدفع عبر Noon Payment Gateway
   ↓
3. عند نجاح الدفع في TestNoonController@success:
   - تحديث payment_status = 'paid'
   - تحديث status = 'confirmed'
   - إرسال الطلب تلقائياً لشركة الشحن
   ↓
4. استلام dsp_order_id من شركة الشحن
   - حفظ dsp_order_id في الطلب
   - حفظ shipping_status
```

---

## 📋 الأوامر المتاحة

### 1. إعادة إرسال طلب معين

```bash
php artisan order:resend-shipping 52
```

**الناتج:**
```
📦 Sending Order #52 (ORD-20251001-740F48) to shipping company...
   ✅ Success! DSP Order ID: DSP-20251001-00020
   📍 Customer: أحمد محمد
   📞 Phone: +966501234567
   💰 Total: 79.00 SAR
```

### 2. إعادة إرسال جميع الطلبات المدفوعة بدون dsp_order_id

```bash
php artisan order:resend-shipping
```

**الناتج:**
```
Found 5 orders to resend...
📦 Sending Order #48...
   ✅ Success! DSP Order ID: DSP-20251001-00018
📦 Sending Order #49...
   ✅ Success! DSP Order ID: DSP-20251001-00019
...
```

---

## 🔍 التحقق من الطلبات

### عرض الطلبات المرسلة

```bash
php artisan tinker
```

```php
// عرض آخر 10 طلبات
\App\Models\Order::with('restaurant')
    ->select('id', 'order_number', 'payment_status', 'dsp_order_id', 'shop_id')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

// عرض الطلبات المدفوعة بدون dsp_order_id
\App\Models\Order::where('payment_status', 'paid')
    ->whereNull('dsp_order_id')
    ->get();
```

### فحص Logs

```bash
# عرض آخر 100 سطر من الـ log
tail -100 storage/logs/laravel.log

# البحث عن logs الشحن
grep "shipping" storage/logs/laravel.log | tail -20

# البحث عن أخطاء الشحن
grep "Failed to create shipping" storage/logs/laravel.log
```

---

## ❌ حل المشاكل الشائعة

### Problem 1: "Invalid shop"

```
Error: {"message":"Invalid shop"}
```

**السبب:** الـ `shop_id` غير موجود أو غير صحيح في نظام شركة الشحن

**الحل:**
1. اتصل بشركة الشحن للحصول على shop_id الصحيح
2. حدّث shop_id في جدول المطاعم:
   ```php
   Restaurant::find(14)->update(['shop_id' => 'CORRECT_SHOP_ID']);
   ```

### Problem 2: لم يُرسل الطلب

**الأسباب المحتملة:**
1. المطعم ليس له shop_id
2. الطلب غير مدفوع (payment_status != 'paid')
3. الطلب مُرسل مسبقاً (dsp_order_id موجود)

**الحل:**
```bash
# التحقق من حالة الطلب
php artisan tinker --execute="echo json_encode(\App\Models\Order::find(52)->toArray(), JSON_PRETTY_PRINT);"

# إعادة إرسال الطلب
php artisan order:resend-shipping 52
```

### Problem 3: API credentials خاطئة

```
Error: Unauthorized
```

**الحل:**
```bash
# التحقق من .env
grep "SHIPPING_API" .env

# تحديث الـ config cache
php artisan config:clear
php artisan config:cache
```

---

## 📊 قاعدة البيانات

### جدول `orders`

```sql
SELECT 
    id,
    order_number,
    payment_status,
    shop_id,
    dsp_order_id,
    shipping_status,
    delivery_name,
    delivery_phone,
    total
FROM orders
WHERE payment_status = 'paid'
ORDER BY created_at DESC
LIMIT 10;
```

### جدول `shipping_orders`

```sql
SELECT * FROM shipping_orders ORDER BY created_at DESC LIMIT 10;
```

### جدول `restaurants`

```sql
SELECT id, name, shop_id FROM restaurants;
```

---

## 🧪 اختبار النظام

### 1. إنشاء طلب تجريبي

1. افتح: http://127.0.0.1:8000/rest-link
2. اختر مطعم
3. أضف منتجات
4. أكمل بيانات التوصيل
5. ادفع عبر Noon (استخدم بطاقة تجريبية)
6. بعد نجاح الدفع، تحقق من الـ logs:

```bash
tail -f storage/logs/laravel.log
```

يجب أن ترى:
```
✅ Order sent to shipping company successfully
order_number: ORD-20251001-ABC123
dsp_order_id: DSP-20251001-00025
```

### 2. اختبار إعادة الإرسال

```bash
# إنشاء طلب بدون إرسال للشحن
php artisan tinker

# داخل tinker
$order = \App\Models\Order::find(52);
$order->dsp_order_id = null;
$order->save();

# ثم إعادة الإرسال
exit
php artisan order:resend-shipping 52
```

---

## 📝 الملفات المهمة

```
app/
  Services/
    ShippingService.php              # خدمة الشحن الرئيسية
  Http/Controllers/
    TestNoonController.php           # معالج نجاح الدفع (يرسل للشحن تلقائياً)
    RestLinkController.php           # إنشاء الطلبات
  Console/Commands/
    ResendOrderToShipping.php        # أمر إعادة إرسال الطلبات
  Models/
    Restaurant.php                   # يحتوي على shop_id
    Order.php                        # يحتوي على shop_id, dsp_order_id

config/
  services.php                       # إعدادات API الشحن

database/migrations/
  *_add_shop_id_to_restaurants_table.php
  *_add_shipping_fields_to_orders_table.php
  *_create_shipping_orders_table.php
```

---

## 🎯 الخطوات التالية

### 1. إعداد shop_id لكل مطعم

```bash
# تواصل مع شركة الشحن للحصول على shop_id لكل مطعم
# ثم حدّثها في قاعدة البيانات

php artisan tinker
```

```php
Restaurant::where('name', 'Tant Bakiza')->update(['shop_id' => 'ACTUAL_SHOP_ID']);
Restaurant::where('name', 'Delawa')->update(['shop_id' => 'ACTUAL_SHOP_ID']);
Restaurant::where('name', 'Gather Us')->update(['shop_id' => 'ACTUAL_SHOP_ID']);
```

### 2. إعادة إرسال الطلبات القديمة

```bash
# بعد تحديث shop_id، أعد إرسال الطلبات المدفوعة
php artisan order:resend-shipping
```

### 3. مراقبة النظام

```bash
# راقب الـ logs للتأكد من أن كل شيء يعمل
tail -f storage/logs/laravel.log | grep -i "shipping\|order"
```

---

## 📞 الدعم الفني

إذا واجهت مشاكل:

1. **تحقق من الـ Logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **تحقق من shop_id:**
   ```bash
   php artisan tinker --execute="Restaurant::all(['id','name','shop_id']);"
   ```

3. **اختبر الاتصال بـ API:**
   ```bash
   curl -X GET https://staging.4ulogistic.com/api/partner/orders \
     -H "Authorization: Bearer YOUR_API_KEY"
   ```

---

## ✨ الميزات

- ✅ **إرسال تلقائي** لكل طلب مدفوع
- ✅ **أمر إعادة إرسال** للطلبات القديمة
- ✅ **تسجيل شامل** في logs
- ✅ **معالجة الأخطاء** مع رسائل واضحة
- ✅ **دعم webhook** من شركة الشحن
- ✅ **تتبع السائق** (driver info, location)

---

**آخر تحديث:** أكتوبر 1, 2025

