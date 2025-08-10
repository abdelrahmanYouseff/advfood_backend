# Server Migration Fix - AdvFood

## المشكلة
عند محاولة تسجيل مستخدم جديد عبر API، يظهر الخطأ:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'role' in 'field list'
```

## السبب
الـ migrations لم يتم تشغيلها على السيرفر، أو تم تشغيلها بشكل غير صحيح.

## الحل

### 1. تشغيل الـ Migrations على السيرفر
```bash
# SSH إلى السيرفر
ssh forge@lively-mountain

# الانتقال إلى مجلد المشروع
cd ~/advfoodapp.clarastars.com

# تشغيل الـ migrations
php artisan migrate
```

### 2. إذا فشل الـ Migration
إذا كان هناك مشكلة في الـ migrations الموجودة، قم بتشغيل الـ migrations الجديدة:

```bash
# تشغيل الـ migrations الجديدة فقط
php artisan migrate --path=database/migrations/2025_08_10_082429_add_role_column_to_users_table.php
php artisan migrate --path=database/migrations/2025_08_10_082447_add_phone_column_to_users_table.php
```

### 3. التحقق من الأعمدة
```bash
# التحقق من أعمدة جدول users
php artisan tinker --execute="echo 'Users table columns: '; print_r(Schema::getColumnListing('users'));"
```

### 4. إصلاح الـ Migrations الموجودة
إذا كانت الـ migrations القديمة فارغة، قم بتحديثها:

#### ملف: `2025_08_07_124029_add_role_to_users_table.php`
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'role')) {
            $table->enum('role', ['admin', 'user'])->default('user')->after('email');
        }
    });
}
```

#### ملف: `2025_08_07_125606_add_phone_to_users_table.php`
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'phone')) {
            $table->string('phone', 20)->nullable()->after('role');
        }
    });
}
```

### 5. إعادة تشغيل الـ Migrations
```bash
# Rollback آخر 3 migrations
php artisan migrate:rollback --step=3

# إعادة تشغيل الـ migrations
php artisan migrate
```

## الأعمدة المطلوبة
بعد تشغيل الـ migrations بنجاح، يجب أن يحتوي جدول `users` على الأعمدة التالية:

```sql
users table:
- id (موجود)
- name (موجود)
- email (موجود)
- role (enum: 'admin', 'user') ✅ جديد
- phone (string, nullable) ✅ جديد
- phone_number (string, nullable) ✅ جديد
- address (text, nullable) ✅ جديد
- country (string, nullable) ✅ جديد
- email_verified_at (موجود)
- password (موجود)
- remember_token (موجود)
- created_at (موجود)
- updated_at (موجود)
```

## اختبار الإصلاح
بعد تشغيل الـ migrations، اختبر الـ API:

```bash
curl -X POST https://advfoodapp.clarastars.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد",
    "email": "test@example.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "phone_number": "+966501234567",
    "address": "شارع الملك فهد، الرياض",
    "country": "السعودية"
  }'
```

## النتيجة المتوقعة
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "أحمد محمد",
            "email": "test@example.com",
            "phone_number": "+966501234567",
            "address": "شارع الملك فهد، الرياض",
            "country": "السعودية",
            "role": "user",
            "created_at": "2025-08-10T08:30:00.000000Z"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

## ملاحظات مهمة
- تأكد من وجود نسخة احتياطية من قاعدة البيانات قبل تشغيل الـ migrations
- إذا كان هناك بيانات موجودة، قد تحتاج لتحديث المستخدمين الموجودين يدوياً
- تأكد من أن جميع الـ migrations تعمل بشكل صحيح قبل النشر 
