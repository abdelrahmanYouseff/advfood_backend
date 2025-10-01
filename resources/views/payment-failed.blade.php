<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فشل الدفع - AdvFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .error-icon {
            animation: shake 0.5s ease-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Error Card -->
            <div class="error-card rounded-2xl p-8 shadow-2xl">
                <!-- Error Icon -->
                <div class="flex justify-center mb-6">
                    <div class="error-icon w-24 h-24 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times text-5xl text-red-500"></i>
                    </div>
                </div>

                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">فشلت عملية الدفع</h1>
                    <p class="text-gray-600 text-lg">عذراً، لم نتمكن من إتمام عملية الدفع</p>
                </div>

                <!-- Error Details -->
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl mt-1 ml-3"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-red-900 mb-2">أسباب محتملة للفشل:</h3>
                            <ul class="text-sm text-red-800 space-y-1">
                                <li>• رصيد غير كافٍ في البطاقة</li>
                                <li>• بيانات البطاقة غير صحيحة</li>
                                <li>• البطاقة منتهية الصلاحية</li>
                                <li>• تم رفض العملية من البنك</li>
                                <li>• مشكلة في الاتصال بالإنترنت</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Information Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-blue-500 text-xl mt-1 ml-3"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-blue-900 mb-1">ماذا يمكنك فعله؟</h3>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• تحقق من بيانات البطاقة وحاول مرة أخرى</li>
                                <li>• استخدم بطاقة أخرى</li>
                                <li>• تواصل مع البنك للتأكد من عدم وجود مشكلة</li>
                                <li>• تواصل معنا للمساعدة</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <a href="/checkout/customer-details"
                       class="w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg font-semibold text-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fas fa-redo"></i>
                        إعادة المحاولة
                    </a>

                    <a href="https://wa.me/966501234567?text={{ urlencode('مرحباً، واجهت مشكلة في الدفع') }}"
                       target="_blank"
                       class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-lg font-semibold text-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp text-xl"></i>
                        تواصل معنا للمساعدة
                    </a>

                    <a href="/rest-link"
                       class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold text-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fas fa-home"></i>
                        العودة للصفحة الرئيسية
                    </a>
                </div>

                <!-- Footer -->
                <div class="text-center mt-6 pt-6 border-t border-gray-200">
                    <p class="text-gray-500 text-sm">
                        <i class="fas fa-lock mr-1"></i>
                        معلوماتك آمنة ولم يتم خصم أي مبلغ
                    </p>
                </div>
            </div>

            <!-- Powered By -->
            <div class="text-center mt-6">
                <p class="text-white/80 text-sm">
                    Powered by <span class="font-semibold">AdvFood</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

