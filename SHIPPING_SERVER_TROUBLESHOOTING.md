# 🔧 حل مشاكل الشحن على السيرفر

## المشكلة
الطلبات لا يتم إرسالها لشركة الشحن على السيرفر (production) بينما تعمل بشكل صحيح على localhost.

## الحلول المحتملة

### 1. ✅ التحقق من ملف `.env` على السيرفر

تأكد من وجود هذه المتغيرات في ملف `.env` على السيرفر:

```env
SHIPPING_API_URL=https://app.leajlak.com/api/v1-partner
SHIPPING_API_KEY=your_api_key_here
```

**خطوات التحقق:**
```bash
# على السيرفر
cd /path/to/your/project
cat .env | grep SHIPPING
```

### 2. ✅ تحديث الـ Config Cache

بعد تحديث ملف `.env`، يجب مسح الكاش:

```bash
# على السيرفر
php artisan config:clear
php artisan config:cache
php artisan cache:clear
```

### 3. ✅ اختبار الاتصال

استخدم الأمر الجديد لاختبار الاتصال:

```bash
php artisan shipping:test-connection
```

هذا الأمر سيعرض:
- ✅ الإعدادات الحالية
- ✅ نتيجة الاتصال
- ✅ أي أخطاء محتملة

### 4. ✅ مشاكل SSL/TLS

إذا كان السيرفر لا يثق في شهادة SSL الخاصة بشركة الشحن، يمكنك تعطيل التحقق:

```env
SHIPPING_API_VERIFY_SSL=false
```

**⚠️ تحذير:** استخدم هذا فقط للاختبار أو إذا كنت متأكداً من أن الاتصال آمن.

### 5. ✅ مشاكل Firewall/Network

تأكد من أن السيرفر يمكنه الوصول إلى API:

```bash
# على السيرفر - اختبار الاتصال
curl -I https://app.leajlak.com/api/v1-partner

# أو اختبار مع API Key
curl -X POST https://app.leajlak.com/api/v1-partner/orders \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### 6. ✅ فحص الـ Logs

تحقق من ملفات الـ logs على السيرفر:

```bash
# على السيرفر
tail -f storage/logs/laravel.log | grep -i shipping
```

ابحث عن:
- `❌ Shipping API credentials missing!` - يعني أن الإعدادات مفقودة
- `🔴 Connection Exception` - يعني مشكلة في الاتصال
- `🔴 Authentication Error (401)` - يعني API Key خاطئ
- `🔴 Validation Error (422)` - يعني البيانات المرسلة غير صحيحة

### 7. ✅ التحقق من الصلاحيات

تأكد من أن Laravel يمكنه كتابة الـ logs:

```bash
# على السيرفر
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

### 8. ✅ التحقق من PHP Extensions

تأكد من تثبيت cURL و OpenSSL:

```bash
# على السيرفر
php -m | grep curl
php -m | grep openssl
```

### 9. ✅ التحقق من HTTP Client

Laravel يستخدم Guzzle HTTP Client. تأكد من أنه يعمل:

```bash
# على السيرفر - في tinker
php artisan tinker
>>> \Illuminate\Support\Facades\Http::get('https://www.google.com')
```

## خطوات التشخيص السريع

1. **فحص الإعدادات:**
   ```bash
   php artisan shipping:test-connection
   ```

2. **فحص الـ Logs:**
   ```bash
   tail -50 storage/logs/laravel.log | grep -i shipping
   ```

3. **تحديث الكاش:**
   ```bash
   php artisan config:clear && php artisan config:cache
   ```

4. **اختبار الاتصال المباشر:**
   ```bash
   curl -X POST https://app.leajlak.com/api/v1-partner/orders \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     -d '{"id":"TEST123","shop_id":"11183","delivery_details":{"name":"Test","phone":"0500000000","email":"test@test.com","address":"Test"},"order":{"payment_type":0,"total":100}}'
   ```

## ملاحظات مهمة

- ✅ **API URL:** يجب أن يكون `https://app.leajlak.com/api/v1-partner`
- ✅ **API Key:** يجب أن يكون صحيحاً ومطابقاً للسيرفر
- ✅ **shop_id:** يجب أن يكون موجوداً في قاعدة البيانات لكل مطعم:
  - Delawa: `11183`
  - Gather Us: `11185`
  - Tant Bakiza: `11184`
- ✅ **Config Cache:** يجب تحديثه بعد أي تغيير في `.env`

## الدعم

إذا استمرت المشكلة بعد تجربة كل الحلول أعلاه:
1. أرسل output من `php artisan shipping:test-connection`
2. أرسل آخر 50 سطر من `storage/logs/laravel.log`
3. تأكد من أن السيرفر يمكنه الوصول إلى الإنترنت

