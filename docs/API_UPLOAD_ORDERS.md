# API رفع الطلبات (Upload Orders)

## المسار (Endpoint)

```
POST /api/orders/upload
```

أو استخدام نفس المنطق على:

```
POST /api/orders
```

---

## الـ Headers

| Header          | القيمة              |
|-----------------|---------------------|
| `Content-Type`  | `application/json`  |
| `Accept`        | `application/json`  |
| `Authorization` | `Bearer {token}` (اختياري إذا كانت الـ API محمية) |

---

## بيانات الطلب (Request Body) — JSON

### الحقول الإلزامية

| الحقل | النوع | القواعد | الوصف |
|--------|------|---------|--------|
| `user_id` | integer | مطلوب، موجود في `users` | معرف المستخدم صاحب الطلب |
| `restaurant_id` | integer | مطلوب، موجود في `restaurants` | معرف المطعم |
| `subtotal` | number | مطلوب، ≥ 0 | مجموع أصناف الطلب قبل التوصيل والضريبة |
| `total` | number | مطلوب، ≥ 0 | المبلغ الإجمالي (مع التوصيل والضريبة) |
| `delivery_address` | string | مطلوب، حد أقصى 255 | عنوان التوصيل |
| `delivery_phone` | string | مطلوب، حد أقصى 20 | رقم هاتف العميل |
| `delivery_name` | string | مطلوب، حد أقصى 191 | اسم العميل |
| `payment_method` | string | مطلوب | طريقة الدفع: `cash` أو `card` أو `online` |

### الحقول الاختيارية

| الحقل | النوع | القواعد | الوصف |
|--------|------|---------|--------|
| `status` | string | اختياري | حالة الطلب: `pending`, `confirmed`, `preparing`, `ready`, `delivering`, `delivered`, `cancelled` — افتراضي: `pending` |
| `delivery_fee` | number | اختياري، ≥ 0 | رسوم التوصيل — افتراضي: 0 |
| `tax` | number | اختياري، ≥ 0 | الضريبة — افتراضي: 0 |
| `special_instructions` | string | اختياري، حد أقصى 1000 | ملاحظات الطلب |
| `payment_status` | string | اختياري | حالة الدفع: `pending`, `paid`, `failed` — افتراضي: `pending` |
| `estimated_delivery_time` | string (date) | اختياري | وقت التوصيل المتوقع (صيغة تاريخ/وقت) |
| `shop_id` | string | اختياري، حد أقصى 50 | معرف المتجر عند شركة الشحن |
| `source` | string | اختياري، حد أقصى 50 | مصدر الطلب (مثل `application`, `link`) — افتراضي: `application` |
| `customer_latitude` | number | اختياري، بين -90 و 90 | خط عرض موقع العميل |
| `customer_longitude` | number | اختياري، بين -180 و 180 | خط طول موقع العميل |
| `items` | array | اختياري | قائمة أصناف الطلب (انظر الهيكل أدناه) |

### هيكل عنصر واحد داخل `items`

| الحقل | النوع | مطلوب عند إرسال `items` | الوصف |
|--------|------|-------------------------|--------|
| `menu_item_id` | integer | نعم، موجود في `menu_items` | معرف المنتج من القائمة |
| `quantity` | integer | نعم، ≥ 1 | الكمية |
| `price` | number | نعم، ≥ 0 | السعر للوحدة |
| `special_instructions` | string | لا، حد أقصى 500 | ملاحظات على الصنف |

---

## مثال طلب (Request) كامل

```json
{
  "user_id": 1,
  "restaurant_id": 1,
  "subtotal": 85.50,
  "total": 103.50,
  "delivery_address": "مبنى 5، الطابق 2، شقة 201، شارع الملك فهد",
  "delivery_phone": "0501234567",
  "delivery_name": "أحمد محمد",
  "payment_method": "cash",
  "delivery_fee": 18,
  "tax": 0,
  "special_instructions": "الطرق على الجرس مرتين",
  "customer_latitude": 24.7136,
  "customer_longitude": 46.6753,
  "items": [
    {
      "menu_item_id": 10,
      "quantity": 2,
      "price": 25.00,
      "special_instructions": "بدون بصل"
    },
    {
      "menu_item_id": 15,
      "quantity": 1,
      "price": 35.50
    }
  ]
}
```

---

## مثال استجابة ناجحة (201 Created)

```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 22,
    "order_number": "ORD-20260307-0001",
    "user_id": 1,
    "restaurant_id": 1,
    "branch_id": null,
    "status": "pending",
    "shop_id": "210",
    "subtotal": 85.50,
    "delivery_fee": 18.00,
    "tax": 0.00,
    "total": 103.50,
    "delivery_address": "مبنى 5، الطابق 2، شقة 201، شارع الملك فهد",
    "delivery_phone": "0501234567",
    "delivery_name": "أحمد محمد",
    "customer_latitude": 24.7136,
    "customer_longitude": 46.6753,
    "special_instructions": "الطرق على الجرس مرتين",
    "payment_method": "cash",
    "payment_status": "pending",
    "source": "application",
    "created_at": "2026-03-07T12:00:00.000000Z",
    "updated_at": "2026-03-07T12:00:00.000000Z",
    "user": { ... },
    "restaurant": { ... },
    "order_items": [ ... ]
  },
  "shipping": {
    "created": true,
    "dsp_order_id": "12345",
    "shipping_status": "New Order"
  }
}
```

---

## استجابة خطأ التحقق (422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "user_id": ["The user id field is required."],
    "delivery_phone": ["The delivery phone field is required."]
  }
}
```

---

## ملاحظات

1. **رقم الطلب:** يُنشأ تلقائياً بصيغة `ORD-YYYYMMDD-XXXX` ولا يُرسل في الطلب.
2. **shop_id:** إذا لم يُرسل يُؤخذ من المطعم أو الفرع (حسب الإحداثيات) أو القيمة الافتراضية.
3. **الإحداثيات:** إذا أرسلت `customer_latitude` و `customer_longitude` يُحدد أقرب فرع ويُستخدم الـ `shop_id` المرتبط به إن وُجد.
4. **الشحن:** قد يتم إنشاء طلب الشحن تلقائياً عند المنصة حسب إعدادات النظام.
