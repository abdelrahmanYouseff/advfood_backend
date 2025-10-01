# ✅ قائمة نشر التحديثات على السيرفر

## 🎯 الهدف
رفع التعديلات من اللوكال إلى السيرفر https://advfoodapp.clarastars.com

---

## 📝 الخطوات بالترتيب

### المرحلة 1️⃣: على اللوكال (جهازك)

```bash
# 1. تأكد من أن كل التعديلات محفوظة
git status

# 2. إذا كان هناك تعديلات غير محفوظة
git add .
git commit -m "Fix: Auto-send orders to shipping + Arabic language support"

# 3. ارفع التحديثات
git push origin main
```

---

### المرحلة 2️⃣: على السيرفر

#### الطريقة A: عبر SSH

```bash
# 1. اتصل بالسيرفر
ssh user@advfoodapp.clarastars.com

# 2. انتقل لمجلد المشروع
cd /path/to/advfood_backend

# 3. اسحب التحديثات
git pull origin main

# 4. شغّل migrations الجديدة
php artisan migrate

# 5. حدّث shop_id لجميع المطاعم
php artisan tinker
```

**داخل Tinker:**
```php
// تحديث shop_id لجميع المطاعم
\App\Models\Restaurant::query()->update(['shop_id' => '821017371']);

// التحقق
\App\Models\Restaurant::select('id','name','shop_id')->get();

// اخرج
exit
```

```bash
# 6. مسح جميع الـ cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# 7. إعادة بناء الـ cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. إعادة إرسال الطلبات المدفوعة القديمة
php artisan order:resend-shipping

# 9. اختبر أن كل شيء يعمل
php artisan tinker --execute="echo \App\Models\Restaurant::first()->shop_id;"
```

#### الطريقة B: عبر cPanel أو File Manager

إذا لم يكن لديك SSH access:

1. **ارفع الملفات يدوياً عبر FTP:**
   - `app/Services/ShippingService.php`
   - `app/Http/Controllers/TestNoonController.php`
   - `app/Http/Controllers/RestLinkController.php`
   - `app/Models/Restaurant.php`
   - `app/Console/Commands/ResendOrderToShipping.php`
   - `database/migrations/2025_10_01_093922_add_shop_id_to_restaurants_table.php`
   - جميع ملفات `resources/views/` المعدلة

2. **من cPanel Terminal أو SSH:**
   ```bash
   cd public_html/advfood_backend
   php artisan migrate
   php artisan config:clear
   php artisan cache:clear
   ```

3. **حدّث shop_id عبر phpMyAdmin:**
   ```sql
   UPDATE restaurants SET shop_id = '821017371';
   ```

4. **إعادة إرسال الطلبات:**
   ```bash
   php artisan order:resend-shipping
   ```

---

## ✅ التحقق من نجاح النشر

### 1. تحقق من الملفات
```bash
# على السيرفر
grep "uniquePhone" app/Services/ShippingService.php
```
**يجب أن يظهر:** `$uniquePhone = $orderObj->delivery_phone`

### 2. تحقق من shop_id
```bash
php artisan tinker --execute="echo \App\Models\Restaurant::first()->shop_id;"
```
**يجب أن يظهر:** `821017371`

### 3. اختبر طلب جديد
1. افتح: https://advfoodapp.clarastars.com/rest-link
2. اختر مطعم → أضف منتج → ادفع
3. بعد الدفع، تحقق من logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```
   **يجب أن ترى:**
   ```
   ✅ Order sent to shipping company successfully
   dsp_order_id: 2443
   ```

---

## 🐛 حل المشاكل

### المشكلة: "Permission denied" عند git pull

```bash
# تأكد من صلاحيات الملفات
sudo chown -R www-data:www-data /path/to/project
sudo chmod -R 755 /path/to/project
```

### المشكلة: migration لا يعمل

```bash
# تحقق من الاتصال بقاعدة البيانات
php artisan tinker --execute="DB::connection()->getPdo();"

# أو شغّل migration يدوياً
php artisan migrate --force
```

### المشكلة: shop_id لم يتحدث

```bash
# تحديث يدوي
php artisan tinker
```
```php
DB::table('restaurants')->update(['shop_id' => '821017371']);
```

---

## 📊 مقارنة قبل وبعد

### قبل النشر (السيرفر القديم) ❌
```
Order -> Payment Success -> ❌ لا يُرسل للشحن
dsp_order_id: null
```

### بعد النشر (السيرفر الجديد) ✅
```
Order -> Payment Success -> ✅ يُرسل تلقائياً للشحن
dsp_order_id: 2443
shipping_status: New Order
phone: 0535815072#54
email: order54@advfood.local
```

---

## 🎯 الأوامر الكاملة للنسخ واللصق

### على السيرفر (بعد git pull أو رفع الملفات):

```bash
# الخطوة 1: تشغيل migrations
php artisan migrate --force

# الخطوة 2: تحديث shop_id
php artisan tinker --execute="\App\Models\Restaurant::query()->update(['shop_id' => '821017371']); echo 'Shop IDs updated';"

# الخطوة 3: مسح cache
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear

# الخطوة 4: إعادة بناء cache
php artisan config:cache && php artisan route:cache

# الخطوة 5: إعادة إرسال الطلبات المدفوعة
php artisan order:resend-shipping

# الخطوة 6: التحقق
tail -20 storage/logs/laravel.log | grep shipping
```

---

## 📱 الميزات الجديدة بعد النشر

✅ **إرسال تلقائي** لكل طلب مدفوع لشركة الشحن  
✅ **دعم اللغة العربية** في جميع الصفحات  
✅ **تصميم محسّن** لصفحة تفاصيل العميل  
✅ **أوامر إدارية** لإعادة إرسال الطلبات  
✅ **معالجة الأخطاء** (phone/email duplicate) تلقائياً  

---

## 📞 الدعم

إذا واجهت مشاكل في النشر:

1. **تحقق من الـ logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

2. **تحقق من permissions:**
   ```bash
   ls -la app/Services/ShippingService.php
   ```

3. **أعد تشغيل PHP/Nginx:**
   ```bash
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart nginx
   ```

---

**بعد إتمام هذه الخطوات، النظام سيعمل تلقائياً على السيرفر! 🚀**

