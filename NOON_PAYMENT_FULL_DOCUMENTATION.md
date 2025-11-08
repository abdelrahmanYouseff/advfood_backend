# دليل الدفع الإلكتروني (Noon Payment) في نظام AdvFood

يوفر هذا الدليل نظرة شاملة على كيفية دمج نظام AdvFood مع بوابة الدفع **Noon Payments**، بدءاً من الإعداد وحتى تدفق العمل، مروراً بمصادر الكود ذات الصلة وكيفية التعامل مع الأخطاء والـ webhooks.

---

## 1. نظرة عامة على التدفق

1. مستخدم التطبيق أو رابط الطلب يقوم بإدخال بياناته في صفحة `checkout/customer-details`.
2. يتم إنشاء طلب (`Order`) بحالة دفع `pending` وتوليد رقم (`order_number`).
3. يتم إنشاء طلب إلى Noon عبر واجهة `INITIATE` للحصول على رابط الدفع (`checkoutData.postUrl`).
4. بعد الدفع، يقوم Noon بالتحويل للصفحة `/payment-success` (عرض للمستخدم) **وبشكل أساسي** يُرسل Webhook إلى `/api/webhook` لتأكيد الدفع.
5. عند تأكيد الدفع (سواء عبر Webhook أو fallback في success عند السماح)، يتم تحديث الطلب إلى `paid`, إصدار الفاتورة، وإرسال الطلب إلى الشحن.

---

## 2. الإعدادات ومتغيرات البيئة

جميع إعدادات Noon مركزية في ملف `config/noon.php` (إن وجد) ومتغيرات البيئة التالية يجب ضبطها في `.env`:

```dotenv
NOON_API_URL=https://api-test.sa.noonpayments.com
NOON_API_KEY=...
NOON_BUSINESS_ID=adv_food
NOON_APPLICATION_ID=adv-food
NOON_SUCCESS_URL=https://advfoodapp.clarastars.com/payment-success
NOON_FAILURE_URL=https://advfoodapp.clarastars.com/payment-failed
```

> لمزيد من الأمثلة راجع الملفات التوضيحية الموجودة في المشروع مثل `README_NOON_PAYMENT.md`, `NOON_PAYMENT_INTEGRATION_GUIDE.md`, `NOON_SUCCESS_ANALYSIS.md`.

---

## 3. الملفات والمسارات الأساسية

### 3.1 التحكم في الدفع: `TestNoonController`

ملف التحكم الرئيسي للدفع موجود في `app/Http/Controllers/TestNoonController.php` ويحتوي على الآتي:

1. **createPayment()**: ينشئ طلب Noon ويرجع رابط الدفع.
2. **success()**: يعالج التوجيه بعد الدفع ويبحث عن الطلب ثم ينتظر تأكيد الـ webhook (مع خيار تخطي ذلك للـ fallback).
3. **fail()**: صفحة فشل الدفع.
4. مجموعة كبيرة من وظائف الاختبار/التشخيص (`checkApiStatus`, `testConnection`, `quickTest`, ...).

### 3.2 مسارات الويب (routes/web.php)

```84:126:routes/web.php
Route::get('/pay', [TestNoonController::class, 'createPayment'])->name('payment.create');
Route::get('/payment-success', [TestNoonController::class, 'success'])->name('payment.success');
Route::get('/payment-failed', [TestNoonController::class, 'fail'])->name('payment.fail');
Route::get('/noon/status', [TestNoonController::class, 'checkApiStatus']);
... // باقي مسارات التشخيص
```

### 3.3 مسار الـ Webhook (routes/api.php)

```33:35:routes/api.php
Route::post('/webhook', [PaymentWebhookController::class, 'handleNoon']);
```

### 3.4 `PaymentWebhookController`

ملف `app/Http/Controllers/PaymentWebhookController.php` يستقبل إشعارات Noon ويؤكد الدفع:

```app/Http/Controllers/PaymentWebhookController.php
public function handleNoon(Request $request)
{
    $payload = $request->all();
    $orderReference = $this->extractOrderReference($payload);
    $order = Order::where('payment_order_reference', $orderReference)
        ->orWhere('order_number', $orderReference)
        ->orWhere('id', $orderReference)
        ->latest()
        ->first();

    if ($this->isPaymentSuccessful($payload)) {
        $order->payment_status = 'paid';
        $order->status = 'confirmed';
        $order->payment_order_reference = $orderReference;
        $order->save();
    }

    return response()->json(['status' => 'processed']);
}
```

- الدالة `extractOrderReference` تستخرج جميع الاحتمالات (`orderReference`, `merchantOrderReference`, ...).
- الدالة `isPaymentSuccessful` تتحقق من قيم الحالة (`success`, `paid`, `resultCode` ...).

### 3.5 نموذج الطلب (Order Model)

أحداث النموذج هي ما يربط الدفع بالشحن.

```98:232:app/Models/Order.php
static::updated(function ($order) {
    if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
        (new ShippingService())->createOrder($order);
    }
});
```

- بمجرد أن يقوم الـ Webhook بضبط `payment_status = 'paid'`, تنطلق آلية إصدار الفاتورة وإرسال الطلب للشحن.

---

## 4. تدفق العمل التفصيلي

1. **جمع بيانات العميل**: عبر `checkout/customer-details` (يتعامل معها `RestLinkController`).
2. **إنشاء الطلب** (
   - `status = pending`
   - `payment_status = pending`
   - حفظ بيانات العنوان ورقم الطلب.
   - تسجيل العميل في جدول `online_customers` لأغراض التسويق والتحليلات.
3. **بدء الدفع**:
   - `RestLinkController::initiatePayment` يبني الحِمل ويرسل طلبًا لـ Noon.
   - Noon يعيد `checkoutData.postUrl` ويتم تحويل المستخدم للرابط.
4. **بعد الدفع**:
   - Noon يعيد المستخدم إلى `/payment-success?order_id=...`.
   - يتم عرض رسالة انتظار حتى وصول الـ Webhook.
5. **Webhook**:
   - Noon يستدعي `/api/webhook` بالنتيجة.
   - `PaymentWebhookController` يحدد الطلب ويضبط `payment_status` إلى `paid`.
6. **نتيجة الدفع الناجح**:
   - الحدث في نموذج `Order` يصدر الفاتورة (إن لم تكن موجودة).
   - يتم استدعاء خدمة الشحن لإرسال الطلب.

> في حالة عدم وصول الـ Webhook يمكن استخدام معامل `allow_direct_update=1` في مسار `payment-success` كحل طارئ، لكن يُفضّل الاعتماد على الـ Webhook.

---

## 5. السجلات (Logging)

الدوال تسجل الكثير من التفاصيل في `storage/logs/laravel.log`:

- عند بدء الطلب: `Noon Payment Request`، `Sending request to Noon`.
- عند الرد: `Noon Payment Response`, `Noon Payment Success`, أو رسائل الخطأ التفصيلية.
- عند نجاح الدفع (success + webhook): `PAYMENT SUCCESS CALLBACK STARTED`, `Order reference extracted`, `Order marked as paid via webhook`.

يسهل ذلك تتبع أي مشاكل أثناء مرحلة الاختبار أو الإنتاج.

---

## 6. الجداول المتأثرة

- **orders**: يتم تحديث الحقول (`payment_status`, `status`, `payment_order_reference`...).
- **online_customers**: تسجل بيانات العميل عند بدء الطلب.
- **invoices**: تصدر تلقائياً بعد تأكيد الدفع.
- **shipping_orders**: يتم إنشاء السجل عند نجاح الدفع وإرسال الطلب للشحن.

---

## 7. نقاط التكامل الإضافية

### RestLinkController

```190:369:app/Http/Controllers/RestLinkController.php
// يقوم بإنشاء الطلب واستدعاء initiatePayment مع تسجيل العميل
```

### جداول التحليلات

- `online_customers`: لا تتعلق بالدفع مباشرة لكنها جزء من تحليل معاملتي checkout.

---

## 8. استكشاف الأخطاء وإصلاحها

| المشكلة | الرسالة المحتملة | الحل |
|---------|------------------|-------|
| بيانات Noon ناقصة | `Payment configuration error` أو سجلات `Noon API credentials not configured` | تحقق من متغيرات `.env` وتشغيل `php artisan config:clear` |
| الطلب فشل عند الإنشاء | `Payment request failed` مع `status_code` | راجع الاستجابة من Noon (المرفقة بالـ log) لمعرفة السبب، غالباً بيانات غير صحيحة مثل amount أو reference | 
| الرابط غير متوفر | `checkout URL missing` | يفيد بأن الطلب لم يُقبل من Noon؛ تأكد من صلاحيات الحساب أو البيانات | 
| حالة الدفع لا تتغير | تحقق من وصول Webhook أو استخدم `allow_direct_update=1` (خيار الطوارئ) | تأكد من إعداد Noon لإرسال Webhook إلى `https://advfoodapp.clarastars.com/api/webhook` |
| Webhook لا يجد الطلب | سجلات `Order not found` | تأكد من أن `merchantOrderReference` أو `orderReference` المطابق موجود ومسجل في `orders` |

---

## 9. توصيات التشغيل

- العمل في بيئة الاختبار (Sandbox) أولاً باستخدام بيانات Noon الاختبارية (مع بطاقات Sandbox).
- مراقبة `storage/logs/laravel.log` أثناء الاختبار والتأكد من وجود رسائل النجاح.
- التأكد من إعداد الـ DNS والـ TLS بحيث تستطيع Noon الوصول إلى `/api/webhook`.
- توثيق المستخدمين والمسؤولين عن مراقبة Webhooks لمعالجة المشاكل بسرعة.
- إعداد تنبيه عندما تفشل إشعارات الدفع (يمكن إضافته مستقبلاً).

---

## 10. ملفات توثيق سابقة داخل المشروع

- `README_NOON_PAYMENT.md`: يحتوي على تفاصيل أولية وخطوات إعداد `category` إلى `pay`.
- `NOON_PAYMENT_INTEGRATION_GUIDE.md`: شرح كامل لسير العملية وتم حل مشاكل سابقة.
- `NOON_SUCCESS_ANALYSIS.md`: تحليل نجاح الاتصال.
- `NOON_ISSUE_REPORT.md`: تقرير مفصل عن المشاكل السابقة وإصلاحها.

هذه الملفات مفيدة عند الحاجة للرجوع إلى تاريخ التعديلات.

---

## خاتمة

اتبع هذا الدليل عند ضبط أو تحديث منظومة الدفع الإلكترونية. وجود Webhook يعمل ونظام سجلات واضح يضمن معالجة الطلبات بأمان، بينما تكامل نموذج الطلب مع الشحن والفوترة يختصر الوقت ويمنع الأخطاء اليدوية. لأي تغييرات مستقبلية، يُنصح بتحديث هذا المستند ليظل المتعاملون على علم بأحدث التغييرات.



