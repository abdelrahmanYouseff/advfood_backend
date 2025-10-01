# 📦 تحديث نظام الطلبات

## ✅ ما تم تغييره

تم تحديث نظام الطلبات من `/rest-link` ليحفظ الطلبات في جدول `orders` الرئيسي بدلاً من جدول `link_orders`.

---

## 🔄 التغييرات الرئيسية

### 1. حفظ الطلبات في جدول `orders`
الآن جميع الطلبات من `/rest-link` تُحفظ في جدول `orders` الرئيسي مع:
- ✅ رقم طلب فريد (order_number): `ORD-20251001-A1B2C3`
- ✅ user_id: يستخدم حساب ضيف افتراضي
- ✅ delivery_name: اسم العميل
- ✅ delivery_phone: رقم الهاتف
- ✅ delivery_address: العنوان الكامل
- ✅ special_instructions: الملاحظات
- ✅ payment_method: `online`
- ✅ payment_status: `pending`
- ✅ status: `pending`

### 2. إنشاء Order Items
يتم إنشاء سجلات في جدول `order_items` لكل منتج في الطلب:
- order_id
- menu_item_id
- quantity
- price
- subtotal

### 3. حساب ضيف (Guest User)
- يتم إنشاء/استخدام حساب ضيف افتراضي: `guest@advfood.com`
- جميع الطلبات من غير المسجلين تُحفظ تحت هذا الحساب

---

## 📊 مقارنة البيانات

### قبل (link_orders):
```php
[
    'restaurant_id' => 1,
    'status' => 'pending',
    'full_name' => 'أحمد محمد',
    'phone_number' => '0501234567',
    'building_no' => '123',
    'floor' => 'الأول',
    'apartment_number' => '5',
    'street' => 'شارع الملك فهد',
    'note' => 'ملاحظات',
    'total' => 150.50,
    'cart_items' => [...] // JSON
]
```

### بعد (orders):
```php
[
    'order_number' => 'ORD-20251001-A1B2C3',
    'user_id' => 1, // Guest User
    'restaurant_id' => 1,
    'status' => 'pending',
    'delivery_name' => 'أحمد محمد',
    'delivery_phone' => '0501234567',
    'delivery_address' => 'مبنى 123، الطابق الأول، شقة 5، شارع الملك فهد',
    'special_instructions' => 'ملاحظات',
    'subtotal' => 150.50,
    'delivery_fee' => 0,
    'tax' => 0,
    'total' => 150.50,
    'payment_method' => 'online',
    'payment_status' => 'pending',
]

// + سجلات في order_items
```

---

## 🔧 الملفات المعدلة

### 1. `app/Http/Controllers/RestLinkController.php`

#### `initiatePayment()` Method:
```php
// قبل:
$order = LinkOrder::create([...]);

// بعد:
$guestUser = User::firstOrCreate(['email' => 'guest@advfood.com'], [...]);
$order = Order::create([...]);
foreach ($cart_items as $item) {
    OrderItem::create([...]);
}
```

#### `index()` Method:
```php
// قبل:
$order = LinkOrder::with('restaurant')->find($request->get('order_id'));

// بعد:
$order = Order::with(['restaurant', 'orderItems.menuItem'])->find($request->get('order_id'));
```

### 2. `resources/views/rest-link.blade.php`

تم تعديل البوب اب ليقرأ من Order بدلاً من LinkOrder:

```blade
// قبل:
{{ $order->full_name }}
{{ $order->phone_number }}
{{ $order->building_no }}، الطابق {{ $order->floor }}...
{{ $order->note }}
@foreach($order->cart_items as $item)

// بعد:
{{ $order->delivery_name }}
{{ $order->delivery_phone }}
{{ $order->delivery_address }}
{{ $order->special_instructions }}
@foreach($order->orderItems as $item)
    {{ $item->menuItem->name }}
```

---

## 📈 المميزات

### 1. ✅ توحيد النظام
- جميع الطلبات الآن في جدول واحد (`orders`)
- سهولة في التقارير والإحصائيات
- سهولة في إدارة الطلبات من Dashboard

### 2. ✅ Order Items منفصلة
- كل منتج له سجل منفصل
- يمكن تتبع المنتجات بسهولة
- يمكن عمل تقارير للمنتجات الأكثر مبيعاً

### 3. ✅ رقم طلب احترافي
- رقم فريد لكل طلب: `ORD-20251001-A1B2C3`
- سهل القراءة والمشاركة

### 4. ✅ تكامل مع Dashboard
- الطلبات تظهر في Dashboard الرئيسي
- يمكن إدارتها من صفحة Orders

---

## 🔍 كيفية العمل

### 1. العميل يطلب من `/rest-link`:
```
1. يختار مطعم
2. يضيف منتجات للسلة
3. يدخل بياناته (اسم، هاتف، عنوان)
4. يضغط "Continue"
```

### 2. النظام يحفظ الطلب:
```php
// ينشئ/يستخدم Guest User
$guestUser = User::firstOrCreate(['email' => 'guest@advfood.com']);

// ينشئ Order
$order = Order::create([
    'order_number' => 'ORD-20251001-A1B2C3',
    'user_id' => $guestUser->id,
    'restaurant_id' => 1,
    'delivery_name' => 'أحمد محمد',
    'delivery_phone' => '0501234567',
    'delivery_address' => 'مبنى 123...',
    'total' => 150.50,
    'payment_method' => 'online',
    'payment_status' => 'pending',
]);

// ينشئ Order Items
foreach ($cartItems as $item) {
    OrderItem::create([
        'order_id' => $order->id,
        'menu_item_id' => $item['id'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'subtotal' => $item['price'] * $item['quantity'],
    ]);
}
```

### 3. التوجيه لـ Noon للدفع:
```
→ Noon Payment Page
→ العميل يدفع
→ رجوع للموقع
→ البوب اب يظهر بتفاصيل الطلب
```

---

## 📱 البوب اب

يعرض البوب اب الآن:
- ✅ رقم الطلب: `ORD-20251001-A1B2C3`
- ✅ اسم العميل
- ✅ رقم الهاتف
- ✅ العنوان الكامل
- ✅ الملاحظات
- ✅ تفاصيل المنتجات من `orderItems`
- ✅ المجموع الكلي

---

## 🗄️ قاعدة البيانات

### جدول `orders`:
```sql
- id
- order_number (unique)
- user_id (FK to users, Guest User)
- restaurant_id (FK)
- status
- delivery_name
- delivery_phone
- delivery_address
- special_instructions
- subtotal
- delivery_fee
- tax
- total
- payment_method (online)
- payment_status (pending → paid)
- created_at
- updated_at
```

### جدول `order_items`:
```sql
- id
- order_id (FK to orders)
- menu_item_id (FK to menu_items)
- quantity
- price
- subtotal
- created_at
- updated_at
```

---

## 🎯 النتيجة

الآن جميع الطلبات (من Dashboard ومن `/rest-link`) موحدة في نظام واحد:

1. ✅ Dashboard Orders → جدول `orders`
2. ✅ Rest-Link Orders → جدول `orders`
3. ✅ جميع الطلبات قابلة للإدارة من مكان واحد
4. ✅ تقارير موحدة
5. ✅ نظام احترافي ومنظم

---

**تاريخ التحديث:** 1 أكتوبر 2025  
**الحالة:** ✅ مكتمل ويعمل

