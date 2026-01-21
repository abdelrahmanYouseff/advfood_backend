# تعليمات إعداد السيرفر - نظام Multi-Tenancy للفروع

## الخطوات المطلوبة على السيرفر:

### 1. تحديث الملفات من Git
```bash
cd /path/to/advfood_backend
git pull origin main
```

### 2. تشغيل Migrations
```bash
php artisan migrate
```

هذا سينشئ الجداول التالية:
- `branches` - جدول الفروع
- `branch_restaurant_shop_ids` - جدول ربط الفروع بالمطاعم و shop_id
- إضافة `branch_id` إلى جدول `orders`

### 3. تحديث config/auth.php
تأكد من أن `config/auth.php` يحتوي على:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'branches' => [
        'driver' => 'session',
        'provider' => 'branches',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class),
    ],
    'branches' => [
        'driver' => 'eloquent',
        'model' => App\Models\Branch::class,
    ],
],
```

### 4. مسح Cache الإعدادات
```bash
php artisan config:clear
php artisan config:cache
```

### 5. تشغيل Seeders لإضافة بيانات الفروع
```bash
# تشغيل BranchSeeder لإضافة الفروع
php artisan db:seed --class=BranchSeeder

# تشغيل BranchRestaurantShopIdSeeder لإضافة shop_id لكل فرع
php artisan db:seed --class=BranchRestaurantShopIdSeeder
```

أو تشغيل جميع الـ seeders مرة واحدة:
```bash
php artisan db:seed
```

### 6. التحقق من البيانات
```bash
php artisan tinker
```

ثم في tinker:
```php
// التحقق من الفروع
App\Models\Branch::all();

// التحقق من shop_id mappings
App\Models\BranchRestaurantShopId::with(['branch', 'restaurant'])->get();
```

## بيانات الفروع الافتراضية:

### فرع المروج (Mrouj)
- **Email:** mrouj@advfood.com
- **Password:** password
- **Location:** 24.7560922, 46.6749848
- **Shop IDs:**
  - Gather Us: 210
  - Delawa: (من جدول restaurants)
  - Tant Bakiza: (من جدول restaurants)

### فرع لبن (Laban)
- **Email:** laban@advfood.com
- **Password:** password
- **Location:** 24.62632179260254, 46.531005859375
- **Shop IDs:**
  - Gather Us: 218
  - Delawa: 219
  - Tant Bakiza: 220

## ملاحظات مهمة:

1. **تغيير كلمات المرور:** بعد إضافة الفروع، يجب تغيير كلمات المرور الافتراضية (`password`) إلى كلمات مرور آمنة.

2. **تأكد من وجود المطاعم:** يجب أن تكون المطاعم التالية موجودة في قاعدة البيانات:
   - Gather Us
   - Delawa
   - Tant Bakiza

3. **بعد التحديث:** تأكد من مسح cache المتصفح وإعادة تسجيل الدخول.
