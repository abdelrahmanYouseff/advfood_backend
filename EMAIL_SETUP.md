# إعدادات الإيميل - Email Configuration

## 📧 إعدادات الإيميل المطلوبة

لإرسال الإيميلات عند تسجيل الدخول، يجب إضافة الإعدادات التالية في ملف `.env`:

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=acc@adv-line.sa
MAIL_PASSWORD=password123
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=acc@adv-line.sa
MAIL_FROM_NAME="AdvFood System"
```

## 🔧 الإعدادات حسب مزود البريد

### Gmail
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=acc@adv-line.sa
MAIL_PASSWORD=password123
MAIL_ENCRYPTION=tls
```

### Outlook/Hotmail
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=acc@adv-line.sa
MAIL_PASSWORD=password123
MAIL_ENCRYPTION=tls
```

### SMTP مخصص
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=acc@adv-line.sa
MAIL_PASSWORD=password123
MAIL_ENCRYPTION=tls
```

## 📝 ملاحظات مهمة

1. **الإيميل المرسل إليه:** يتم إرسال الإيميل تلقائياً إلى `acc@adv-line.sa` عند كل تسجيل دخول
2. **المحتوى:** يحتوي الإيميل على جميع الفواتير المتاحة في النظام
3. **التنسيق:** الإيميل منسق بشكل جميل ويحتوي على:
   - ملخص الفواتير (الإجمالي، المدفوعة، المعلقة)
   - قائمة تفصيلية بجميع الفواتير
   - معلومات كل فاتورة (الرقم، المطعم، العميل، المبلغ)

## 🧪 اختبار الإعدادات

لاختبار إعدادات الإيميل، يمكنك:

1. تسجيل الدخول إلى النظام
2. التحقق من صندوق الوارد لـ `acc@adv-line.sa`
3. التحقق من ملفات الـ logs في `storage/logs/laravel.log`

## ⚠️ استكشاف الأخطاء

إذا لم يتم إرسال الإيميل:

1. تحقق من إعدادات `.env`
2. تحقق من ملف `storage/logs/laravel.log` للبحث عن أخطاء
3. تأكد من أن كلمة المرور صحيحة
4. تأكد من تفعيل "الوصول للتطبيقات الأقل أماناً" في Gmail (إذا كنت تستخدم Gmail)

