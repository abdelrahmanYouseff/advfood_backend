<?php

// ملف تشخيص مشكلة رفع الصور والصفحة البيضاء
// ضع هذا الملف في المجلد الجذر للمشروع على السيرفر

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>تشخيص مشكلة رفع الصور والصفحة البيضاء</h1>";

// 1. التحقق من إعدادات PHP
echo "<h2>1. إعدادات PHP</h2>";
echo "<strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "<br>";
echo "<strong>post_max_size:</strong> " . ini_get('post_max_size') . "<br>";
echo "<strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "<br>";
echo "<strong>memory_limit:</strong> " . ini_get('memory_limit') . "<br>";
echo "<strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "<br>";

// 2. التحقق من إعدادات Laravel
echo "<h2>2. إعدادات Laravel</h2>";
echo "<strong>APP_DEBUG:</strong> " . (env('APP_DEBUG') ? 'true' : 'false') . "<br>";
echo "<strong>APP_ENV:</strong> " . env('APP_ENV') . "<br>";
echo "<strong>APP_URL:</strong> " . env('APP_URL') . "<br>";

// 3. التحقق من المسارات
echo "<h2>3. المسارات</h2>";
$storagePublic = storage_path('app/public');
$logosDir = $storagePublic . '/restaurants/logos';

echo "<strong>storage/app/public:</strong> " . $storagePublic . " - " . (is_dir($storagePublic) ? "✅ موجود" : "❌ غير موجود") . "<br>";
echo "<strong>restaurants/logos:</strong> " . $logosDir . " - " . (is_dir($logosDir) ? "✅ موجود" : "❌ غير موجود") . "<br>";

// 4. التحقق من الصلاحيات
echo "<h2>4. الصلاحيات</h2>";
if (is_dir($storagePublic)) {
    echo "<strong>storage/app/public:</strong> " . substr(sprintf('%o', fileperms($storagePublic)), -4) . "<br>";
    echo "<strong>قابل للكتابة:</strong> " . (is_writable($storagePublic) ? "✅ نعم" : "❌ لا") . "<br>";
}

if (is_dir($logosDir)) {
    echo "<strong>restaurants/logos:</strong> " . substr(sprintf('%o', fileperms($logosDir)), -4) . "<br>";
    echo "<strong>قابل للكتابة:</strong> " . (is_writable($logosDir) ? "✅ نعم" : "❌ لا") . "<br>";
}

// 5. اختبار رفع ملف بسيط
echo "<h2>5. اختبار رفع ملف بسيط</h2>";
$testFile = $storagePublic . '/test_' . time() . '.txt';
if (file_put_contents($testFile, 'test content')) {
    echo "✅ تم إنشاء ملف اختبار: " . basename($testFile) . "<br>";
    unlink($testFile);
} else {
    echo "❌ فشل في إنشاء ملف اختبار<br>";
}

// 6. اختبار رفع صورة
echo "<h2>6. اختبار رفع صورة</h2>";
echo "<form method='post' enctype='multipart/form-data' style='border: 2px dashed #ccc; padding: 20px; margin: 20px 0;'>";
echo "<h3>اختبار رفع صورة</h3>";
echo "<input type='file' name='test_image' accept='image/*' required><br><br>";
echo "<input type='submit' value='رفع الصورة للاختبار' style='background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "</form>";

if ($_POST && isset($_FILES['test_image'])) {
    echo "<h3>نتيجة الاختبار:</h3>";
    
    $file = $_FILES['test_image'];
    echo "<strong>اسم الملف:</strong> " . $file['name'] . "<br>";
    echo "<strong>نوع الملف:</strong> " . $file['type'] . "<br>";
    echo "<strong>حجم الملف:</strong> " . number_format($file['size'] / 1024, 2) . " KB<br>";
    echo "<strong>كود الخطأ:</strong> " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // التحقق من نوع الملف
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo "❌ نوع الملف غير مسموح به: " . $file['type'] . "<br>";
        } else {
            // محاولة رفع الملف
            $uploadPath = $logosDir . '/test_' . time() . '_' . $file['name'];
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                echo "✅ تم رفع الملف بنجاح: " . basename($uploadPath) . "<br>";
                
                // اختبار الوصول عبر الويب
                $webUrl = env('APP_URL') . '/storage/restaurants/logos/' . basename($uploadPath);
                echo "<strong>URL الصورة:</strong> <a href='$webUrl' target='_blank'>$webUrl</a><br>";
                
                // عرض الصورة
                echo "<img src='$webUrl' style='max-width: 200px; max-height: 200px; border: 1px solid #ccc; margin: 10px 0;'><br>";
                
                // حذف الملف للتنظيف
                unlink($uploadPath);
                echo "🗑️ تم حذف ملف الاختبار للتنظيف<br>";
            } else {
                echo "❌ فشل في رفع الملف<br>";
                echo "<strong>سبب الفشل:</strong> " . error_get_last()['message'] . "<br>";
            }
        }
    } else {
        echo "❌ خطأ في رفع الملف<br>";
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'الملف أكبر من upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'الملف أكبر من MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
            UPLOAD_ERR_EXTENSION => 'تم إيقاف الرفع بواسطة إضافة PHP'
        ];
        echo "<strong>تفاصيل الخطأ:</strong> " . ($errorMessages[$file['error']] ?? 'خطأ غير معروف') . "<br>";
    }
}

// 7. محاكاة عملية Laravel
echo "<h2>7. محاكاة عملية Laravel</h2>";
if ($_POST && isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
    echo "<h3>محاكاة عملية Laravel:</h3>";
    
    try {
        // محاكاة عملية Laravel store
        $file = $_FILES['test_image'];
        $fileName = time() . '_' . $file['name'];
        $uploadPath = $logosDir . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo "✅ محاكاة Laravel ناجحة<br>";
            echo "<strong>المسار المحفوظ:</strong> restaurants/logos/" . $fileName . "<br>";
            
            $webUrl = env('APP_URL') . '/storage/restaurants/logos/' . $fileName;
            echo "<strong>URL النهائي:</strong> <a href='$webUrl' target='_blank'>$webUrl</a><br>";
            
            // حذف الملف للتنظيف
            unlink($uploadPath);
        } else {
            echo "❌ فشل في محاكاة Laravel<br>";
        }
    } catch (Exception $e) {
        echo "❌ خطأ في المحاكاة: " . $e->getMessage() . "<br>";
    }
}

// 8. أوامر الحل المقترحة
echo "<h2>8. أوامر الحل المقترحة</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "# 1. تفعيل وضع التطوير مؤقتاً\n";
echo "sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env\n\n";

echo "# 2. إنشاء المجلدات\n";
echo "mkdir -p storage/app/public/restaurants/logos\n";
echo "mkdir -p storage/app/public/menu-items\n\n";

echo "# 3. تعيين الصلاحيات\n";
echo "chmod -R 755 storage/app/public\n";
echo "chmod -R 755 public/storage\n\n";

echo "# 4. تعيين المالك\n";
echo "chown -R forge:forge storage/app/public\n";
echo "chown -R forge:forge public/storage\n\n";

echo "# 5. إعادة إنشاء الرابط الرمزي\n";
echo "rm -f public/storage\n";
echo "php artisan storage:link\n\n";

echo "# 6. مسح الكاش\n";
echo "php artisan cache:clear\n";
echo "php artisan config:clear\n";
echo "php artisan view:clear\n";
echo "php artisan route:clear\n\n";

echo "# 7. إعادة تحميل التكوين\n";
echo "php artisan config:cache\n\n";

echo "# 8. إعادة تشغيل الخادم\n";
echo "sudo systemctl restart nginx\n";
echo "sudo systemctl restart php8.1-fpm\n";
echo "</pre>";

// 9. معلومات إضافية
echo "<h2>9. معلومات إضافية</h2>";
echo "<strong>إصدار PHP:</strong> " . phpversion() . "<br>";
echo "<strong>إصدار Laravel:</strong> " . app()->version() . "<br>";
echo "<strong>المجلد الحالي:</strong> " . getcwd() . "<br>";
echo "<strong>المستخدم:</strong> " . get_current_user() . "<br>";

?> 