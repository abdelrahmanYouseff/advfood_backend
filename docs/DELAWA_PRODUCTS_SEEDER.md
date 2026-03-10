# Seeder منتجات ديلاوة (Delawa Products Seeder)

## الوصف

يقوم **DelawaProductsSeeder** بإدخال منتجات مطعم ديلاوة في جدول `menu_items` وربطها بمطعم ديلاوة (معرف المطعم: **821017371**). البيانات مأخوذة من API خارجي سابقاً وتم نسخها داخل الـ Seeder؛ **لا يوجد أي استدعاء لـ API أثناء التشغيل**.

## تشغيل الـ Seeder

### تشغيل مستقل (مُفضّل للإنتاج)

```bash
php artisan db:seed --class=DelawaProductsSeeder
```

### تشغيل مع باقي الـ Seeders

```bash
php artisan db:seed
```

(يتم استدعاء `DelawaProductsSeeder` تلقائياً من `DatabaseSeeder`.)

## بيئة الإنتاج (Production)

1. **لا يعتمد الـ Seeder على أي API خارجي** — كل المنتجات مخزّنة داخل الملف `database/seeders/DelawaProductsSeeder.php`.
2. إذا لم يكن مطعم ديلاوة موجوداً في جدول `restaurants` بالمعرف `821017371`، يتم إنشاؤه تلقائياً باسم "Delawa" وعنوان ورقم افتراضي.
3. لتحديث أو إعادة إدخال المنتجات: عدّل مصفوفة `products()` داخل الـ Seeder ثم شغّل الأمر مرة أخرى. الـ Seeder يستخدم `updateOrCreate` حسب `restaurant_id` + `name`، لذلك إعادة التشغيل تحدّث الأسعار والصور دون تكرار السجلات.

## الملف المعني

- **[database/seeders/DelawaProductsSeeder.php](../database/seeders/DelawaProductsSeeder.php)** — يحتوي على ثابت `DELAWA_RESTAURANT_ID = 821017371` ومصفوفة المنتجات.

## عدد المنتجات

يتم إدخال **33** منتجاً مرتبطاً بمطعم ديلاوة.
