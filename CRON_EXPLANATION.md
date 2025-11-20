# شرح كيف يعمل Cron Job على السيرفر

## 📋 ما تم إضافته:
```bash
* * * * * cd /home/forge/advfoodapp.clarastars.com && php artisan schedule:run >> /dev/null 2>&1
```

## 🔍 شرح كيف يعمل:

### 1. Cron Job يعمل كل دقيقة:
- `* * * * *` = كل دقيقة، كل ساعة، كل يوم، كل شهر، كل يوم أسبوع

### 2. كل دقيقة يحدث التالي:
- النظام ينفذ الأمر: `cd /home/forge/advfoodapp.clarastars.com && php artisan schedule:run`
- Laravel يقرأ `bootstrap/app.php` ويرى أن `sync:zyda-orders` مضبوط ليعمل `everyMinute()`
- Laravel يشغل الأمر: `php artisan sync:zyda-orders`
- السكريبت يسحب البيانات من Zyda ويسجلها في قاعدة البيانات

### 3. الناتج:
- `>> /dev/null 2>&1` يعني أن الناتج يتم تجاهله (لأن Laravel يسجل في ملفات السجل)

## ✅ كيفية التحقق من أن Cron يعمل:

### 1. تحقق من وجود cron job:
```bash
crontab -l
```
يجب أن ترى السطر الذي أضفته.

### 2. تحقق من أن Laravel scheduler يعمل:
```bash
cd /home/forge/advfoodapp.clarastars.com
php artisan schedule:list
```
يجب أن ترى:
```
* * * * *  php artisan sync:zyda-orders  ................ Has Mutex › Next Due: XX seconds from now
```

### 3. جرب الأمر يدوياً:
```bash
cd /home/forge/advfoodapp.clarastars.com
php artisan sync:zyda-orders
```

### 4. راقب السجلات:
```bash
tail -f storage/logs/laravel.log
```

### 5. تحقق من log خاص بـ cron:
```bash
tail -f /var/log/cron
```
أو (حسب التوزيعة):
```bash
grep CRON /var/log/syslog
```

## 🔧 اختبار Cron Job:

### اختبار مباشر:
```bash
# شغّل الأمر مباشرة
cd /home/forge/advfoodapp.clarastars.com && php artisan schedule:run
```

### راقب في نفس الوقت:
```bash
# في terminal آخر
tail -f storage/logs/laravel.log
```

## 📝 ملاحظات مهمة:

### 1. بدون Overlapping:
- `withoutOverlapping()` في `bootstrap/app.php` يمنع تشغيل مهمة جديدة إذا كانت المهمة السابقة لا تزال تعمل
- إذا استغرقت المزامنة دقيقة واحدة، لن تبدأ مهمة جديدة حتى تنتهي

### 2. Mutex (قفل):
- Laravel يستخدم mutex (قفل) لمنع التداخل
- Mutex يتم تخزينه في `storage/framework/schedule-*`

### 3. السجلات:
- جميع السجلات في `storage/logs/laravel.log`
- إذا حدث خطأ، ستجده في السجل

### 4. إذا لم يعمل:
- تحقق من صلاحيات الملفات
- تحقق من أن `php` في PATH
- تحقق من سجلات cron في `/var/log/cron` أو `/var/log/syslog`

## 🐛 استكشاف الأخطاء:

### إذا لم يعمل Cron:

1. تحقق من أن cron service يعمل:
```bash
sudo service cron status
```

2. تحقق من صلاحيات الملفات:
```bash
ls -la /home/forge/advfoodapp.clarastars.com/artisan
```

3. جرب الأمر مباشرة:
```bash
cd /home/forge/advfoodapp.clarastars.com
php artisan schedule:run -v
```

4. تحقق من سجلات cron:
```bash
grep CRON /var/log/syslog | tail -20
```

## ✅ الخلاصة:

1. ✅ Cron job يعمل كل دقيقة
2. ✅ يستدعي `php artisan schedule:run`
3. ✅ Laravel يتحقق من المهام المجدولة
4. ✅ إذا حان الوقت، يشغل `sync:zyda-orders`
5. ✅ السكريبت يسحب البيانات ويسجلها

**الآن السكريبت يعمل تلقائياً كل دقيقة! 🎉**

