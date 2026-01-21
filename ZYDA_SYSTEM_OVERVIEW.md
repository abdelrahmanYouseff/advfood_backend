# نظام Zyda - نظرة عامة كاملة

## تدفق النظام الكامل

### 1. جلب الأوردرات من Zyda (Python Script)

**الملف**: `python/scrap_zyda.py`

**كيف يعمل:**
1. السكريبت يفتح متصفح Chrome في وضع headless
2. يسجل الدخول إلى Zyda Dashboard باستخدام:
   - Email: `abdelrahman.yousef@hadaf-hq.com`
   - Password: `V@ntom2121992`
3. يجلب كل الأوردرات من صفحة Orders الحالية
4. لكل أوردر، يستخرج:
   - رقم الأوردر (zyda_order_key مثل `#A27P-4QY3`)
   - اسم العميل
   - رقم التليفون
   - العنوان
   - المبلغ الإجمالي
   - الآيتمز (المنتجات)

**التشغيل:**
```bash
# تلقائي كل دقيقة عبر Cron Job:
* * * * * cd /home/forge/advfoodapp.clarastars.com && php artisan sync:zyda-orders

# يدوي:
cd python
source venv/bin/activate
python scrap_zyda.py
```

---

### 2. إرسال الأوردر إلى Laravel API

**الملف**: `python/scrap_zyda.py` (سطر 1093-1191)

**Endpoint**: `POST /api/zyda/orders`

**البيانات المرسلة:**
```json
{
  "name": "اسم العميل",
  "phone": "+966535815072",
  "address": "العنوان",
  "items": [{"name": "منتج", "quantity": 1, "price": 37.0}],
  "total_amount": 121.0,
  "zyda_order_key": "#A27P-4QY3"
}
```

---

### 3. استقبال الأوردر في Laravel

**الملف**: `app/Http/Controllers/Api/ZydaOrderController.php` (سطر 92-190)

**الخطوات:**
1. ✅ **Validation**: التحقق من البيانات المرسلة
2. ✅ **Check Duplicate**: التحقق من عدم تكرار الأوردر باستخدام `zyda_order_key`
3. ✅ **Call OrderSyncService**: استدعاء `saveScrapedOrder()`

---

### 4. دمج اللوكيشن من WhatsApp

**الملف**: `app/Services/OrderSyncService.php` (سطر 42-59)

**كيف يعمل:**
```php
// البحث عن اللوكيشن في جدول whatsapp_msg
$whatsappLocation = DB::table('whatsapp_msg')
    ->whereNotNull('location')
    ->where(function ($query) use ($zydaOrderKey) {
        $query->where('deliver_order', $zydaOrderKey)
              ->orWhere('deliver_order', 'like', $zydaOrderKey . '%');
    })
    ->orderByDesc('id')
    ->limit(1)
    ->value('location');

// استخدام اللوكيشن من WhatsApp أولاً، ثم من Zyda
$finalLocation = $whatsappLocation ?? ($orderData['location'] ?? null);
```

**الفائدة:**
- العميل يرسل اللوكيشن في الواتساب قبل ما يطلب
- السيستم يربط بين الأوردر واللوكيشن باستخدام `zyda_order_key`
- يتم دمج اللوكيشن تلقائياً مع الأوردر

---

### 5. تحديد الفرع الأقرب

**الملف**: `app/Services/BranchService.php`

**كيف يعمل:**
```php
// استخراج الإحداثيات من اللوكيشن
$coordinates = parseLocation($location); // latitude, longitude

// البحث عن الفرع الأقرب باستخدام Haversine Formula
$nearestBranch = BranchService::findNearestBranch(
    $customerLatitude,
    $customerLongitude
);

// حساب المسافة بالكيلومترات
$distance = BranchService::calculateDistance(
    $customerLat, $customerLon,
    $branchLat, $branchLon
);
```

**الفروع المتاحة:**
1. **Mrouj** (المروج):
   - Latitude: 24.7560922
   - Longitude: 46.6749848
   - Shop IDs: Gather Us (210), Delawa, Tant Bakiza

2. **Laban** (اللبن):
   - Latitude: 24.62632179260254
   - Longitude: 46.531005859375
   - Shop IDs: Gather Us (218), Delawa (219), Tant Bakiza (220)

---

### 6. إنشاء Order وإرساله للفرع

**الملف**: `app/Http/Controllers/Api/ZydaOrderController.php` (سطر 469-796)

**الخطوات:**
1. ✅ **تحديد الفرع**: بناءً على المسافة
2. ✅ **اختيار shop_id**: من `branch_restaurant_shop_ids`
3. ✅ **إنشاء Order**: في جدول `orders`
4. ✅ **ربط Order بـ Zyda Order**: تحديث `zyda_orders.order_id`
5. ✅ **إرسال لشركة الشحن**: تلقائياً عبر Order Model boot

**البيانات المحفوظة:**
```php
Order::create([
    'order_number' => 'ZYDA-20260121-ABC123',
    'user_id' => 36,
    'restaurant_id' => 821017372, // Gather Us
    'branch_id' => 1, // Mrouj أو 2 Laban
    'shop_id' => '210', // أو 218 حسب الفرع
    'status' => 'confirmed',
    'source' => 'zyda',
    'customer_latitude' => 24.7560922,
    'customer_longitude' => 46.6749848,
    'location_link' => 'https://www.google.com/maps/...',
    'payment_status' => 'paid',
    'sound' => true, // ✅ تشغيل الصوت
]);
```

---

### 7. الصوت الأنثوي للأوردرات الجديدة

**الملف**: `resources/js/pages/Orders.vue` (سطر 642-884)

**كيف يعمل:**
```javascript
// 1. حساب رقم الأوردر اليومي
const getDailyOrderNumber = (order) => {
    const todayOrders = orders.filter(o => 
        new Date(o.created_at).toDateString() === new Date().toDateString()
    );
    return todayOrders.findIndex(o => o.id === order.id) + 1;
};

// 2. تشغيل الصوت الأنثوي
const playOrderAnnouncement = (order) => {
    const dailyNumber = getDailyOrderNumber(order);
    const message = `Order Number ${dailyNumber}`;
    
    const utterance = new SpeechSynthesisUtterance(message);
    utterance.lang = 'en-US';
    utterance.rate = 0.9;
    utterance.pitch = 1.2;
    utterance.volume = 1.0;
    
    // اختيار صوت أنثوي
    const femaleVoice = voices.find(v =>
        v.name.includes('Samantha') ||
        v.name.includes('Zira') ||
        v.name.includes('female')
    );
    
    if (femaleVoice) {
        utterance.voice = femaleVoice;
    }
    
    speechSynthesis.speak(utterance);
};

// 3. تكرار الصوت كل 5 ثواني حتى قبول الأوردر
const startOrderAnnouncement = (order) => {
    playOrderAnnouncement(order);
    
    const intervalId = setInterval(() => {
        if (isUnacceptedOrder(order)) {
            playOrderAnnouncement(order);
        } else {
            clearInterval(intervalId);
        }
    }, 5000); // كل 5 ثواني
};
```

**متى يشتغل الصوت:**
- ✅ عند وصول أوردر جديد (status: pending, confirmed)
- ✅ يتكرر كل 5 ثواني حتى قبول الأوردر
- ✅ يشتغل لكل الفروع (مش بس الفرع المسجل دخوله)
- ✅ يقول "Order Number 1, 2, 3..." (رقم الأوردر اليومي)

**اختبار الصوت:**
- زر "اختبار الصوت الأنثوي" في صفحة Orders

---

## الملفات المهمة

### Backend (Laravel)

1. **Controllers**:
   - `app/Http/Controllers/Api/ZydaOrderController.php` - API للأوردرات
   - `app/Http/Controllers/OrderController.php` - عرض الأوردرات
   - `app/Http/Controllers/DashboardController.php` - فلترة حسب الفرع

2. **Services**:
   - `app/Services/OrderSyncService.php` - دمج اللوكيشن
   - `app/Services/BranchService.php` - حساب المسافة
   - `app/Services/ZydaScriptRunner.php` - تشغيل Python

3. **Models**:
   - `app/Models/Branch.php` - الفروع
   - `app/Models/BranchRestaurantShopId.php` - shop_id لكل فرع
   - `app/Models/ZydaOrder.php` - أوردرات Zyda
   - `app/Models/Order.php` - الأوردرات النهائية

4. **Database**:
   - `branches` - بيانات الفروع
   - `branch_restaurant_shop_ids` - shop_id لكل فرع ومطعم
   - `zyda_orders` - أوردرات Zyda قبل التحويل
   - `orders` - الأوردرات النهائية
   - `whatsapp_msg` - رسائل الواتساب مع اللوكيشن

### Frontend (Vue.js)

1. **Pages**:
   - `resources/js/pages/Orders.vue` - صفحة الأوردرات مع الصوت
   - `resources/js/pages/Dashboard.vue` - الداشبورد مع فلترة الفرع

### Python

1. **Scripts**:
   - `python/scrap_zyda.py` - سكريبت جلب الأوردرات
   - `python/requirements.txt` - المكتبات المطلوبة
   - `python/SETUP_SERVER.md` - تعليمات التثبيت

---

## الأوامر المهمة

### على السيرفر

```bash
# 1. إعداد Python Environment
cd ~/advfoodapp.clarastars.com/python
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# 2. تشغيل السكريبت يدوياً
php artisan sync:zyda-orders

# 3. فحص Logs
tail -f storage/logs/laravel.log | grep -i "zyda\|order\|branch"

# 4. إضافة Cron Job
crontab -e
# أضف: * * * * * cd /home/forge/advfoodapp.clarastars.com && php artisan sync:zyda-orders
```

### محلياً (Local)

```bash
# 1. تشغيل السكريبت
cd python
python3 scrap_zyda.py

# 2. تشغيل Laravel
php artisan serve

# 3. فحص Logs
tail -f storage/logs/laravel.log
```

---

## التأكد من عمل النظام

### 1. السكريبت Python
```bash
cd python
source venv/bin/activate
python scrap_zyda.py
# يجب أن يظهر: "Summary: created=X updated=X skipped=X failed=0"
```

### 2. دمج اللوكيشن
- تأكد من وجود بيانات في `whatsapp_msg` مع `deliver_order` و `location`
- السيستم سيربط تلقائياً باستخدام `zyda_order_key`

### 3. تحديد الفرع
- عند حفظ اللوكيشن في Dashboard، يظهر الفرع الأقرب والمسافة
- الأوردر يروح تلقائياً للفرع الأقرب

### 4. الصوت الأنثوي
- افتح صفحة Orders
- ادوس زر "اختبار الصوت الأنثوي"
- يجب أن تسمع صوت أنثوي يقول "Order Number 1"

### 5. فلترة الفروع
- سجل دخول بحساب فرع (mrouj@advfood.com أو laban@advfood.com)
- يجب أن تظهر فقط أوردرات هذا الفرع

---

## استكشاف الأخطاء

### السكريبت مش بيجيب الأوردرات
```bash
# 1. تأكد من Python
which python3
python3 --version

# 2. تأكد من المكتبات
source venv/bin/activate
pip list | grep -E "selenium|requests"

# 3. تأكد من Chrome
google-chrome --version
```

### اللوكيشن مش بيتدمج
```sql
-- تحقق من وجود البيانات
SELECT * FROM whatsapp_msg WHERE deliver_order LIKE '%A27P%';
SELECT * FROM zyda_orders WHERE zyda_order_key LIKE '%A27P%';
```

### الصوت مش شغال
1. افتح Developer Console (F12)
2. ابحث عن أخطاء في Console
3. تأكد من أن المتصفح يدعم Web Speech API
4. جرب في Chrome (أفضل دعم)

### الفرع مش بيتحدد
```sql
-- تحقق من بيانات الفروع
SELECT * FROM branches;
SELECT * FROM branch_restaurant_shop_ids;
```

---

## ملاحظات مهمة

1. ✅ **السكريبت يجلب الأوردرات تلقائياً كل دقيقة**
2. ✅ **اللوكيشن يتدمج تلقائياً من WhatsApp**
3. ✅ **الفرع يتحدد تلقائياً بناءً على المسافة**
4. ✅ **الصوت الأنثوي يشتغل تلقائياً ويتكرر**
5. ✅ **كل فرع يرى أوردراته فقط**

---

## الدعم

إذا واجهت أي مشكلة:
1. افحص `storage/logs/laravel.log`
2. نفّذ `php artisan sync:zyda-orders` يدوياً
3. تأكد من إعدادات Python و Virtual Environment
