# 🔧 إصلاح مشكلة الـ Logging على السيرفر

## ❌ المشكلة الحالية

في ملف `.env` على السيرفر:
```env
LOG_CHANNEL=errorlog
```

هذا يعني أن الـ logs **لا تُكتب في `laravel.log`** بل في **PHP error log**!

---

## ✅ الحل

### 1. تعديل ملف `.env` على السيرفر:

```bash
# SSH إلى السيرفر
ssh forge@lively-mountain

# تعديل ملف .env
cd ~/advfoodapp.clarastars.com
nano .env
```

### 2. غيّر هذه السطور:

**من:**
```env
LOG_CHANNEL=errorlog
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

**إلى:**
```env
LOG_CHANNEL=single
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

**أو:**
```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

### 3. بعد التعديل، نفّذ:

```bash
php artisan config:clear
php artisan config:cache
php artisan cache:clear
```

### 4. تحقق من الصلاحيات:

```bash
chmod -R 775 storage/logs
chown -R forge:forge storage/logs

# إذا لم يكن forge هو المالك:
# sudo chown -R forge:forge storage/logs
```

### 5. اختبار الـ Logging:

```bash
php artisan tinker
>>> \Illuminate\Support\Facades\Log::info('Test log message');
>>> exit

# تحقق من الملف
tail -5 storage/logs/laravel.log
```

---

## 📋 ملخص التغييرات المطلوبة في .env

```env
# ❌ قبل (خطأ)
LOG_CHANNEL=errorlog

# ✅ بعد (صحيح)
LOG_CHANNEL=single
```

---

## 🔍 ملاحظة إضافية

أيضاً لاحظت أن `NOON_API_URL` لا يزال يشير إلى test:
```env
NOON_API_URL=https://api-test.sa.noonpayments.com
```

إذا كنت تريد استخدام production، غيّره إلى:
```env
NOON_API_URL=https://api.sa.noonpayments.com
```

ثم:
```bash
php artisan config:clear
php artisan config:cache
```

---

## ✅ بعد الإصلاح

بعد تطبيق التغييرات، افتح:
```
https://advfoodapp.clarastars.com/logs
```

ستجد جميع الـ logs تظهر في `laravel.log`! 🎉

