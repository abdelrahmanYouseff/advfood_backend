<?php

// ملف تشخيص مشكلة التخزين والصور
// ضع هذا الملف في المجلد الجذر للمشروع على السيرفر

echo "<h1>تشخيص مشكلة التخزين والصور</h1>";

// 1. التحقق من إعدادات Laravel
echo "<h2>1. إعدادات Laravel</h2>";
echo "<strong>APP_URL:</strong> " . env('APP_URL') . "<br>";
echo "<strong>FILESYSTEM_DISK:</strong> " . env('FILESYSTEM_DISK', 'local') . "<br>";
echo "<strong>APP_ENV:</strong> " . env('APP_ENV') . "<br>";

// 2. التحقق من المسارات
echo "<h2>2. المسارات</h2>";
echo "<strong>storage_path():</strong> " . storage_path() . "<br>";
echo "<strong>public_path():</strong> " . public_path() . "<br>";
echo "<strong>storage_path('app/public'):</strong> " . storage_path('app/public') . "<br>";
echo "<strong>public_path('storage'):</strong> " . public_path('storage') . "<br>";

// 3. التحقق من وجود المجلدات
echo "<h2>3. وجود المجلدات</h2>";
$storagePublic = storage_path('app/public');
$publicStorage = public_path('storage');
$logosDir = $storagePublic . '/restaurants/logos';

echo "<strong>storage/app/public:</strong> " . (is_dir($storagePublic) ? "✅ موجود" : "❌ غير موجود") . "<br>";
echo "<strong>public/storage:</strong> " . (is_dir($publicStorage) ? "✅ موجود" : "❌ غير موجود") . "<br>";
echo "<strong>restaurants/logos:</strong> " . (is_dir($logosDir) ? "✅ موجود" : "❌ غير موجود") . "<br>";

// 4. التحقق من الرابط الرمزي
echo "<h2>4. الرابط الرمزي</h2>";
if (is_link($publicStorage)) {
    echo "<strong>public/storage:</strong> ✅ رابط رمزي موجود<br>";
    echo "<strong>الهدف:</strong> " . readlink($publicStorage) . "<br>";
} else {
    echo "<strong>public/storage:</strong> ❌ ليس رابط رمزي<br>";
}

// 5. التحقق من الصلاحيات
echo "<h2>5. الصلاحيات</h2>";
if (is_dir($storagePublic)) {
    echo "<strong>storage/app/public:</strong> " . substr(sprintf('%o', fileperms($storagePublic)), -4) . "<br>";
}
if (is_dir($publicStorage)) {
    echo "<strong>public/storage:</strong> " . substr(sprintf('%o', fileperms($publicStorage)), -4) . "<br>";
}

// 6. اختبار الكتابة
echo "<h2>6. اختبار الكتابة</h2>";
$testFile = $storagePublic . '/test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo "✅ يمكن الكتابة في storage/app/public<br>";
    unlink($testFile);
} else {
    echo "❌ لا يمكن الكتابة في storage/app/public<br>";
}

// 7. اختبار الوصول عبر الويب
echo "<h2>7. اختبار الوصول عبر الويب</h2>";
$testWebFile = $storagePublic . '/web_test.txt';
file_put_contents($testWebFile, 'web test');
$webUrl = env('APP_URL') . '/storage/web_test.txt';
echo "<strong>URL الاختبار:</strong> <a href='$webUrl' target='_blank'>$webUrl</a><br>";

// 8. قائمة الملفات الموجودة
echo "<h2>8. الملفات الموجودة في storage/app/public</h2>";
if (is_dir($storagePublic)) {
    $files = scandir($storagePublic);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
}

// 9. قائمة الملفات في restaurants/logos
echo "<h2>9. الملفات في restaurants/logos</h2>";
if (is_dir($logosDir)) {
    $logoFiles = scandir($logosDir);
    foreach ($logoFiles as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
            $logoUrl = env('APP_URL') . '/storage/restaurants/logos/' . $file;
            echo "&nbsp;&nbsp;&nbsp;&nbsp;URL: <a href='$logoUrl' target='_blank'>$logoUrl</a><br>";
        }
    }
} else {
    echo "❌ مجلد restaurants/logos غير موجود<br>";
}

// 10. إعدادات PHP
echo "<h2>10. إعدادات PHP</h2>";
echo "<strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "<br>";
echo "<strong>post_max_size:</strong> " . ini_get('post_max_size') . "<br>";
echo "<strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "<br>";

// 11. أوامر الحل المقترحة
echo "<h2>11. أوامر الحل المقترحة</h2>";
echo "<pre>";
echo "# 1. إنشاء المجلدات\n";
echo "mkdir -p storage/app/public/restaurants/logos\n";
echo "mkdir -p storage/app/public/menu-items\n\n";

echo "# 2. تعيين الصلاحيات\n";
echo "chmod -R 755 storage/app/public\n";
echo "chmod -R 755 public/storage\n\n";

echo "# 3. تعيين المالك\n";
echo "chown -R forge:forge storage/app/public\n";
echo "chown -R forge:forge public/storage\n\n";

echo "# 4. إعادة إنشاء الرابط الرمزي\n";
echo "rm -f public/storage\n";
echo "php artisan storage:link\n\n";

echo "# 5. مسح الكاش\n";
echo "php artisan cache:clear\n";
echo "php artisan config:clear\n";
echo "php artisan view:clear\n";
echo "php artisan route:clear\n\n";

echo "# 6. إعادة تحميل التكوين\n";
echo "php artisan config:cache\n";
echo "</pre>";

echo "<h2>12. اختبار رفع ملف</h2>";
echo "<form method='post' enctype='multipart/form-data'>";
echo "<input type='file' name='test_file' accept='image/*'>";
echo "<input type='submit' value='رفع ملف اختبار'>";
echo "</form>";

if ($_POST && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $uploadPath = $storagePublic . '/test_upload_' . time() . '_' . $file['name'];
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo "✅ تم رفع الملف بنجاح: " . basename($uploadPath) . "<br>";
            $webUrl = env('APP_URL') . '/storage/' . basename($uploadPath);
            echo "URL: <a href='$webUrl' target='_blank'>$webUrl</a><br>";
        } else {
            echo "❌ فشل في رفع الملف<br>";
        }
    } else {
        echo "❌ خطأ في رفع الملف: " . $file['error'] . "<br>";
    }
}
?>
