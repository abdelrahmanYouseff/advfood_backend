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
        .status-track {
            position: relative;
        }
        .status-track::before {
            content: "";
            position: absolute;
            inset: 50% 0 auto 0;
            transform: translateY(-50%);
            height: 2px;
            background: linear-gradient(90deg, rgba(16,185,129,0.15), rgba(16,185,129,0.5));
            z-index: 0;
        }
        @media (max-width: 768px) {
            .status-track::before {
                inset: 0 auto 0 50%;
                transform: translateX(-50%);
                height: 100%;
                width: 2px;
                background: linear-gradient(180deg, rgba(16,185,129,0.15), rgba(16,185,129,0.5));
            }
        }
        .status-step {
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        .status-step .status-badge {
            width: 48px;
            height: 48px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 auto 0.75rem;
            background: rgba(229, 231, 235, 0.7);
            color: rgba(55, 65, 81, 0.6);
            border: 2px solid rgba(209, 213, 219, 0.7);
        }
        .status-step.completed .status-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-color: rgba(16, 185, 129, 0.35);
            box-shadow: 0 12px 20px -10px rgba(16, 185, 129, 0.4);
        }
        .status-step.active .status-badge {
            background: #10b981;
            color: white;
            border-color: #059669;
            transform: scale(1.08);
            box-shadow: 0 18px 25px -12px rgba(16, 185, 129, 0.55);
        }
        .status-step.cancelled .status-badge {
            background: rgba(239, 68, 68, 0.2);
            color: #b91c1c;
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 12px 20px -10px rgba(239, 68, 68, 0.45);
        }
        .status-step.active .status-label,
        .status-step.completed .status-label {
            font-weight: 700;
            color: #1f2937;
        }
        .status-step.upcoming {
            opacity: 0.45;
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
    @php
        $resolvedOrderId = $orderId ?? request()->get('order_id');
    @endphp
    <div class="min-h-screen flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full">
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
                @if($resolvedOrderId)
                <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-gray-600 font-medium">رقم الطلب</span>
                        <span class="text-2xl font-bold text-green-600" id="order-number-display">#{{ str_pad($resolvedOrderId, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="border-t border-green-200 pt-4">
                        <div class="flex items-center text-green-700 gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="font-medium">
                                حالة الطلب:
                                <span id="order-status-display" class="text-green-700">جاري التحديث...</span>
                            </span>
                        </div>
                        <p id="order-updated-at" class="text-xs text-gray-500 mt-1 hidden">آخر تحديث: --</p>
                    </div>
                </div>
                @endif

                <!-- Status Timeline -->
                <div id="status-timeline-wrapper" class="mb-6 hidden">
                    <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-inner">
                        <div class="flex flex-col gap-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600">
                                    <i class="fas fa-route"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">تتبع حالة الطلب</h3>
                                    <p class="text-sm text-gray-500" id="status-helper-text">نقوم بتحديث الحالة تلقائياً كل 10 ثواني.</p>
                                </div>
                            </div>
                        </div>
                        <div class="status-track grid grid-cols-1 md:grid-cols-6 gap-6 md:gap-4 relative py-6 md:py-0 md:px-6 text-center" id="status-track">
                            <!-- Status steps will be populated via JavaScript -->
                        </div>
                    </div>
                </div>

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
                    <a href="https://wa.me/966501234567?text={{ urlencode('مرحباً، أريد الاستفسار عن طلبي رقم #' . str_pad($resolvedOrderId ?? '0', 4, '0', STR_PAD_LEFT)) }}"
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

    <script>
        window.__PAYMENT_ORDER_ID__ = {{ $resolvedOrderId ? json_encode($resolvedOrderId) : 'null' }};
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const orderId = (typeof window.__PAYMENT_ORDER_ID__ !== 'undefined' && window.__PAYMENT_ORDER_ID__ !== null)
                ? window.__PAYMENT_ORDER_ID__.toString()
                : params.get('order_id');
            const hasPreview = params.get('preview');
            const orderStatusDisplay = document.getElementById('order-status-display');
            const orderUpdatedAt = document.getElementById('order-updated-at');
            const statusTimelineWrapper = document.getElementById('status-timeline-wrapper');
            const statusTrackContainer = document.getElementById('status-track');
            const statusHelperText = document.getElementById('status-helper-text');

            const statusSteps = [
                { key: 'pending', label: 'في انتظار التأكيد', description: 'تم استلام طلبك ونقوم الآن بمراجعته.' },
                { key: 'confirmed', label: 'تم التأكيد', description: 'تم تأكيد الطلب وسيبدأ تحضيره قريباً.' },
                { key: 'preparing', label: 'قيد التحضير', description: 'يعمل فريقنا على تجهيز طلبك بعناية.' },
                { key: 'ready', label: 'جاهز للاستلام', description: 'طلبك جاهز، ننتظر شركة التوصيل.' },
                { key: 'delivering', label: 'جاري التوصيل', description: 'مندوب التوصيل في طريقه إليك.' },
                { key: 'delivered', label: 'تم التسليم', description: 'أهلاً وسهلاً! نتمنى لك وجبة شهية.' },
            ];

            const statusTranslations = {
                pending: 'قيد الانتظار',
                confirmed: 'تم التأكيد',
                preparing: 'قيد التحضير',
                ready: 'جاهز للاستلام',
                delivering: 'جاري التوصيل',
                delivered: 'تم التسليم',
                cancelled: 'تم الإلغاء'
            };

            const renderStatusSteps = () => {
                statusTrackContainer.innerHTML = '';
                statusSteps.forEach((step, index) => {
                    const stepElement = document.createElement('div');
                    stepElement.className = 'status-step upcoming text-center md:text-left';
                    stepElement.dataset.statusKey = step.key;
                    stepElement.innerHTML = `
                        <div class="status-badge">${index + 1}</div>
                        <div class="status-label text-base text-gray-600">${step.label}</div>
                    `;
                    statusTrackContainer.appendChild(stepElement);
                });
            };

            const setStepStates = (currentStatus) => {
                const allSteps = Array.from(statusTrackContainer.querySelectorAll('.status-step'));
                const currentIndex = statusSteps.findIndex(step => step.key === currentStatus);

                allSteps.forEach((stepElement, index) => {
                    stepElement.classList.remove('completed', 'active', 'upcoming', 'cancelled');
                    if (currentStatus === 'cancelled') {
                        if (index === 0) {
                            stepElement.classList.add('cancelled', 'active');
                        } else {
                            stepElement.classList.add('upcoming');
                        }
                    } else if (currentIndex === -1) {
                        stepElement.classList.add('upcoming');
                    } else if (index < currentIndex) {
                        stepElement.classList.add('completed');
                    } else if (index === currentIndex) {
                        stepElement.classList.add('active');
                    } else {
                        stepElement.classList.add('upcoming');
                    }
                });
            };

            const formatTimestamp = (isoString) => {
                try {
                    const date = new Date(isoString);
                    if (Number.isNaN(date.getTime())) {
                        return null;
                    }
                    return date.toLocaleString('ar-EG', {
                        hour12: true,
                        hour: 'numeric',
                        minute: '2-digit',
                        day: 'numeric',
                        month: 'long'
                    });
                } catch {
                    return null;
                }
            };

            const updateStatusUI = (orderPayload) => {
                if (!orderPayload) {
                    orderStatusDisplay.textContent = 'تعذر تحميل بيانات الطلب.';
                    orderStatusDisplay.classList.replace('text-green-700', 'text-red-600');
                    statusHelperText.textContent = 'لم نتمكن من الوصول إلى البيانات. تأكد من صحة رقم الطلب أو حاول لاحقاً.';
                    return;
                }

                const orderInfo = orderPayload.order_info ?? {};
                const currentStatus = (orderInfo.status || '').toLowerCase();
                const translatedStatus = statusTranslations[currentStatus] || statusTranslations.pending;
                const updatedAt = orderInfo.updated_at ?? orderInfo.created_at ?? null;

                orderStatusDisplay.textContent = translatedStatus;
                orderStatusDisplay.classList.remove('text-red-600');
                orderStatusDisplay.classList.add('text-green-700');

                if (updatedAt) {
                    const formattedTime = formatTimestamp(updatedAt);
                    if (formattedTime) {
                        orderUpdatedAt.textContent = `آخر تحديث: ${formattedTime}`;
                        orderUpdatedAt.classList.remove('hidden');
                    }
                }

                if (currentStatus === 'cancelled') {
                    statusHelperText.textContent = 'تم إلغاء الطلب. لطلب المزيد من المساعدة يرجى التواصل مع خدمة العملاء.';
                } else {
                    statusHelperText.textContent = 'نقوم بتحديث الحالة تلقائياً كل 10 ثواني.';
                }

                    setStepStates(currentStatus || 'pending');
            };

            const handleFetchSuccess = (responseData) => {
                if (!responseData?.success || !responseData?.data) {
                    throw new Error('Invalid payload structure');
                }
                return responseData.data;
            };

            const fetchOrderStatus = async () => {
                if (!orderId) {
                    return;
                }

                try {
                    const response = await fetch(`/api/orders/${orderId}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    const payload = handleFetchSuccess(data);
                    updateStatusUI(payload);
                } catch (error) {
                    console.error('Failed to fetch order status:', error);
                    orderStatusDisplay.textContent = 'لا يمكن تحديث الحالة حالياً.';
                    orderStatusDisplay.classList.replace('text-green-700', 'text-red-600');
                    statusHelperText.textContent = 'حدث خطأ أثناء تحديث الحالة. سنحاول مرة أخرى تلقائياً.';
                }
            };

            if (orderId) {
                statusTimelineWrapper.classList.remove('hidden');
                renderStatusSteps();
                fetchOrderStatus();
                setInterval(fetchOrderStatus, 10000);
            } else if (!hasPreview) {
                orderStatusDisplay.textContent = 'لم يتم العثور على رقم الطلب.';
                orderStatusDisplay.classList.replace('text-green-700', 'text-red-600');
            } else {
                orderStatusDisplay.textContent = 'وضع المعاينة - أدخل رقم طلب لمشاهدة التتبع.';
                orderStatusDisplay.classList.replace('text-green-700', 'text-gray-500');
            }
        });
    </script>
</body>
</html>

