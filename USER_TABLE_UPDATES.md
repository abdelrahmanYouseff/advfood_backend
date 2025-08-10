# User Table Updates - AdvFood

## الأعمدة المضافة لجدول المستخدمين

### 📋 الأعمدة الجديدة
تم إضافة الأعمدة التالية إلى جدول `users`:

1. **phone_number** (string, 20 characters, nullable)
   - رقم الهاتف للمستخدم
   - يمكن أن يكون فارغاً

2. **address** (text, nullable)
   - عنوان المستخدم
   - يمكن أن يكون فارغاً

3. **country** (string, 100 characters, nullable)
   - بلد المستخدم
   - يمكن أن يكون فارغاً

### 🗄️ هيكل الجدول المحدث
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NULL,
    address TEXT NULL,
    country VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,           -- موجود مسبقاً
    role ENUM('admin', 'user') DEFAULT 'user',  -- موجود مسبقاً
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 🔧 الملفات المحدثة

#### 1. **Migration**
- `database/migrations/2025_08_10_075446_add_fields_to_users_table.php`
  - إضافة الأعمدة الجديدة
  - دعم rollback لحذف الأعمدة

#### 2. **Model**
- `app/Models/User.php`
  - إضافة الحقول الجديدة إلى `$fillable`
  - إضافة methods مساعدة: `isAdmin()`, `isUser()`

#### 3. **Controller**
- `app/Http/Controllers/UserController.php`
  - تحديث `index()` method ليشمل الحقول الجديدة

#### 4. **Frontend**
- `resources/js/pages/Users.vue`
  - تحديث interface Props
  - إضافة عرض الحقول الجديدة
  - إضافة badges للدور (Admin/User)
  - إضافة أيقونات للهاتف والبلد

### 🎨 التحديثات في الواجهة الأمامية

#### عرض المستخدمين
- ✅ عرض رقم الهاتف (إذا كان موجوداً)
- ✅ عرض البلد (إذا كان موجوداً)
- ✅ badges ملونة للدور (Admin/User)
- ✅ أيقونات مناسبة لكل حقل

#### التصميم
- ✅ badges حمراء للمديرين
- ✅ badges خضراء للمستخدمين العاديين
- ✅ أيقونات SVG للهاتف والبلد
- ✅ عرض مشروط للحقول (فقط إذا كانت موجودة)

### 🚀 كيفية الاستخدام

#### إضافة مستخدم جديد
```php
User::create([
    'name' => 'أحمد محمد',
    'email' => 'ahmed@example.com',
    'password' => Hash::make('password'),
    'phone_number' => '+966501234567',
    'address' => 'شارع الملك فهد، الرياض',
    'country' => 'السعودية',
    'role' => 'user'
]);
```

#### التحقق من الدور
```php
$user = User::find(1);

if ($user->isAdmin()) {
    // المستخدم مدير
}

if ($user->isUser()) {
    // المستخدم عادي
}
```

### 📊 البيانات الحالية
- جميع المستخدمين الموجودين سابقاً سيكون لديهم `NULL` في الحقول الجديدة
- يمكن تحديث البيانات يدوياً من خلال لوحة التحكم
- الحقول اختيارية ولا تؤثر على الوظائف الموجودة

### 🔄 Rollback
إذا أردت التراجع عن التغييرات:
```bash
php artisan migrate:rollback
```

### 📝 ملاحظات مهمة
- الحقول الجديدة اختيارية (nullable)
- لا تؤثر على المستخدمين الموجودين
- تدعم التطبيق المتعدد اللغات
- متوافقة مع نظام الأدوار الموجود 
