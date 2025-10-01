# 🛒 تدفق عملية الدفع مع Noon Payment Gateway

## نظرة عامة

تم ربط صفحة تفاصيل العميل مباشرةً مع بوابة الدفع Noon. عند الضغط على زر "Continue"، يتم:
1. حفظ الطلب في قاعدة البيانات
2. إنشاء طلب دفع في Noon
3. توجيه المستخدم مباشرة لصفحة الدفع الخاصة بـ Noon

## 📋 خطوات العملية

### 1. صفحة اختيار المطعم
```
URL: /rest-link
```
- عرض جميع المطاعم المتاحة
- المستخدم يختار مطعم

### 2. صفحة قائمة الطعام
```
URL: /restaurant/{id}
```
- عرض قائمة الطعام للمطعم المختار
- المستخدم يضيف المنتجات للسلة
- يتم حفظ البيانات في `sessionStorage`:
  - `cartData`: قائمة المنتجات
  - `cartTotal`: المبلغ الإجمالي
  - `restaurantId`: معرف المطعم

### 3. صفحة تفاصيل العميل
```
URL: /checkout/customer-details
```

**البيانات المطلوبة:**
- الاسم الكامل (Full Name)
- رقم الهاتف (Phone Number)
- رقم المبنى (Building No)
- الطابق (Floor)
- رقم الشقة (Apartment Number)
- اسم الشارع (Street)
- ملاحظات (Note - اختياري)

**عند الضغط على "Continue":**

1. يتم التحقق من وجود منتجات في السلة
2. إرسال طلب POST إلى:
   ```
   /checkout/initiate-payment
   ```

3. **البيانات المرسلة:**
   ```json
   {
     "restaurant_id": 1,
     "full_name": "أحمد محمد",
     "phone_number": "0501234567",
     "building_no": "123",
     "floor": "الأول",
     "apartment_number": "5",
     "street": "شارع الملك فهد",
     "note": "ملاحظات إضافية",
     "total": 150.50,
     "cart_items": [
       {
         "id": 1,
         "name": "برجر",
         "price": 25.00,
         "quantity": 2
       }
     ]
   }
   ```

### 4. معالجة الطلب في الخادم
```
Controller: RestLinkController@initiatePayment
```

**الخطوات:**
1. **التحقق من البيانات** (Validation)
2. **حفظ الطلب** في جدول `link_orders`
3. **إنشاء طلب دفع** في Noon API:
   ```json
   {
     "apiOperation": "INITIATE",
     "order": {
       "amount": 150.50,
       "currency": "SAR",
       "reference": "ORDER-1-1759302507",
       "name": "Order #1",
       "category": "pay"
     },
     "configuration": {
       "returnUrl": "https://advfoodapp.clarastars.com/payment-success?order_id=1",
       "paymentAction": "AUTHORIZE"
     }
   }
   ```
4. **استلام رد من Noon** يحتوي على رابط الدفع
5. **إرجاع الرد** للمتصفح:
   ```json
   {
     "success": true,
     "checkout_url": "https://pay-test.sa.noonpayments.com/...",
     "order_id": 1
   }
   ```

### 5. التوجيه لصفحة الدفع
```
المتصفح يتم توجيهه تلقائياً إلى:
https://pay-test.sa.noonpayments.com/...
```

**في هذه الصفحة:**
- المستخدم يدخل بيانات بطاقته الائتمانية
- يتم معالجة الدفع عبر Noon
- بعد الدفع، يتم التوجيه إلى:
  - **نجاح:** `/payment-success?order_id=1`
  - **فشل:** `/payment-failed?order_id=1`

## 🔐 بيانات الاتصال بـ Noon

```php
// config/noon.php
'business_id' => 'adv_food'
'application_id' => 'adv-food'
'category' => 'pay'
'currency' => 'SAR'
```

## 📊 جدول قاعدة البيانات

### جدول `link_orders`
```sql
- id
- restaurant_id (FK)
- status (pending/paid/failed)
- full_name
- phone_number
- building_no
- floor
- apartment_number
- street
- note
- total
- cart_items (JSON)
- created_at
- updated_at
```

## 🧪 اختبار النظام

### 1. التأكد من وجود بيانات في sessionStorage
```javascript
// في console المتصفح:
sessionStorage.getItem('cartData')
sessionStorage.getItem('cartTotal')
sessionStorage.getItem('restaurantId')
```

### 2. اختبار بطاقات Noon (Sandbox)
- **نجاح:** 4111 1111 1111 1111
- **فشل:** 4000 0000 0000 0002
- CVV: أي 3 أرقام
- تاريخ الانتهاء: أي تاريخ مستقبلي

### 3. فحص السجلات
```bash
tail -f storage/logs/laravel.log
```

## 🔄 سير العمل الكامل

```
[صفحة المطاعم] 
    ↓ اختيار مطعم
[صفحة القائمة]
    ↓ إضافة منتجات
[تفاصيل العميل]
    ↓ إدخال البيانات + Continue
[معالجة في الخادم]
    ↓ حفظ الطلب + إنشاء طلب Noon
[صفحة Noon للدفع]
    ↓ إدخال بيانات البطاقة
[نجاح/فشل]
    ↓
[صفحة النتيجة]
```

## ⚠️ نقاط مهمة

1. **يجب وجود بيانات في sessionStorage** قبل الانتقال لصفحة تفاصيل العميل
2. **المبلغ يجب أن يكون أكثر من 0**
3. **معرف المطعم يجب أن يكون صحيح**
4. **بيانات العميل كاملة ومطلوبة**
5. **رابط returnUrl يجب أن يكون صحيح** في البيئة الإنتاجية

## 🚀 التفعيل في بيئة الإنتاج

عند الانتقال للإنتاج:
1. تغيير `NOON_API_URL` في `.env`
2. تغيير `NOON_API_KEY` إلى مفتاح الإنتاج
3. تغيير `NOON_ENVIRONMENT=production`
4. التأكد من `returnUrl` يشير للدومين الصحيح

---

**آخر تحديث:** 1 أكتوبر 2025
**الحالة:** ✅ جاهز للاستخدام

