# 📋 دليل عرض الـ Logs على السيرفر

## الملفات المتوفرة

### 1. `view-shipping-logs.sh` - عرض logs الشحن
عرض جميع logs المتعلقة بشركة الشحن

### 2. `view-order-logs.sh` - عرض logs طلب معين
عرض logs لطلب محدد

### 3. `view-all-logs.sh` - عرض جميع الـ logs
عرض جميع الـ logs مع تلوين حسب النوع

---

## الاستخدام

### عرض logs الشحن

```bash
# عرض آخر 50 سطر من logs الشحن
./view-shipping-logs.sh

# متابعة logs الشحن في الوقت الفعلي (مثل tail -f)
./view-shipping-logs.sh -f

# عرض آخر 100 سطر
./view-shipping-logs.sh -l 100

# عرض جميع logs الشحن
./view-shipping-logs.sh -a

# عرض الأخطاء فقط
./view-shipping-logs.sh -e

# عرض الرسائل الناجحة فقط
./view-shipping-logs.sh -s

# تصفية حسب order ID
./view-shipping-logs.sh -o 123

# تصفية حسب التاريخ
./view-shipping-logs.sh -d 2025-11-04

# دمج عدة خيارات
./view-shipping-logs.sh -f -e -o 123
```

### عرض logs طلب معين

```bash
# عرض logs لطلب رقم 123
./view-order-logs.sh 123

# متابعة logs لطلب في الوقت الفعلي
./view-order-logs.sh 123 -f

# عرض قائمة بالطلبات الأخيرة
./view-order-logs.sh
```

### عرض جميع الـ logs

```bash
# عرض آخر 100 سطر
./view-all-logs.sh

# عرض آخر 500 سطر
./view-all-logs.sh 500
```

---

## أمثلة عملية

### 1. متابعة طلبات الشحن الجديدة في الوقت الفعلي
```bash
./view-shipping-logs.sh -f -s
```

### 2. البحث عن أخطاء الشحن
```bash
./view-shipping-logs.sh -e -l 200
```

### 3. تتبع طلب معين
```bash
./view-order-logs.sh 456 -f
```

### 4. عرض logs اليوم
```bash
./view-shipping-logs.sh -d $(date +%Y-%m-%d)
```

### 5. البحث عن logs لطلب معين مع الأخطاء فقط
```bash
./view-shipping-logs.sh -o 789 -e
```

---

## الألوان في الـ Output

- 🔴 **أحمر**: أخطاء (errors, exceptions, failed)
- 🟢 **أخضر**: نجاح (success, successful)
- 🟡 **أصفر**: تحذيرات (warnings)
- 🔵 **أزرق**: معلومات عامة
- 🟣 **بنفسجي**: معلومات الدفع (payment)
- 🔷 **أزرق فاتح**: معلومات الشحن (shipping)

---

## نصائح مفيدة

### 1. البحث في logs يدوياً
```bash
# البحث عن كلمة معينة
grep -i "shop_id" storage/logs/laravel.log | tail -50

# البحث مع الألوان
grep --color=always -i "error" storage/logs/laravel.log | tail -50
```

### 2. عرض logs حسب التاريخ
```bash
# عرض logs اليوم
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log | grep -i shipping

# عرض logs أمس
grep "$(date -d yesterday +%Y-%m-%d)" storage/logs/laravel.log
```

### 3. حفظ logs في ملف
```bash
# حفظ logs الشحن في ملف
./view-shipping-logs.sh -a > shipping_logs_$(date +%Y%m%d).txt

# حفظ logs طلب معين
./view-order-logs.sh 123 > order_123_logs.txt
```

### 4. البحث المتقدم
```bash
# البحث عن logs تحتوي على shop_id و error
grep -i "shop_id" storage/logs/laravel.log | grep -i error | tail -20

# البحث عن logs بين تاريخين
sed -n '/2025-11-04 10:00/,/2025-11-04 12:00/p' storage/logs/laravel.log
```

---

## الرسائل المهمة في الـ Logs

### ✅ رسائل النجاح
- `✅ Order automatically sent to shipping company after payment confirmed`
- `✅ Shipping API Response Received`
- `🎉 Order successfully sent to shipping company and saved!`

### ❌ رسائل الأخطاء
- `❌ Shipping API credentials missing!`
- `🔴 Connection Exception - Cannot reach shipping API`
- `🔴 Authentication Error (401) - Invalid API Token`
- `🔴 Validation Error (422) - Details:`

### ⚠️ تحذيرات
- `⚠️ Using default shop_id`
- `⚠️ Failed to automatically send order to shipping company`

### 🔍 معلومات التشخيص
- `🚀 Starting shipping order creation`
- `📤 Sending order to shipping company`
- `🔍 Got shop_id from restaurant`

---

## حل المشاكل الشائعة

### المشكلة: لا يمكن تنفيذ الملفات
```bash
chmod +x view-*.sh
```

### المشكلة: الملف غير موجود
```bash
# تأكد من أنك في مجلد المشروع
cd /path/to/advfood_backend

# تحقق من وجود ملف الـ logs
ls -lh storage/logs/laravel.log
```

### المشكلة: لا توجد logs
```bash
# تحقق من صلاحيات الملف
ls -l storage/logs/

# إذا كان الملف فارغ، تحقق من إعدادات Laravel
php artisan config:show logging
```

---

## أوامر إضافية مفيدة

### عرض حجم ملف الـ logs
```bash
ls -lh storage/logs/laravel.log
```

### تنظيف ملف الـ logs القديم
```bash
# نسخ احتياطي
cp storage/logs/laravel.log storage/logs/laravel.log.backup

# مسح الملف
> storage/logs/laravel.log
```

### مراقبة استخدام الذاكرة أثناء عرض الـ logs
```bash
watch -n 1 'tail -20 storage/logs/laravel.log'
```

---

## الدعم

إذا واجهت مشاكل:
1. تحقق من أنك في المجلد الصحيح
2. تحقق من صلاحيات الملفات (`chmod +x`)
3. تحقق من وجود ملف `storage/logs/laravel.log`
4. تحقق من أن Laravel يمكنه الكتابة في مجلد `storage/logs/`

