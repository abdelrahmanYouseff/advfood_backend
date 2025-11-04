# 📋 عرض الـ Logs على السيرفر

## 🔧 إصلاح مشكلة الـ Logs الفارغة

إذا كان ملف `storage/logs/laravel.log` فارغ، اتبع هذه الخطوات:

### 1. التحقق من إعدادات الـ Logging في .env
```bash
# تحقق من LOG_CHANNEL
grep LOG_CHANNEL .env

# يجب أن يكون:
# LOG_CHANNEL=single
# أو
# LOG_CHANNEL=stack
```

### 2. إصلاح الصلاحيات
```bash
# أعط صلاحيات الكتابة للمجلد
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs

# أو إذا كنت تستخدم forge:
sudo chown -R forge:forge storage/logs
sudo chmod -R 775 storage/logs
```

### 3. تحديث Config Cache
```bash
php artisan config:clear
php artisan config:cache
```

### 4. اختبار الـ Logging
```bash
# اختبار كتابة log
php artisan tinker
>>> \Illuminate\Support\Facades\Log::info('Test log message');
>>> exit

# تحقق من الملف
tail -5 storage/logs/laravel.log
```

### 5. إذا كان LOG_CHANNEL=null أو syslog
```bash
# عدّل .env
nano .env
# غيّر LOG_CHANNEL إلى:
LOG_CHANNEL=single
LOG_LEVEL=debug

# ثم:
php artisan config:clear
php artisan config:cache
```

### 6. التحقق من Daily Logs
إذا كان LOG_CHANNEL=daily، قد يكون الملف باسم تاريخي:
```bash
ls -la storage/logs/
# ابحث عن laravel-2025-11-04.log مثلاً
```

---

## 📋 عرض جميع الـ Logs في ملف laravel.log

### 1. عرض آخر 100 سطر
```bash
tail -n 100 storage/logs/laravel.log
```

### 2. متابعة الـ Logs في الوقت الفعلي (مثل tail -f)
```bash
tail -f storage/logs/laravel.log
```

### 3. عرض جميع الـ Logs
```bash
cat storage/logs/laravel.log
```

### 4. عرض مع البحث عن كلمات معينة
```bash
# البحث عن logs الشحن
grep -i "shipping" storage/logs/laravel.log | tail -100

# البحث عن logs طلب معين
grep -i "order_id.*123" storage/logs/laravel.log

# البحث عن الأخطاء
grep -i "error" storage/logs/laravel.log | tail -100
```

### 5. عرض logs اليوم
```bash
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log
```

### 6. عرض logs مع الألوان
```bash
tail -f storage/logs/laravel.log | grep --color=always -E "error|success|warning|shipping"
```

---

## أمثلة سريعة

### متابعة logs الشحن فقط
```bash
tail -f storage/logs/laravel.log | grep -i "shipping"
```

### عرض آخر 500 سطر من logs الشحن
```bash
grep -i "shipping" storage/logs/laravel.log | tail -500
```

### البحث عن logs طلب معين
```bash
grep -i "order_id.*456" storage/logs/laravel.log
```

---

## ملاحظات

- جميع الـ logs موجودة في: `storage/logs/laravel.log`
- استخدم `tail -f` لمتابعة الـ logs في الوقت الفعلي
- اضغط `Ctrl+C` لإيقاف المتابعة
- تأكد من أن `LOG_CHANNEL=single` في `.env`

