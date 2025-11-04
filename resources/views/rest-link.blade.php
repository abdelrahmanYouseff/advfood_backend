<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdvFood - Restaurants</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
        }
        [dir="rtl"] body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        }
        .link-card {
            transition: all 0.4s ease;
            background: #ffffff;
            border: 2px solid #e5e7eb;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .link-card:hover {
            transform: translateY(-10px);
            border-color: #667eea;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
        }
        .link-card:active {
            transform: translateY(-5px) scale(0.98);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .link-card {
                transition: all 0.2s ease;
            }
            .link-card:hover {
                transform: translateY(-3px);
            }
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen py-4 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-6 sm:space-y-8">
            <!-- Header -->
            <div class="text-center relative">
                <!-- Language Switcher -->
                <div class="absolute top-0 right-0 sm:top-0 sm:right-0">
                    <div class="relative">
                        <button onclick="toggleLanguageMenu()" class="flex items-center gap-1 sm:gap-2 bg-white hover:bg-gray-50 px-2 sm:px-3 py-1.5 sm:py-2 rounded-full shadow-md border-2 border-gray-200 hover:border-purple-300 transition-all">
                            <img id="current-flag" src="{{ asset('icons/united-kingdom.png') }}"
                                 alt="English"
                                 class="w-5 h-5 sm:w-6 sm:h-6 rounded-full object-cover">
                            <span id="current-lang" class="text-gray-700 text-xs sm:text-sm font-medium">EN</span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs hidden sm:inline"></i>
                        </button>

                        <!-- Language Dropdown -->
                        <div id="language-menu" class="hidden absolute top-full right-0 mt-2 bg-white rounded-xl shadow-xl border-2 border-gray-200 overflow-hidden min-w-[120px] sm:min-w-[140px] z-50">
                            <button onclick="switchLanguage('en')" class="w-full flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-3 hover:bg-purple-50 transition-all active:bg-purple-100">
                                <img src="{{ asset('icons/united-kingdom.png') }}"
                                     alt="English"
                                     class="w-5 h-5 sm:w-6 sm:h-6 rounded-full object-cover">
                                <span class="text-gray-700 text-xs sm:text-sm font-medium">English</span>
                            </button>
                            <button onclick="switchLanguage('ar')" class="w-full flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-3 hover:bg-purple-50 transition-all border-t border-gray-100 active:bg-purple-100">
                                <img src="{{ asset('icons/ain.png') }}"
                                     alt="العربية"
                                     class="w-5 h-5 sm:w-6 sm:h-6 rounded-full object-cover">
                                <span class="text-gray-700 text-xs sm:text-sm font-medium">العربية</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mx-auto h-24 w-24 sm:h-36 sm:w-36 rounded-full flex items-center justify-center mb-4 sm:mb-6 shadow-lg overflow-hidden p-1.5 sm:p-2" style="background-color: #cf4823;">
                    <img src="{{ asset('images/WhatsApp Image 2025-10-12 at 10.20.57 AM.jpeg') }}"
                         alt="AdvFood Logo"
                         class="w-full h-full object-contain rounded-full">
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-1 sm:mb-2">@AdvFood</h1>
                <p id="subtitle" class="text-gray-600 text-base sm:text-lg px-2">Discover Amazing Restaurants</p>
                <div class="mt-3 sm:mt-4">
                    <span class="inline-block bg-purple-100 text-purple-700 px-3 sm:px-4 py-1 sm:py-1.5 rounded-full text-xs sm:text-sm font-semibold">
                        <span id="restaurant-count">{{ $restaurants->count() }}</span> <span id="restaurants-text">Restaurants Available</span>
                    </span>
                </div>
            </div>

            <!-- Restaurant Links - Grid Layout -->
            <div class="flex justify-center px-2 sm:px-0">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 w-full max-w-md sm:max-w-none">
                @forelse($restaurants as $restaurant)
                    <a href="{{ route('restaurant.menu', $restaurant->id) }}" class="block">
                        <div class="link-card rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center group">
                            <!-- Restaurant Circle Image -->
                            <div class="mb-3 sm:mb-4 flex justify-center">
                                @if($restaurant->logo)
                                    <img src="{{ asset('storage/' . $restaurant->logo) }}"
                                         alt="{{ $restaurant->name }}"
                                         class="w-20 h-20 sm:w-28 sm:h-28 object-cover rounded-full border-4 border-white/30 shadow-xl group-hover:scale-110 transition-transform duration-300"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                @endif
                                <div class="w-20 h-20 sm:w-28 sm:h-28 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full border-4 border-white/30 shadow-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                                     style="{{ $restaurant->logo ? 'display: none;' : '' }}">
                                    <i class="fas fa-store text-2xl sm:text-3xl text-white"></i>
                                    </div>
                                </div>

                            <!-- Restaurant Info -->
                            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-2 sm:mb-3 truncate px-2">{{ $restaurant->name }}</h3>

                            @if($restaurant->delivery_time)
                                <div class="flex items-center justify-center text-gray-600 text-xs sm:text-sm">
                                    <i class="fas fa-clock text-xs mr-1 text-purple-500 time-icon"></i>
                                    <span>{{ $restaurant->delivery_time }} <span class="time-unit">min</span></span>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-store text-6xl text-gray-300 mb-4"></i>
                        <h3 id="no-restaurants-title" class="text-xl font-semibold text-gray-800 mb-2">No Restaurants Available</h3>
                        <p id="no-restaurants-text" class="text-gray-600">Check back later for amazing dining options!</p>
                    </div>
                @endforelse
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center pt-4 sm:pt-8">
                <p class="text-gray-500 text-xs sm:text-sm">
                    <span id="powered-by-text">Powered by</span> <span class="font-semibold" style="color: #cf4823;">AdvFood</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Success Payment Popup -->
    @if($order && request()->get('payment_status') === 'success')
    <div id="successPopup" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto" style="animation: fadeIn 0.3s ease-out;">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden my-4" style="animation: slideUp 0.5s ease-out; max-height: 90vh; overflow-y: auto;">
            <!-- Header with gradient -->
            <div class="bg-gradient-to-r from-green-500 via-emerald-500 to-green-600 p-4 text-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -mr-16 -mt-16"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full -ml-12 -mb-12"></div>
                </div>

                <!-- Success Icon with animation -->
                <div class="relative z-10 mb-2 flex items-center justify-center gap-4">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-xl" style="animation: scaleUp 0.6s ease-out;">
                        <svg class="checkmark" width="40" height="40" viewBox="0 0 52 52">
                            <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="#10b981" stroke-width="3"/>
                            <path class="checkmark-check" fill="none" stroke="#10b981" stroke-width="3" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl font-bold text-white mb-1 relative z-10">تم الدفع بنجاح!</h2>
                        <p class="text-green-100 text-sm relative z-10">شكراً لك، تم استلام طلبك</p>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="p-5">
                <!-- Order Number and Customer Info in one row -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Order Number -->
                    <div class="bg-green-50 border-2 border-green-200 rounded-xl p-3 text-center">
                        <div class="text-gray-600 text-xs mb-1">رقم الطلب</div>
                        <div class="text-xl font-bold text-green-600">{{ $order->order_number }}</div>
        </div>

                    <!-- Restaurant Info -->
                    <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-3">
                        <div class="text-xs text-blue-600 font-medium mb-1">المطعم</div>
                        <div class="text-lg text-blue-900 font-semibold">{{ $order->restaurant->name ?? 'مطعم' }}</div>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="bg-gray-50 rounded-xl p-3 mb-4">
                    <h3 class="font-semibold text-gray-800 mb-2 flex items-center text-sm">
                        <i class="fas fa-user text-blue-500 mr-2"></i>
                        معلومات العميل
                    </h3>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div>
                            <span class="text-gray-500">👤 الاسم:</span>
                            <span class="text-gray-800 font-medium">{{ $order->delivery_name }}</span>
                    </div>
                    <div>
                            <span class="text-gray-500">📱 الهاتف:</span>
                            <span class="text-gray-800 font-medium" dir="ltr">{{ $order->delivery_phone }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">📍 العنوان:</span>
                            <span class="text-gray-800 font-medium">{{ $order->delivery_address }}</span>
                        </div>
                        @if($order->special_instructions)
                        <div class="col-span-2">
                            <span class="text-gray-500">📝 ملاحظات:</span>
                            <span class="text-gray-800 font-medium">{{ $order->special_instructions }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Order Items -->
                    <div class="bg-gray-50 rounded-xl p-3">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center text-sm">
                            <i class="fas fa-shopping-bag text-green-500 mr-2"></i>
                            تفاصيل الطلب
                        </h3>
                        <div class="space-y-1.5">
                            @foreach($order->orderItems as $item)
                            <div class="flex justify-between items-center text-xs">
                                <div class="flex items-center">
                                    <span class="bg-green-100 text-green-600 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold mr-1.5">{{ $item->quantity }}</span>
                                    <span class="text-gray-700">{{ $item->menuItem->name ?? 'منتج' }}</span>
                                </div>
                                <span class="text-gray-800 font-semibold">{{ number_format($item->subtotal, 2) }} رس</span>
            </div>
                            @endforeach

                            <!-- Total -->
                            <div class="border-t-2 border-gray-200 pt-2 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-800 font-bold text-sm">المجموع:</span>
                                    <span class="text-green-600 font-bold text-lg">{{ number_format($order->total, 2) }} رس</span>
                                </div>
                            </div>
                        </div>
            </div>

                    <!-- Status Info -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3">
                        <div class="flex items-start">
                            <i class="fas fa-clock text-yellow-600 text-lg mt-0.5 mr-2"></i>
                            <div class="flex-1">
                                <div class="font-semibold text-yellow-900 mb-1 text-sm">قيد التحضير</div>
                                <p class="text-yellow-700 text-xs leading-relaxed">
                                    سيتم تحضير طلبك خلال 15-25 دقيقة
                                    <br>
                                    سنرسل لك إشعار عند جاهزية الطلب
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <a href="https://wa.me/966507844079?text={{ urlencode('مرحباً، أريد الاستفسار عن طلبي رقم ' . $order->order_number) }}"
                       target="_blank"
                       class="bg-green-500 hover:bg-green-600 text-white py-2.5 rounded-xl font-semibold text-sm transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl">
                        <i class="fab fa-whatsapp text-lg"></i>
                        واتساب
                    </a>

                    <button onclick="closeSuccessPopup()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2.5 rounded-xl font-semibold text-sm transition-all duration-300">
                        <i class="fas fa-times mr-1"></i>
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes scaleUp {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark-check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke-width: 3;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>

    <script>
        function closeSuccessPopup() {
            const popup = document.getElementById('successPopup');
            if (popup) {
                popup.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    popup.remove();
                    // Remove query parameters from URL
                    const url = new URL(window.location.href);
                    url.searchParams.delete('order_id');
                    url.searchParams.delete('payment_status');
                    window.history.replaceState({}, '', url);
                }, 300);
            }
        }
    </script>
    @endif

    <script>
        const translations = {
            en: {
                subtitle: 'Discover Amazing Restaurants',
                restaurantsText: 'Restaurants Available',
                timeUnit: 'min',
                noRestaurantsTitle: 'No Restaurants Available',
                noRestaurantsText: 'Check back later for amazing dining options!',
                poweredBy: 'Powered by'
            },
            ar: {
                subtitle: 'اكتشف أفضل المطاعم',
                restaurantsText: 'مطعم متاح',
                timeUnit: 'دقيقة',
                noRestaurantsTitle: 'لا توجد مطاعم متاحة',
                noRestaurantsText: 'تحقق لاحقاً للحصول على خيارات طعام مذهلة!',
                poweredBy: 'مدعوم بواسطة'
            }
        };

        function toggleLanguageMenu() {
            const menu = document.getElementById('language-menu');
            menu.classList.toggle('hidden');
        }

        function switchLanguage(lang) {
            const currentFlag = document.getElementById('current-flag');
            const currentLang = document.getElementById('current-lang');
            const htmlRoot = document.getElementById('html-root');

            if (lang === 'en') {
                currentFlag.src = "{{ asset('icons/united-kingdom.png') }}";
                currentFlag.alt = "English";
                currentLang.textContent = "EN";
                htmlRoot.setAttribute('lang', 'en');
                htmlRoot.setAttribute('dir', 'ltr');
            } else if (lang === 'ar') {
                currentFlag.src = "{{ asset('icons/ain.png') }}";
                currentFlag.alt = "العربية";
                currentLang.textContent = "AR";
                htmlRoot.setAttribute('lang', 'ar');
                htmlRoot.setAttribute('dir', 'rtl');
            }

            // Update all text elements
            const subtitle = document.getElementById('subtitle');
            const restaurantsText = document.getElementById('restaurants-text');
            const timeUnits = document.querySelectorAll('.time-unit');
            const noRestaurantsTitle = document.getElementById('no-restaurants-title');
            const noRestaurantsText = document.getElementById('no-restaurants-text');
            const poweredByText = document.getElementById('powered-by-text');

            if (subtitle) subtitle.textContent = translations[lang].subtitle;
            if (restaurantsText) restaurantsText.textContent = translations[lang].restaurantsText;
            if (noRestaurantsTitle) noRestaurantsTitle.textContent = translations[lang].noRestaurantsTitle;
            if (noRestaurantsText) noRestaurantsText.textContent = translations[lang].noRestaurantsText;
            if (poweredByText) poweredByText.textContent = translations[lang].poweredBy;

            timeUnits.forEach(unit => {
                unit.textContent = translations[lang].timeUnit;
            });

            // Update time icons position based on direction
            const timeIcons = document.querySelectorAll('.time-icon');
            timeIcons.forEach(icon => {
                if (lang === 'ar') {
                    icon.classList.remove('mr-1');
                    icon.classList.add('ml-1');
                } else {
                    icon.classList.remove('ml-1');
                    icon.classList.add('mr-1');
                }
            });

            // Close menu
            document.getElementById('language-menu').classList.add('hidden');

            // Store preference
            localStorage.setItem('preferred_language', lang);
        }

        // Close language menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('language-menu');
            const button = event.target.closest('button[onclick="toggleLanguageMenu()"]');

            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Load saved language preference on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('preferred_language');
            if (savedLang && savedLang !== 'en') {
                switchLanguage(savedLang);
            }
        });
    </script>

</body>
</html>
