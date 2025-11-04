# 📋 دليل الـ Logging الشامل

## ✅ ما يتم تسجيله تلقائياً

### 1. 🌐 كل Request على الموقع
**الـ Middleware:** `LogAllRequests`
**الملف:** `app/Http/Middleware/LogAllRequests.php`

يتم تسجيل:
- ✅ كل request (GET, POST, PUT, DELETE, etc.)
- ✅ URL كامل
- ✅ IP Address
- ✅ User Agent
- ✅ بيانات المستخدم (user_id, user_name)
- ✅ Request Data (مع إخفاء البيانات الحساسة)
- ✅ Headers المهمة
- ✅ Status Code
- ✅ وقت التنفيذ (Duration)

**مثال:**
```
🌐 Incoming Request
✅ Request Completed
```

---

### 2. 🔐 Login/Logout
**الملف:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**عند محاولة Login:**
```
🔐 LOGIN ATTEMPT
✅ LOGIN SUCCESS
```

**عند Logout:**
```
🚪 LOGOUT
```

---

### 3. 💳 عملية الدفع
**الملف:** `app/Http/Controllers/RestLinkController.php`

**عند إنشاء Order:**
```
💾 SAVE ORDER REQUEST
✅ LinkOrder created successfully
```

**عند بدء الدفع:**
```
💳 INITIATE PAYMENT REQUEST
✅ Order created for payment
🌐 Sending Noon Payment Request
📡 Noon Payment Response Received
✅ Noon Payment Success - Redirecting to checkout
```

**عند نجاح الدفع:**
```
💰 PAYMENT SUCCESS CALLBACK STARTED
🔍 STEP 1: Searching for order
📦 ORDER FOUND
✅ Payment successful for order
```

---

### 4. 📦 إرسال الطلب لشركة الشحن
**الملف:** `app/Models/Order.php` (boot method)

**عند تحديث payment_status إلى 'paid':**
```
🔄 ORDER MODEL UPDATED EVENT TRIGGERED
✅ PAYMENT_STATUS CHANGED TO PAID
🚀 CONDITIONS MET - Calling ShippingService::createOrder
```

**الملف:** `app/Services/ShippingService.php`

**عند محاولة الإرسال:**
```
📦 SHIPPINGSERVICE::createOrder CALLED
🔍 STEP 1: Checking API credentials
✅ API credentials OK
🚀 Starting shipping order creation
📤 Sending order to shipping company
✅ Shipping API Response Received
🎉 Order successfully sent to shipping company and saved!
```

---

### 5. 📋 صفحات مهمة
**Dashboard:**
```
📊 Dashboard accessed
```

**Orders Page:**
```
📋 Orders page accessed
📋 Orders loaded
```

**Restaurant Menu:**
```
🍽️ Restaurant menu page accessed
📋 Restaurant menu loaded
```

**Rest Link:**
```
🏠 Rest Link page accessed
```

---

### 6. ✅ قبول الطلبات
**الملف:** `app/Http/Controllers/OrderController.php`

```
✅ ORDER ACCEPT ACTION
📦 Order found for accept
✅ Order status updated
✅ Invoice created for accepted order
```

---

## 📊 ملخص العمليات التي يتم تسجيلها

| العملية | الرمز | الملف |
|---------|------|-------|
| كل Request | 🌐 | LogAllRequests Middleware |
| Login | 🔐 | AuthenticatedSessionController |
| Logout | 🚪 | AuthenticatedSessionController |
| إنشاء Order | 💾 | RestLinkController |
| بدء الدفع | 💳 | RestLinkController |
| نجاح الدفع | 💰 | TestNoonController |
| تحديث Order | 🔄 | Order Model |
| إرسال للشحن | 📦 | ShippingService |
| Dashboard | 📊 | DashboardController |
| Orders Page | 📋 | OrderController |
| Restaurant Menu | 🍽️ | RestLinkController |

---

## 🔍 كيفية البحث في الـ Logs

### عرض جميع الـ Logs:
```
https://advfoodapp.clarastars.com/logs
```

### البحث عن عمليات معينة:
```
# جميع عمليات الدفع
logs?filter=payment

# جميع عمليات الشحن
logs?filter=shipping

# جميع الأخطاء
logs?level=error

# جميع عمليات Login
logs?filter=LOGIN

# طلب معين
logs?filter=order_id.*123
```

---

## 📝 ملاحظات مهمة

1. ✅ **كل Request يتم تسجيله تلقائياً** من خلال `LogAllRequests` middleware
2. ✅ **كل عملية دفع** يتم تسجيلها بالتفصيل
3. ✅ **كل عملية شحن** يتم تسجيلها خطوة بخطوة
4. ✅ **كل Login/Logout** يتم تسجيله
5. ✅ **كل صفحة يتم زيارتها** يتم تسجيلها

---

## 🚀 على السيرفر

بعد رفع التغييرات، تأكد من:

1. **تفعيل الـ Logging:**
   ```bash
   # في .env
   LOG_CHANNEL=single
   LOG_LEVEL=debug
   ```

2. **إصلاح الصلاحيات:**
   ```bash
   chmod -R 775 storage/logs
   chown -R forge:forge storage/logs
   ```

3. **تحديث Config:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

4. **التحقق من الـ Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 📍 عرض الـ Logs

```
https://advfoodapp.clarastars.com/logs
```

ستجد جميع العمليات مسجلة بالتفصيل! 🎉

