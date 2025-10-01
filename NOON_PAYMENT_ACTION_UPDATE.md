# تحديث Noon Payment Action

## المشكلة
كانت المعاملات تُرفض في مرحلة التفويض (authorization) لأن `paymentAction` كان مضبوط على `"AUTHORIZE"` بدلاً من `"SALE"`.

## الحل المطبق
تم تحديث `paymentAction` من `"AUTHORIZE"` إلى `"SALE"` في الملفات التالية:

### 1. RestLinkController.php
```php
// قبل التحديث
"paymentAction" => "AUTHORIZE"

// بعد التحديث  
"paymentAction" => "SALE"
```

### 2. TestNoonController.php
```php
// قبل التحديث
"paymentAction" => "AUTHORIZE"

// بعد التحديث
"paymentAction" => "SALE"
```

## الفرق بين AUTHORIZE و SALE

### AUTHORIZE
- يخزن المبلغ مؤقتاً (authorization only)
- يتطلب خطوة إضافية للـ capture
- قد يُرفض في مرحلة التفويض

### SALE
- يخزن المبلغ ويؤكده مباشرة (authorization + capture)
- لا يتطلب خطوة إضافية
- أكثر موثوقية للمعاملات

## متغيرات البيئة المطلوبة
تأكد من وجود المتغيرات التالية في ملف `.env`:

```env
NOON_API_KEY=your_noon_api_key_here
NOON_API_URL=https://api-test.sa.noonpayments.com
NOON_APPLICATION_ID=adv-food
NOON_BUSINESS_ID=adv_food
NOON_SUCCESS_URL=https://yourdomain.com/payment-success
NOON_FAILURE_URL=https://yourdomain.com/payment-failed
NOON_ENVIRONMENT=test
NOON_DEBUG=false
```

## اختبار التحديث
يمكنك اختبار التحديث باستخدام:
- `/test-noon` - للاختبار السريع
- `/test-noon/final-test` - للاختبار النهائي
- `/test-noon/quick-test` - للاختبار السريع بمبلغ 1 ريال

## النتيجة المتوقعة
بعد هذا التحديث، يجب أن تنجح المعاملات وتصل إلى مرحلة التأكيد (capture) بنجاح.
