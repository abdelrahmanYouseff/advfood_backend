# 📚 توثيق شامل لنظام AdvFood Backend

## 📋 جدول المحتويات
1. [نظرة عامة على النظام](#نظرة-عامة-على-النظام)
2. [الكونترولرز (Controllers)](#الكونترولرز-controllers)
3. [الخدمات (Services)](#الخدمات-services)
4. [الروتس (Routes)](#الروتس-routes)
5. [الوظائف الرئيسية](#الوظائف-الرئيسية)

---

## 🎯 نظرة عامة على النظام

نظام AdvFood هو نظام إدارة مطاعم وطلبات طعام مبني على Laravel مع واجهة Inertia.js. يدعم النظام:
- إدارة المطاعم والمنتجات
- إدارة الطلبات والتوصيل
- الدفع الإلكتروني عبر Noon Payments
- التكامل مع شركات الشحن
- نظام النقاط للعملاء
- مزامنة طلبات Zyda

---

## 🎮 الكونترولرز (Controllers)

### 1. OrderController
**المسار:** `app/Http/Controllers/OrderController.php`

**الوظيفة:** إدارة الطلبات في النظام

**الوظائف:**
- `index()` - عرض قائمة الطلبات مع الإحصائيات
- `create()` - عرض نموذج إنشاء طلب جديد
- `store(Request $request)` - حفظ طلب جديد في قاعدة البيانات
- `show(string $id)` - عرض تفاصيل طلب معين
- `updateStatus(Request $request, string $id)` - تحديث حالة الطلب
- `accept(string $id)` - قبول طلب وإنشاء فاتورة له
- `destroy(string $id)` - حذف طلب
- `createTestOrder()` - إنشاء طلب تجريبي للاختبار
- `deleteTestOrders()` - حذف جميع الطلبات التجريبية
- `resendToShipping(string $id)` - إعادة إرسال طلب إلى شركة الشحن
- `generateOrderNumber()` - توليد رقم طلب فريد

---

### 2. ShippingController
**المسار:** `app/Http/Controllers/ShippingController.php`

**الوظيفة:** إدارة التكامل مع شركات الشحن

**الوظائف:**
- `handleWebhook(Request $request)` - معالجة webhooks من شركة الشحن
- `createOrder(Request $request)` - إنشاء طلب شحن يدوياً
- `getStatus(string $dspOrderId)` - الحصول على حالة طلب الشحن
- `cancel(string $dspOrderId)` - إلغاء طلب شحن

---

### 3. DashboardController
**المسار:** `app/Http/Controllers/DashboardController.php`

**الوظيفة:** عرض لوحة التحكم الرئيسية

**الوظائف:**
- `index()` - عرض لوحة التحكم مع الإحصائيات والطلبات الحديثة

---

### 4. ZydaSyncController
**المسار:** `app/Http/Controllers/ZydaSyncController.php`

**الوظيفة:** مزامنة طلبات Zyda من خلال سكريبت Python

**الوظائف:**
- `__invoke(Request $request)` - تشغيل سكريبت Python لمزامنة طلبات Zyda

---

### 5. WebhookLogController
**المسار:** `app/Http/Controllers/WebhookLogController.php`

**الوظيفة:** عرض وإدارة سجلات Webhooks

**الوظائف:**
- `index(Request $request)` - عرض سجلات Webhooks من ملف الـ logs
- `api(Request $request)` - API endpoint للحصول على Webhooks كـ JSON
- `parseWebhookBlock($block)` - تحليل كتلة log لاستخراج بيانات Webhook

---

### 6. GenericWebhookController
**المسار:** `app/Http/Controllers/GenericWebhookController.php`

**الوظيفة:** استقبال أي نوع من Webhooks وتسجيلها

**الوظائف:**
- `handle(Request $request)` - استقبال webhook عام وتسجيل جميع البيانات

---

### 7. OnlineCustomerController
**المسار:** `app/Http/Controllers/OnlineCustomerController.php`

**الوظيفة:** إدارة العملاء عبر الإنترنت

**الوظائف:**
- `index(Request $request)` - عرض قائمة العملاء مع إمكانية البحث
- `export(Request $request)` - تصدير بيانات العملاء إلى CSV
- `baseQuery(Request $request)` - بناء استعلام قاعدة البيانات
- `transformCustomer(OnlineCustomer $customer)` - تحويل بيانات العميل للعرض

---

### 8. TestNoonController
**المسار:** `app/Http/Controllers/TestNoonController.php`

**الوظيفة:** اختبار تكامل Noon Payments

**الوظائف:**
- `createPayment()` - إنشاء عملية دفع تجريبية
- `success(Request $request)` - معالجة نجاح الدفع
- `fail()` - معالجة فشل الدفع
- `checkApiStatus()` - التحقق من حالة API نون
- `testConnection()` - اختبار الاتصال مع نون
- `quickTest()` - اختبار سريع
- `testHeaders()` - اختبار الـ headers
- `finalTest()` - اختبار نهائي
- `testNewApiKey()` - اختبار API Key جديد
- `quickNewKeyTest()` - اختبار سريع مع API Key جديد
- `finalEnvTest()` - اختبار متغيرات البيئة
- `testWithConfig()` - اختبار مع config
- `finalDirectTest()` - اختبار نهائي مباشر
- `finalEnvConfigTest()` - اختبار نهائي مع config من .env
- `testAuthHeader()` - اختبار Authorization header
- `generateSupportTicket()` - إنشاء نموذج تذكرة دعم

---

### 9. RestLinkController
**المسار:** `app/Http/Controllers/RestLinkController.php`

**الوظيفة:** إدارة صفحات المطاعم والطلبات عبر الروابط

**الوظائف:**
- `index(Request $request)` - عرض صفحة روابط المطاعم (Linktree style)
- `tantBakiza(Request $request)` - عرض صفحة مطعم Tant Bakiza
- `show($id)` - عرض قائمة مطعم معين
- `customerDetails()` - عرض صفحة تفاصيل العميل
- `payment()` - عرض صفحة الدفع
- `saveOrder(Request $request)` - حفظ طلب LinkOrder
- `initiatePayment(Request $request)` - بدء عملية الدفع عبر Noon
- `recordOnlineCustomer(array $attributes)` - تسجيل عميل عبر الإنترنت

---

### 10. PaymentWebhookController
**المسار:** `app/Http/Controllers/PaymentWebhookController.php`

**الوظيفة:** معالجة Webhooks من Noon Payments

**الوظائف:**
- `handleNoon(Request $request)` - معالجة webhook من نون
- `extractOrderReference(array $payload)` - استخراج رقم الطلب من payload
- `isPaymentSuccessful(array $payload)` - التحقق من نجاح الدفع

---

### 11. LogController
**المسار:** `app/Http/Controllers/LogController.php`

**الوظيفة:** عرض وإدارة ملفات الـ Logs

**الوظائف:**
- `index(Request $request)` - عرض ملفات الـ logs مع إمكانية التصفية
- `clear()` - مسح ملف الـ log
- `download()` - تحميل ملف الـ log
- `formatBytes($size, $precision)` - تنسيق حجم الملف

---

### 12. MenuItemController
**المسار:** `app/Http/Controllers/MenuItemController.php`

**الوظيفة:** إدارة عناصر القائمة (المنتجات)

**الوظائف:**
- `index()` - عرض قائمة جميع المنتجات
- `create()` - عرض نموذج إضافة منتج جديد
- `store(Request $request)` - حفظ منتج جديد
- `show(string $id)` - عرض تفاصيل منتج
- `edit(string $id)` - عرض نموذج تعديل منتج
- `update(Request $request, string $id)` - تحديث منتج
- `destroy(string $id)` - حذف منتج

---

### 13. DeliveryTripController
**المسار:** `app/Http/Controllers/DeliveryTripController.php`

**الوظيفة:** إدارة رحلات التوصيل

**الوظائف:**
- `index()` - عرض قائمة رحلات التوصيل
- `create()` - عرض نموذج إنشاء رحلة توصيل
- `store(Request $request)` - حفظ رحلة توصيل جديدة
- `show(string $id)` - عرض تفاصيل رحلة توصيل
- `edit(string $id)` - عرض نموذج تعديل رحلة توصيل
- `update(Request $request, string $id)` - تحديث رحلة توصيل
- `destroy(string $id)` - حذف رحلة توصيل
- `start(string $id)` - بدء رحلة توصيل
- `complete(string $id)` - إكمال رحلة توصيل
- `updateOrderStatus(Request $request, string $tripId, string $orderId)` - تحديث حالة طلب في رحلة

---

### 14. RestaurantController
**المسار:** `app/Http/Controllers/RestaurantController.php`

**الوظيفة:** إدارة المطاعم

**الوظائف:**
- `index()` - عرض قائمة المطاعم
- `create()` - عرض نموذج إضافة مطعم جديد
- `store(Request $request)` - حفظ مطعم جديد
- `show(string $id)` - عرض تفاصيل مطعم
- `edit(string $id)` - عرض نموذج تعديل مطعم
- `update(Request $request, string $id)` - تحديث مطعم
- `destroy(string $id)` - حذف مطعم

---

### 15. LinkOrderController
**المسار:** `app/Http/Controllers/LinkOrderController.php`

**الوظيفة:** إدارة طلبات الروابط (Link Orders)

**الوظائف:**
- `index()` - عرض قائمة Link Orders
- `show(LinkOrder $linkOrder)` - عرض تفاصيل Link Order
- `updateStatus(Request $request, LinkOrder $linkOrder)` - تحديث حالة Link Order

---

### 16. UserController
**المسار:** `app/Http/Controllers/UserController.php`

**الوظيفة:** إدارة المستخدمين

**الوظائف:**
- `index()` - عرض قائمة المستخدمين مع النقاط
- `create()` - عرض نموذج إضافة مستخدم جديد
- `store(Request $request)` - حفظ مستخدم جديد وتسجيله في نظام النقاط
- `show(string $id)` - عرض تفاصيل مستخدم
- `edit(string $id)` - عرض نموذج تعديل مستخدم
- `update(Request $request, string $id)` - تحديث مستخدم
- `destroy(string $id)` - حذف مستخدم
- `registerUserInExternalSystem(User $user)` - تسجيل مستخدم في نظام خارجي

---

### 17. AdController
**المسار:** `app/Http/Controllers/AdController.php`

**الوظيفة:** إدارة الإعلانات

**الوظائف:**
- `index()` - عرض قائمة الإعلانات
- `create()` - عرض نموذج إضافة إعلان جديد
- `store(Request $request)` - حفظ إعلان جديد
- `show(Ad $ad)` - عرض تفاصيل إعلان
- `edit(Ad $ad)` - عرض نموذج تعديل إعلان
- `update(Request $request, Ad $ad)` - تحديث إعلان
- `destroy(Ad $ad)` - حذف إعلان
- `toggleStatus(Ad $ad)` - تفعيل/تعطيل إعلان

---

### 18. LocationController
**المسار:** `app/Http/Controllers/LocationController.php`

**الوظيفة:** إدارة مواقع المستخدمين (API)

**الوظائف:**
- `index()` - الحصول على قائمة مواقع المستخدم
- `store(Request $request)` - إضافة موقع جديد
- `show(Location $location)` - عرض تفاصيل موقع
- `update(Request $request, Location $location)` - تحديث موقع
- `destroy(Location $location)` - حذف موقع
- `setDefault(Location $location)` - تعيين موقع كافتراضي
- `getDefault()` - الحصول على الموقع الافتراضي

---

### 19. InvoiceController
**المسار:** `app/Http/Controllers/InvoiceController.php`

**الوظيفة:** إدارة الفواتير

**الوظائف:**
- `index()` - عرض قائمة الفواتير
- `show(string $id)` - عرض تفاصيل فاتورة

---

### 20. Controller (Base)
**المسار:** `app/Http/Controllers/Controller.php`

**الوظيفة:** الكلاس الأساسي لجميع الكونترولرز

---

## 🌐 API Controllers

### 1. Api\RestaurantController
**المسار:** `app/Http/Controllers/Api/RestaurantController.php`

**الوظيفة:** API endpoints للمطاعم

---

### 2. Api\AuthController
**المسار:** `app/Http/Controllers/Api/AuthController.php`

**الوظيفة:** API endpoints للمصادقة

---

### 3. Api\MobileAppController
**المسار:** `app/Http/Controllers/Api/MobileAppController.php`

**الوظيفة:** API endpoints للتطبيق المحمول

---

### 4. Api\MenuItemController
**المسار:** `app/Http/Controllers/Api/MenuItemController.php`

**الوظيفة:** API endpoints لعناصر القائمة

---

### 5. Api\AdController
**المسار:** `app/Http/Controllers/Api/AdController.php`

**الوظيفة:** API endpoints للإعلانات

---

### 6. Api\OrderController
**المسار:** `app/Http/Controllers/Api/OrderController.php`

**الوظيفة:** API endpoints للطلبات

---

### 7. Api\OrderItemController
**المسار:** `app/Http/Controllers/Api/OrderItemController.php`

**الوظيفة:** API endpoints لعناصر الطلب

---

### 8. Api\SimpleOrderController
**المسار:** `app/Http/Controllers/Api/SimpleOrderController.php`

**الوظيفة:** API endpoints لطلبات بسيطة

---

### 9. Api\ZydaOrderController
**المسار:** `app/Http/Controllers/Api/ZydaOrderController.php`

**الوظيفة:** API endpoints لطلبات Zyda

---

### 10. Api\LocationController
**المسار:** `app/Http/Controllers/Api/LocationController.php`

**الوظيفة:** API endpoints للمواقع

---

### 11. Api\UserController
**المسار:** `app/Http/Controllers/Api/UserController.php`

**الوظيفة:** API endpoints للمستخدمين

---

### 12. Api\AdminController
**المسار:** `app/Http/Controllers/Api/AdminController.php`

**الوظيفة:** API endpoints للمسؤولين

---

### 13. Api\InvoiceController
**المسار:** `app/Http/Controllers/Api/InvoiceController.php`

**الوظيفة:** API endpoints للفواتير

---

## ⚙️ الخدمات (Services)

### 1. ShippingService
**المسار:** `app/Services/ShippingService.php`

**الوظيفة:** خدمة التكامل مع شركات الشحن

**الوظائف:**
- `createOrder($order)` - إنشاء طلب شحن وإرساله لشركة الشحن
- `getOrderStatus($shippingOrderId)` - الحصول على حالة طلب الشحن
- `handleWebhook(Request $request)` - معالجة webhooks من شركة الشحن
- `cancelOrder(string $shippingOrderId)` - إلغاء طلب شحن
- `mapPaymentType($paymentMethod)` - تحويل نوع الدفع
- `buildUrl(string $endpointTemplate, array $params)` - بناء URL
- `flattenArray(array $array, string $prefix)` - تسطيح مصفوفة

---

### 2. PointsService
**المسار:** `app/Services/PointsService.php`

**الوظيفة:** خدمة إدارة نظام النقاط

**الوظائف:**
- `createCustomer($userData)` - إنشاء عميل جديد في نظام النقاط
- `getCustomerPoints($customerId)` - الحصول على رصيد نقاط عميل
- `findCustomerByEmail($email)` - البحث عن عميل بالبريد الإلكتروني

---

### 3. OrderSyncService
**المسار:** `app/Services/OrderSyncService.php`

**الوظيفة:** خدمة مزامنة طلبات Zyda

**الوظائف:**
- `saveScrapedOrder(array $orderData)` - حفظ طلب Zyda في قاعدة البيانات
- `fetchLocationFromWebhook(string $phone)` - جلب موقع من webhook
- `searchLocationByPhone(string $phone, $webhookData)` - البحث عن موقع برقم الهاتف
- `findLocationInWebhookItem(string $normalizedPhone, $item)` - البحث عن موقع في عنصر webhook
- `normalizePhone(string $phone)` - توحيد تنسيق رقم الهاتف

---

### 4. ZydaScriptRunner
**المسار:** `app/Services/ZydaScriptRunner.php`

**الوظيفة:** تشغيل سكريبت Python لمزامنة طلبات Zyda

**الوظائف:**
- `run()` - تشغيل السكريبت وإرجاع النتائج
- `runScript(string $scriptPath)` - تنفيذ سكريبت Python
- `executeProcess(string $binary, string $scriptName, string $workingDir)` - تنفيذ العملية
- `isCommandNotFound(ProcessFailedException $e)` - التحقق من عدم وجود الأمر
- `parseSummary(string $output)` - تحليل ملخص النتائج

---

## 🛣️ الروتس (Routes)

### Web Routes (`routes/web.php`)

#### Public Routes (غير محمية)
- `GET /` - إعادة توجيه إلى صفحة تسجيل الدخول
- `GET /rest-link` - صفحة روابط المطاعم
- `GET /tant-bakiza` - صفحة مطعم Tant Bakiza
- `GET /restaurant/{id}` - صفحة قائمة مطعم
- `GET /checkout/customer-details` - صفحة تفاصيل العميل
- `GET /checkout/payment` - صفحة الدفع
- `POST /checkout/save-order` - حفظ طلب
- `POST /checkout/initiate-payment` - بدء عملية الدفع

#### Protected Routes (محمية بـ auth)
- `GET /dashboard` - لوحة التحكم
- `Resource /users` - إدارة المستخدمين
- `Resource /restaurants` - إدارة المطاعم
- `Resource /menu-items` - إدارة المنتجات
- `Resource /orders` - إدارة الطلبات
  - `PATCH /orders/{order}/accept` - قبول طلب
  - `POST /orders/{order}/update-status` - تحديث حالة طلب
  - `POST /orders/{order}/resend-shipping` - إعادة إرسال للشحن
  - `POST /orders/create-test` - إنشاء طلب تجريبي
  - `DELETE /orders/delete-test` - حذف الطلبات التجريبية
  - `POST /orders/sync-zyda` - مزامنة Zyda
- `GET /online-customers` - قائمة العملاء عبر الإنترنت
- `GET /online-customers/export` - تصدير العملاء
- `Resource /invoices` - إدارة الفواتير
- `Resource /link-orders` - إدارة Link Orders
  - `POST /link-orders/{linkOrder}/update-status` - تحديث حالة
- `Resource /ads` - إدارة الإعلانات
  - `POST /ads/{ad}/toggle-status` - تفعيل/تعطيل إعلان
- `Resource /delivery-trips` - إدارة رحلات التوصيل
  - `PATCH /delivery-trips/{deliveryTrip}/start` - بدء رحلة
  - `PATCH /delivery-trips/{deliveryTrip}/complete` - إكمال رحلة
  - `PATCH /delivery-trips/{deliveryTrip}/orders/{order}/update-status` - تحديث حالة طلب
- `GET /logs` - عرض الـ logs
- `POST /logs/clear` - مسح الـ logs
- `GET /logs/download` - تحميل الـ logs
- `GET /webhooks` - عرض Webhooks

#### Test Routes (لاختبار Noon Payments)
- `GET /pay` - إنشاء عملية دفع تجريبية
- `GET /payment-success` - صفحة نجاح الدفع
- `GET /payment-failed` - صفحة فشل الدفع
- `GET /noon/status` - حالة API نون
- `GET /noon/test` - اختبار الاتصال
- `GET /noon/quick` - اختبار سريع
- `GET /noon/headers` - اختبار الـ headers
- `GET /noon/final` - اختبار نهائي
- `GET /noon/newkey` - اختبار API Key جديد
- `GET /noon/quicknew` - اختبار سريع مع API Key جديد
- `GET /noon/envtest` - اختبار متغيرات البيئة
- `GET /noon/config` - اختبار مع config
- `GET /noon/direct` - اختبار مباشر
- `GET /noon/envconfig` - اختبار config من .env
- `GET /noon/auth` - اختبار Authorization header
- `GET /noon/support` - إنشاء نموذج تذكرة دعم

---

### API Routes (`routes/api.php`)

#### Public API Routes
- `POST /api/webhook` - Webhook من Noon Payments
- `POST /api/webhook/generic` - Webhook عام
- `GET /api/webhooks/logs` - سجلات Webhooks كـ JSON
- `GET /api/restaurants` - قائمة المطاعم
- `GET /api/restaurant/{id}/items` - منتجات مطعم
- `GET /api/locations` - قائمة المواقع
- `POST /api/locations` - إضافة موقع
- `GET /api/locations/{id}` - تفاصيل موقع
- `PUT /api/locations/{id}` - تحديث موقع
- `DELETE /api/locations/{id}` - حذف موقع
- `POST /api/locations/{id}/set-default` - تعيين موقع كافتراضي
- `GET /api/locations-default` - الموقع الافتراضي
- `GET /api/menu-items` - قائمة المنتجات
- `GET /api/menu-items/featured` - المنتجات المميزة
- `GET /api/restaurants/{restaurant}/menu-items` - منتجات مطعم
- `GET /api/menu-items/{menuItem}` - تفاصيل منتج
- `POST /api/auth/register` - تسجيل مستخدم جديد
- `POST /api/auth/login` - تسجيل دخول
- `GET /api/ads` - قائمة الإعلانات
- `GET /api/ads/featured` - الإعلانات المميزة
- `GET /api/ads/type/{type}` - إعلانات حسب النوع
- `GET /api/ads/{ad}` - تفاصيل إعلان
- `POST /api/ads/{ad}/click` - زيادة عدد النقرات
- `POST /api/delete-user/{id}` - حذف مستخدم بالبريد
- `GET /api/points/customer/{pointCustomerId}` - نقاط عميل
- `GET /api/points/{pointCustomerId}` - نقاط عميل (مباشر)
- `POST /api/mobile/payment/checkout-url` - رابط الدفع للتطبيق
- `GET /api/mobile/orders` - طلبات المستخدم
- `POST /api/zyda/orders` - إنشاء طلب Zyda
- `PATCH /api/zyda/orders/{id}/location` - تحديث موقع طلب Zyda
- `DELETE /api/zyda/orders/{id}` - حذف طلب Zyda
- `POST /api/shipping/webhook` - Webhook من شركة الشحن
- `POST /api/create-order` - إنشاء طلب شحن
- `GET /api/shipping/status/{dspOrderId}` - حالة طلب الشحن
- `POST /api/shipping/cancel/{dspOrderId}` - إلغاء طلب شحن
- `GET /api/order/{id}` - تفاصيل طلب (لـ chatbot)

#### Protected API Routes (محمية بـ auth:sanctum)
- `GET /api/user` - بيانات المستخدم الحالي
- `GET /api/auth/points` - نقاط المستخدم
- `GET /api/test-auth` - اختبار المصادقة
- `POST /api/menu-items` - إضافة منتج
- `PUT /api/menu-items/{menuItem}` - تحديث منتج
- `PATCH /api/menu-items/{menuItem}` - تحديث منتج
- `DELETE /api/menu-items/{menuItem}` - حذف منتج
- `POST /api/menu-items/{menuItem}/toggle-availability` - تفعيل/تعطيل منتج
- `GET /api/users` - قائمة المستخدمين
- `GET /api/users/{id}` - تفاصيل مستخدم
- `DELETE /api/users/{id}` - حذف مستخدم
- `Resource /api/orders` - إدارة الطلبات
- `GET /api/users/{userId}/orders` - طلبات مستخدم
- `GET /api/users/{userId}/orders/stats` - إحصائيات طلبات مستخدم
- `GET /api/orders/{orderId}/items` - عناصر طلب
- `POST /api/order-items` - إضافة عنصر طلب
- `POST /api/order-items/multiple` - إضافة عناصر متعددة
- `GET /api/order-items/{id}` - تفاصيل عنصر طلب
- `PUT /api/order-items/{id}` - تحديث عنصر طلب
- `PATCH /api/order-items/{id}` - تحديث عنصر طلب
- `DELETE /api/order-items/{id}` - حذف عنصر طلب
- `POST /api/simple-orders` - إنشاء طلب بسيط
- `GET /api/simple-orders/{id}` - تفاصيل طلب بسيط

---

### Auth Routes (`routes/auth.php`)

#### Guest Routes
- `GET /login` - صفحة تسجيل الدخول
- `POST /login` - معالجة تسجيل الدخول
- `GET /forgot-password` - صفحة نسيان كلمة المرور
- `POST /forgot-password` - إرسال رابط إعادة تعيين كلمة المرور
- `GET /reset-password/{token}` - صفحة إعادة تعيين كلمة المرور
- `POST /reset-password` - معالجة إعادة تعيين كلمة المرور

#### Authenticated Routes
- `GET /verify-email` - صفحة التحقق من البريد
- `GET /verify-email/{id}/{hash}` - التحقق من البريد
- `POST /email/verification-notification` - إرسال إشعار التحقق
- `GET /confirm-password` - تأكيد كلمة المرور
- `POST /confirm-password` - معالجة تأكيد كلمة المرور
- `POST /logout` - تسجيل الخروج

---

### Settings Routes (`routes/settings.php`)

#### Protected Routes
- `GET /settings` - إعادة توجيه إلى /settings/profile
- `GET /settings/profile` - صفحة تعديل الملف الشخصي
- `PATCH /settings/profile` - تحديث الملف الشخصي
- `DELETE /settings/profile` - حذف الحساب
- `GET /settings/password` - صفحة تغيير كلمة المرور
- `PUT /settings/password` - تحديث كلمة المرور
- `GET /settings/appearance` - صفحة المظهر

---

### Console Routes (`routes/console.php`)

- `php artisan inspire` - عرض اقتباس ملهم

---

## 🔑 الوظائف الرئيسية

### 1. إدارة الطلبات
- إنشاء طلبات جديدة (يدوياً أو عبر الروابط)
- تحديث حالة الطلبات
- قبول الطلبات وإنشاء فواتير
- إرسال الطلبات لشركات الشحن
- تتبع حالة الشحن

### 2. إدارة المطاعم والمنتجات
- إدارة المطاعم (إضافة، تعديل، حذف)
- إدارة المنتجات (إضافة، تعديل، حذف، تفعيل/تعطيل)
- عرض القوائم للعملاء

### 3. نظام الدفع
- التكامل مع Noon Payments
- معالجة Webhooks من Noon
- تتبع حالة الدفع

### 4. نظام الشحن
- التكامل مع شركات الشحن
- إنشاء طلبات الشحن
- تتبع حالة الشحن
- معالجة Webhooks من شركات الشحن

### 5. نظام النقاط
- تسجيل العملاء في نظام النقاط
- جلب رصيد النقاط
- تتبع مستويات العملاء

### 6. مزامنة Zyda
- تشغيل سكريبت Python لمزامنة الطلبات
- حفظ الطلبات في قاعدة البيانات
- ربط الطلبات بالمواقع من Webhooks

### 7. إدارة العملاء
- تسجيل العملاء عبر الإنترنت
- تصدير بيانات العملاء
- تتبع حالة العملاء

### 8. إدارة الإعلانات
- إضافة وتعديل الإعلانات
- تتبع النقرات والمشاهدات
- تفعيل/تعطيل الإعلانات

### 9. إدارة رحلات التوصيل
- إنشاء رحلات توصيل
- ربط الطلبات بالرحلات
- تتبع حالة الرحلات

### 10. نظام السجلات
- عرض سجلات النظام
- تصفية السجلات
- تحميل السجلات
- عرض Webhooks المستلمة

---

## 📝 ملاحظات مهمة

1. **الطلبات:** النظام يستخدم `order_number` (مثل ORD-20251104-D80175) وليس `id` الداخلي عند التواصل مع شركات الشحن
2. **shop_id:** يتم الحصول على shop_id من الطلب أولاً، ثم من المطعم كبديل، وأخيراً القيمة الافتراضية 11183
3. **الإحداثيات:** النظام يستخدم `customer_latitude` و `customer_longitude` من الطلب لإرسالها لشركات الشحن
4. **الدفع:** عند نجاح الدفع، يتم تحديث حالة الطلب تلقائياً وإرساله لشركة الشحن
5. **Zyda:** يتم مزامنة طلبات Zyda عبر سكريبت Python يتم تشغيله يدوياً أو تلقائياً

---

## 🔧 التكاملات الخارجية

1. **Noon Payments** - نظام الدفع الإلكتروني
2. **شركات الشحن** - عبر ShippingService
3. **نظام النقاط** - عبر PointsService
4. **Zyda** - مزامنة الطلبات عبر Python script

---

تم إنشاء هذا التوثيق في: {{ date('Y-m-d H:i:s') }}

