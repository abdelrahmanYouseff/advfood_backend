<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نجح الدفع - AdvFood</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .success-card {
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
        .success-icon {
            animation: scaleUp 0.5s ease-out;
        }
        @keyframes scaleUp {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        .checkmark {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: block;
            stroke-width: 3;
            stroke: #10b981;
            stroke-miterlimit: 10;
            box-shadow: inset 0px 0px 0px #10b981;
            animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
        }
        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke-miterlimit: 10;
            stroke: #10b981;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .checkmark-check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }
        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }
        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 50px #10b981;
            }
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- Success Card -->
            <div class="success-card rounded-2xl p-8 shadow-2xl">
                <!-- Success Icon -->
                <div class="flex justify-center mb-6">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>

                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">تم الدفع بنجاح!</h1>
                    <p class="text-gray-600 text-lg">شكراً لك، تم استلام طلبك</p>
                </div>

                <!-- Order Details -->
                @if(request()->get('order_id'))
                <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600 font-medium">رقم الطلب</span>
                        <span class="text-2xl font-bold text-green-600">#{{ str_pad(request()->get('order_id'), 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="border-t border-green-200 pt-4">
                        <div class="flex items-center text-green-700">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-2"></div>
                            <span class="font-medium">حالة الطلب: قيد التحضير</span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Information Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 text-xl mt-1 ml-3"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-blue-900 mb-1">معلومات مهمة</h3>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• سيتم تحضير طلبك خلال 15-25 دقيقة</li>
                                <li>• سنرسل لك إشعار عند جاهزية الطلب</li>
                                <li>• يمكنك متابعة حالة طلبك عبر واتساب</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <a href="https://wa.me/966501234567?text={{ urlencode('مرحباً، أريد الاستفسار عن طلبي رقم #' . str_pad(request()->get('order_id', '0'), 4, '0', STR_PAD_LEFT)) }}"
                       target="_blank"
                       class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-lg font-semibold text-lg transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp text-xl"></i>
                        تواصل معنا عبر واتساب
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
                        <i class="fas fa-shield-alt mr-1"></i>
                        دفعك آمن ومحمي
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

