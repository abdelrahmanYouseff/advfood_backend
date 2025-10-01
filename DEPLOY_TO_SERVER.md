# 🚀 دليل نشر التحديثات على السيرفر

## المشكلة
التعديلات على اللوكال تعمل بنجاح ✅  
لكن على السيرفر https://advfoodapp.clarastars.com لا تعمل ❌

## السبب
التعديلات موجودة فقط على جهازك المحلي ولم يتم نشرها على السيرفر.

---

## 📦 الملفات المهمة التي تم تعديلها

### 1. ملفات البرمجة الأساسية
```
app/Services/ShippingService.php           ✅ الحل الرئيسي لمشكلة الشحن
app/Http/Controllers/TestNoonController.php  ✅ إرسال تلقائي للشحن
app/Http/Controllers/RestLinkController.php  ✅ استخدام shop_id من المطعم
app/Models/Restaurant.php                   ✅ دعم shop_id
app/Console/Commands/ResendOrderToShipping.php  ✅ أمر إعادة الإرسال
```

### 2. ملفات قاعدة البيانات
```
database/migrations/2025_10_01_093922_add_shop_id_to_restaurants_table.php
```

### 3. ملفات العرض (Views)
```
resources/views/rest-link.blade.php         ✅ لون اللوجو + العربية
resources/views/restaurant-menu.blade.php   ✅ دعم العربية
resources/views/checkout/customer-details.blade.php  ✅ تصميم جديد + عربية
```

---

## 🔧 خطوات النشر على السيرفر

### الطريقة 1: رفع الملفات عبر FTP/SFTP

1. **ارفع الملفات المعدلة:**
   ```
   app/Services/ShippingService.php
   app/Http/Controllers/TestNoonController.php
   app/Http/Controllers/RestLinkController.php
   app/Models/Restaurant.php
   app/Console/Commands/ResendOrderToShipping.php
   resources/views/*.blade.php
   ```

2. **ارفع Migration الجديد:**
   ```
   database/migrations/2025_10_01_093922_add_shop_id_to_restaurants_table.php
   ```

3. **على السيرفر، نفذ:**
   ```bash
   cd /path/to/advfood_backend
   
   # تشغيل migration
   php artisan migrate
   
   # تحديث shop_id للمطاعم
   php artisan tinker
   ```
   
   ```php
   \App\Models\Restaurant::query()->update(['shop_id' => '821017371']);
   exit
   ```
   
   ```bash
   # مسح الـ cache
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   
   # إعادة إرسال الطلبات المدفوعة
   php artisan order:resend-shipping
   ```

### الطريقة 2: عبر Git (الأفضل)

1. **على اللوكال، قم بعمل commit وpush:**
   ```bash
   git add .
   git commit -m "Fix: Auto-send orders to shipping company + Arabic support"
   git push origin main
   ```

2. **على السيرفر، قم بعمل pull:**
   ```bash
   ssh user@advfoodapp.clarastars.com
   cd /path/to/advfood_backend
   
   git pull origin main
   
   # تشغيل migrations
   php artisan migrate
   
   # تحديث shop_id
   php artisan tinker
   ```
   
   ```php
   \App\Models\Restaurant::query()->update(['shop_id' => '821017371']);
   exit
   ```
   
   ```bash
   # مسح cache
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   
   # إعادة إرسال الطلبات القديمة المدفوعة
   php artisan order:resend-shipping
   ```

---

## ✅ التحقق من نجاح النشر على السيرفر

### 1. تحقق من shop_id
```bash
ssh user@server
cd /path/to/project
php artisan tinker --execute="echo json_encode(\App\Models\Restaurant::select('id','name','shop_id')->get()->toArray(), JSON_PRETTY_PRINT);"
```

**يجب أن ترى:**
```json
[
  {
    "id": 14,
    "name": "Tant Bakiza",
    "shop_id": "821017371"
  }
]
```

### 2. اختبر طلب جديد
1. افتح https://advfoodapp.clarastars.com/rest-link
2. اطلب منتج وادفع
3. بعد الدفع، تحقق من logs:

```bash
tail -f storage/logs/laravel.log
```

**يجب أن ترى:**
```
✅ Order sent to shipping company successfully
dsp_order_id: 2443
shipping_status: New Order
```

### 3. تحقق من الطلبات
```bash
php artisan tinker --execute="echo json_encode(\App\Models\Order::select('id','order_number','dsp_order_id','shop_id')->where('payment_status','paid')->orderBy('id','desc')->limit(5)->get()->toArray(), JSON_PRETTY_PRINT);"
```

**يجب أن يكون `dsp_order_id` موجود لكل طلب مدفوع.**

---

## 🔍 في حالة المشاكل على السيرفر

### تحقق من:

1. **هل تم رفع الملفات؟**
   ```bash
   # على السيرفر
   grep "uniquePhone" app/Services/ShippingService.php
   ```
   إذا لم يظهر شيء = الملف لم يُرفع

2. **هل تم تشغيل migration؟**
   ```bash
   php artisan migrate:status | grep shop_id
   ```

3. **هل shop_id محدث؟**
   ```bash
   php artisan tinker --execute="Restaurant::first()->shop_id;"
   ```

4. **فحص الـ logs:**
   ```bash
   tail -100 storage/logs/laravel.log | grep -i "shipping\|error"
   ```

---

## 📋 ملخص سريع

| الخطوة | الأمر |
|--------|-------|
| 1. رفع الملفات | `git push` أو FTP |
| 2. تحديث السيرفر | `git pull` على السيرفر |
| 3. تشغيل migrations | `php artisan migrate` |
| 4. تحديث shop_id | `Restaurant::query()->update(['shop_id' => '821017371'])` |
| 5. مسح cache | `php artisan config:clear && php artisan cache:clear` |
| 6. إعادة إرسال الطلبات | `php artisan order:resend-shipping` |

---

## 🎯 الملفات الحرجة (يجب رفعها)

```
✅ app/Services/ShippingService.php
   - إضافة email وphone فريد لكل طلب
   - حل مشكلة "already been taken"

✅ app/Http/Controllers/TestNoonController.php
   - إرسال تلقائي للشحن بعد الدفع الناجح
   - Logs محسّنة

✅ app/Models/Restaurant.php
   - دعم shop_id في fillable

✅ database/migrations/*_add_shop_id_to_restaurants_table.php
   - إضافة عمود shop_id للمطاعم
```

---

**بعد رفع التحديثات، النظام سيعمل تلقائياً على السيرفر! 🚀**

