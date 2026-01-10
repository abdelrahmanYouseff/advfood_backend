# دليل تعديل الحد الأقصى لحجم رفع الملفات على السيرفر

## الخطوة 1: الوصول إلى ملف php.ini

### للوصول إلى السيرفر:
```bash
# عبر SSH
ssh username@advfoodapp.clarastars.com

# أو استخدام FTP/SFTP
# Connect to: advfoodapp.clarastars.com
```

### العثور على ملف php.ini:
```bash
# معرفة مسار ملف php.ini
php --ini

# أو
php -i | grep "Loaded Configuration File"
```

**النتيجة المحتملة:**
```
Loaded Configuration File => /etc/php/8.1/fpm/php.ini
# أو
Loaded Configuration File => /etc/php/8.1/apache2/php.ini
# أو
Loaded Configuration File => /etc/php/8.1/cli/php.ini
```

**ملاحظة مهمة:** إذا كنت تستخدم PHP-FPM مع Nginx أو Apache، فأنت تحتاج إلى تعديل ملف `php.ini` الخاص بـ FPM:
- `/etc/php/8.1/fpm/php.ini` (لـ PHP-FPM)
- `/etc/php/8.1/apache2/php.ini` (لـ Apache mod_php)

## الخطوة 2: تعديل القيم في php.ini

افتح ملف php.ini باستخدام nano أو vi:

```bash
sudo nano /etc/php/8.1/fpm/php.ini
# أو
sudo vi /etc/php/8.1/fpm/php.ini
```

ابحث عن الأسطر التالية وعدلها:

```ini
; Maximum allowed size for uploaded files
upload_max_filesize = 10M

; Maximum size of POST data that PHP will accept
post_max_size = 12M

; Maximum execution time of each script, in seconds
max_execution_time = 300

; Maximum time in seconds a script is allowed to parse input data
max_input_time = 300

; Maximum number of files that can be uploaded via a single request
max_file_uploads = 20
```

**ملاحظة:** تأكد من:
- إزالة علامة `;` في بداية السطر إذا كانت موجودة (لإلغاء التعليق)
- استخدام `M` للحجم بالميجابايت أو `K` للكيلوبايت

## الخطوة 3: إعادة تشغيل PHP-FPM أو Apache

### إذا كنت تستخدم PHP-FPM:
```bash
# Ubuntu/Debian
sudo systemctl restart php8.1-fpm
# أو
sudo service php8.1-fpm restart

# CentOS/RHEL
sudo systemctl restart php-fpm
```

### إذا كنت تستخدم Apache:
```bash
sudo systemctl restart apache2
# أو
sudo service apache2 restart
```

### إذا كنت تستخدم Nginx:
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

## الخطوة 4: التحقق من التغييرات

### طريقة 1: عبر SSH
```bash
php -i | grep -E "upload_max_filesize|post_max_size"
```

### طريقة 2: إنشاء ملف phpinfo.php
أنشئ ملف `phpinfo.php` في المجلد `public`:

```php
<?php
phpinfo();
?>
```

ثم افتح في المتصفح:
```
https://advfoodapp.clarastars.com/phpinfo.php
```

**⚠️ مهم جداً:** احذف هذا الملف بعد التحقق من الإعدادات لأسباب أمنية!

### طريقة 3: عبر Laravel Artisan
```bash
php artisan tinker
# ثم في Tinker:
ini_get('upload_max_filesize');
ini_get('post_max_size');
```

---

## الطريقة 2: تعديل عبر .htaccess (إذا سمح السيرفر)

لقد قمنا بالفعل بتعديل ملف `public/.htaccess`، لكن بعض السيرفرات قد لا تسمح بتعديل إعدادات PHP عبر `.htaccess`.

**إذا لم تعمل طريقة `.htaccess`، استخدم الطريقة 1 (php.ini).**

---

## الطريقة 3: تعديل عبر cPanel (إذا كان متاحاً)

1. سجل دخول إلى **cPanel**
2. ابحث عن **"PHP Selector"** أو **"Select PHP Version"**
3. اختر إصدار PHP
4. اختر **"Options"** أو **"Extensions"**
5. ابحث عن:
   - `upload_max_filesize`
   - `post_max_size`
   - `max_execution_time`
6. غيّر القيم إلى:
   - `upload_max_filesize = 10M`
   - `post_max_size = 12M`
   - `max_execution_time = 300`
7. احفظ التغييرات

---

## ملاحظات مهمة:

1. **القيم المطلوبة:**
   - `upload_max_filesize = 10M` (الحد الأقصى لحجم الملف المُرفوع)
   - `post_max_size = 12M` (يجب أن يكون أكبر من `upload_max_filesize`)

2. **إعادة التشغيل ضرورية:** بعد تعديل `php.ini`، يجب إعادة تشغيل PHP-FPM أو Apache حتى يتم تطبيق التغييرات.

3. **التحقق من Permissions:** تأكد من أن لديك صلاحيات تعديل ملف `php.ini`:
   ```bash
   ls -la /etc/php/8.1/fpm/php.ini
   ```

4. **Backup:** احتفظ بنسخة احتياطية من ملف `php.ini` قبل التعديل:
   ```bash
   sudo cp /etc/php/8.1/fpm/php.ini /etc/php/8.1/fpm/php.ini.backup
   ```

5. **الاختبار:** بعد التعديل، اختبر رفع صورة بحجم أكبر من 2 ميجابايت للتأكد من أن كل شيء يعمل.

---

## حل المشاكل الشائعة:

### المشكلة 1: لا يمكنني تعديل php.ini
**الحل:** اتصل بمقدم خدمة الاستضافة لطلب تعديل الإعدادات.

### المشكلة 2: التعديلات لا تعمل بعد إعادة التشغيل
**الحل:**
- تأكد من تعديل ملف `php.ini` الصحيح (FPM وليس CLI)
- تحقق من عدم وجود ملف `.user.ini` في المجلد الرئيسي يفرض قيوداً
- امسح أي cache موجود

### المشكلة 3: post_max_size أقل من upload_max_filesize
**الحل:** تأكد من أن `post_max_size` أكبر من `upload_max_filesize`.

---

## الأوامر السريعة:

```bash
# معرفة مسار php.ini
php --ini

# معرفة القيم الحالية
php -i | grep -E "upload_max_filesize|post_max_size|max_execution_time"

# إعادة تشغيل PHP-FPM
sudo systemctl restart php8.1-fpm

# التحقق من حالة PHP-FPM
sudo systemctl status php8.1-fpm
```

