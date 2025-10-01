<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdvFood - Restaurants</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .link-card {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .link-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .restaurant-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-24 w-24 bg-white rounded-full flex items-center justify-center mb-6 shadow-lg overflow-hidden">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="AdvFood Logo"
                         class="h-16 w-16 object-contain"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="h-16 w-16 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full flex items-center justify-center" style="display: none;">
                        <i class="fas fa-utensils text-2xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">@AdvFood</h1>
                <p class="text-white/80 text-lg">Discover Amazing Restaurants</p>
                <div class="mt-4">
                    <span class="inline-block bg-white/20 text-white px-3 py-1 rounded-full text-sm">
                        {{ $restaurants->count() }} Restaurants Available
                    </span>
                </div>
            </div>

            <!-- Restaurant Links -->
            <div class="space-y-4">
                @forelse($restaurants as $restaurant)
                    <a href="{{ route('restaurant.menu', $restaurant->id) }}" class="block">
                        <div class="link-card rounded-2xl p-6 text-center">
                            <div class="flex items-center space-x-4">
                                @if($restaurant->logo)
                                    <img src="{{ asset('storage/' . $restaurant->logo) }}"
                                         alt="{{ $restaurant->name }}"
                                         class="restaurant-image"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                @endif
                                <div class="restaurant-image bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center"
                                     style="{{ $restaurant->logo ? 'display: none;' : '' }}">
                                    <i class="fas fa-store text-2xl text-white"></i>
                                </div>

                                <div class="flex-1 text-left">
                                    <h3 class="text-xl font-semibold text-white mb-1">{{ $restaurant->name }}</h3>
                                    <p class="text-white/70 text-sm mb-2">{{ $restaurant->description ?? 'Delicious food awaits you' }}</p>
                                    <div class="flex items-center space-x-3">
                                        @if($restaurant->rating)
                                            <div class="flex items-center">
                                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                                <span class="text-white/80 text-sm ml-1">{{ number_format($restaurant->rating, 1) }}</span>
                                            </div>
                                        @endif
                                        @if($restaurant->delivery_time)
                                            <div class="flex items-center">
                                                <i class="fas fa-clock text-white/60 text-sm"></i>
                                                <span class="text-white/80 text-sm ml-1">{{ $restaurant->delivery_time }} min</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center">
                                            <i class="fas fa-map-marker-alt text-white/60 text-sm"></i>
                                            <span class="text-white/80 text-sm ml-1">{{ $restaurant->address ?? 'Location' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-white/60">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-12">
                        <i class="fas fa-store text-6xl text-white/40 mb-4"></i>
                        <h3 class="text-xl font-semibold text-white mb-2">No Restaurants Available</h3>
                        <p class="text-white/70">Check back later for amazing dining options!</p>
                    </div>
                @endforelse
            </div>

            <!-- Footer -->
            <div class="text-center pt-8">
                <p class="text-white/60 text-sm">
                    Powered by <span class="font-semibold">AdvFood</span>
                </p>
                <div class="flex justify-center space-x-4 mt-4">
                    <a href="#" class="text-white/60 hover:text-white transition-colors">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="#" class="text-white/60 hover:text-white transition-colors">
                        <i class="fab fa-facebook text-xl"></i>
                    </a>
                    <a href="#" class="text-white/60 hover:text-white transition-colors">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                </div>
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
                    <a href="https://wa.me/966501234567?text={{ urlencode('مرحباً، أريد الاستفسار عن طلبي رقم ' . $order->order_number) }}"
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

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </script>
    @endif

    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.link-card');

            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>
