# إعداد السكريبت على السيرفر

## 1. إنشاء Virtual Environment

```bash
cd ~/advfoodapp.clarastars.com/python

# إنشاء virtual environment
python3 -m venv venv

# إذا python3 غير موجود، جرب:
python -m venv venv

# تفعيل virtual environment
source venv/bin/activate

# تحديث pip
pip install --upgrade pip
```

## 2. تثبيت المتطلبات

```bash
# تأكد أنك في مجلد python وأن venv مفعل
cd ~/advfoodapp.clarastars.com/python
source venv/bin/activate

# تثبيت المكتبات المطلوبة
pip install selenium requests webdriver-manager

# للتحقق من التثبيت
pip list
```

## 3. تثبيت Chrome و ChromeDriver

```bash
# تثبيت Chrome (إذا لم يكن مثبت)
wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list'
sudo apt-get update
sudo apt-get install -y google-chrome-stable

# التحقق من تثبيت Chrome
google-chrome --version

# ChromeDriver سيتم تثبيته تلقائيًا من السكريبت باستخدام webdriver-manager
```

## 4. إضافة Cron Job

```bash
# فتح crontab
crontab -e

# إضافة السطر التالي لتشغيل السكريبت كل دقيقة:
* * * * * cd /home/forge/advfoodapp.clarastars.com && /usr/bin/php artisan sync:zyda-orders >> /dev/null 2>&1
```

## 5. اختبار السكريبت يدوياً

```bash
cd ~/advfoodapp.clarastars.com

# تشغيل الأمر مباشرة
php artisan sync:zyda-orders

# أو تشغيل السكريبت مباشرة
cd python
source venv/bin/activate
python scrap_zyda.py
```

## 6. فحص السجلات (Logs)

```bash
# فحص Laravel logs
tail -100 ~/advfoodapp.clarastars.com/storage/logs/laravel.log

# فحص logs في الوقت الفعلي
tail -f ~/advfoodapp.clarastars.com/storage/logs/laravel.log | grep -i "zyda\|error\|python"
```

## 7. التأكد من الصلاحيات

```bash
# التأكد من صلاحيات المجلد
chmod -R 755 ~/advfoodapp.clarastars.com/python
chown -R forge:forge ~/advfoodapp.clarastars.com/python
```

## 8. المتغيرات البيئية (اختياري)

```bash
# إضافة متغير بيئي لـ API endpoint في .env
echo "ZYDA_API_ENDPOINT=https://advfoodapp.clarastars.com/api/zyda/orders" >> ~/advfoodapp.clarastars.com/.env
```

## استكشاف الأخطاء

### مشكلة: Python not found
```bash
# التحقق من وجود Python
which python3
which python

# إذا لم يكن موجود، ثبته:
sudo apt-get update
sudo apt-get install -y python3 python3-pip python3-venv
```

### مشكلة: Chrome/ChromeDriver errors
```bash
# تثبيت المتطلبات لـ Chrome headless
sudo apt-get install -y fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 \
  libatspi2.0-0 libcairo2 libcups2 libdbus-1-3 libdrm2 libgbm1 libglib2.0-0 \
  libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libx11-6 libxcb1 libxcomposite1 \
  libxdamage1 libxext6 libxfixes3 libxkbcommon0 libxrandr2 xdg-utils
```

### مشكلة: Selenium errors
```bash
# إعادة تثبيت Selenium و webdriver-manager
source venv/bin/activate
pip uninstall -y selenium webdriver-manager
pip install selenium==4.38.0 webdriver-manager
```

## ملاحظات مهمة

1. **Virtual Environment**: يجب تفعيل venv قبل تشغيل أي أمر pip
2. **Cron Job**: تأكد من المسار الصحيح لـ PHP و artisan
3. **Permissions**: تأكد من صلاحيات المجلد والملفات
4. **Logs**: راقب السجلات باستمرار لاكتشاف الأخطاء مبكراً
