<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $restaurant->name }} - Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .menu-card {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
        }
        .menu-item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
        }

        /* Mobile menu item image */
        @media (max-width: 640px) {
            .menu-item-image,
            .menu-item-img {
                width: 96px !important;
                height: 96px !important;
                min-width: 96px !important;
                min-height: 96px !important;
                max-width: 96px !important;
                max-height: 96px !important;
                object-fit: cover !important;
                border-radius: 12px;
                flex-shrink: 0 !important;
                display: block !important;
            }

            /* Ensure image container has proper size */
            .menu-card img.menu-item-img {
                width: 96px !important;
                height: 96px !important;
                object-fit: cover !important;
                display: block !important;
            }
        }
        .restaurant-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        .back-button {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .back-button:hover {
            background: #f9fafb;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.15);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .cart-sidebar {
            background: #ffffff;
            border-left: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
        }
        .cart-item {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .cart-item:hover {
            border-color: #667eea;
        }
        .cart-badge {
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            top: -8px;
            right: -8px;
        }

        /* Mobile-specific styles */
        @media (max-width: 640px) {
            /* Cart at bottom for mobile */
            #cart-sidebar {
                width: 100% !important;
                height: auto;
                max-height: 70vh;
                bottom: 0 !important;
                top: auto !important;
                left: 0 !important;
                right: 0 !important;
                transform: translateY(100%) !important;
                border-radius: 24px 24px 0 0;
                border-left: none;
                border-right: none;
                border-bottom: none;
                box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            }

            #cart-sidebar.translate-y-0,
            #cart-sidebar:not(.translate-y-full) {
                transform: translateY(0) !important;
            }

            /* Menu items grid for mobile */
            .menu-items-container {
                padding-bottom: 80px !important;
            }

            /* Mobile cart button fixed at bottom - matching design */
            .mobile-cart-button {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                background: #FFD700 !important;
                color: #000 !important;
                padding: 16px 20px !important;
                z-index: 40 !important;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.15) !important;
                border-radius: 0 !important;
                cursor: pointer !important;
                user-select: none !important;
                min-height: 60px !important;
                display: flex !important;
                align-items: center !important;
                width: 100% !important;
            }

            .mobile-cart-button.hidden {
                display: none !important;
            }

            .mobile-cart-button:active {
                background: #FFC700;
                transform: scale(0.98);
            }

            .mobile-cart-button .cart-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }

            .mobile-cart-button .view-cart-text {
                font-weight: 700;
                font-size: 17px;
                color: #000;
                letter-spacing: -0.3px;
            }

            .mobile-cart-button .price-container {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 3px;
            }

            .mobile-cart-button .old-price {
                text-decoration: line-through;
                color: #666;
                font-size: 13px;
                line-height: 1.1;
                font-weight: 400;
            }

            .mobile-cart-button .current-price {
                color: #000;
                font-weight: 700;
                font-size: 19px;
                line-height: 1.1;
                letter-spacing: -0.5px;
            }

            .mobile-cart-button .currency-symbol {
                font-size: 13px;
                margin-right: 2px;
            }
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translate(-50%, 0);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button and Cart -->
            <div class="mb-4 md:mb-6 flex justify-between items-center">
                <a href="{{ route('rest-link') }}" class="inline-flex items-center gap-1 md:gap-2 back-button rounded-full px-3 md:px-4 py-2 text-gray-700 hover:text-purple-600 text-sm md:text-base">
                    <i class="fas fa-arrow-left text-sm md:text-base back-arrow"></i>
                    <span class="hidden sm:inline" id="back-text-full">Back to Restaurants</span>
                    <span class="sm:hidden" id="back-text-short">Back</span>
                </a>

                <!-- Cart Button -->
                <button onclick="toggleCart()" class="relative inline-flex items-center gap-1 md:gap-2 back-button rounded-full px-3 md:px-4 py-2 text-gray-700 hover:text-purple-600 text-sm md:text-base">
                    <i class="fas fa-shopping-cart text-sm md:text-base"></i>
                    <span class="hidden sm:inline" id="cart-text">السلة</span>
                    <div id="cart-badge" class="cart-badge" style="display: none;">0</div>
                </button>
            </div>

            <!-- Restaurant Header -->
            <div class="text-center mb-6 md:mb-8">
                <div class="mx-auto mb-4 md:mb-6">
                    @if($restaurant->logo)
                        <img src="{{ asset('storage/' . $restaurant->logo) }}"
                             alt="{{ $restaurant->name }}"
                             class="restaurant-logo mx-auto w-16 h-16 md:w-24 md:h-24"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    @endif
                    <div class="restaurant-logo bg-gradient-to-br from-purple-500 to-blue-600 flex items-center justify-center mx-auto w-16 h-16 md:w-24 md:h-24"
                         style="{{ $restaurant->logo ? 'display: none;' : '' }}">
                        <i class="fas fa-store text-xl md:text-3xl text-white"></i>
                    </div>
                </div>

                <h1 class="text-2xl md:text-4xl font-bold text-gray-800 mb-2">{{ $restaurant->name }}</h1>
                <p class="text-gray-600 text-sm md:text-lg mb-3 md:mb-4 px-4">{{ $restaurant->description ?? 'Delicious food awaits you' }}</p>

                <div class="flex justify-center items-center space-x-3 md:space-x-6 text-gray-600 mb-4 md:mb-6 text-xs md:text-sm">
                    @if($restaurant->rating)
                        <div class="flex items-center">
                            <i class="fas fa-star text-yellow-500 text-xs md:text-sm"></i>
                            <span class="ml-1 text-gray-700">{{ number_format($restaurant->rating, 1) }}</span>
                        </div>
                    @endif
                    @if($restaurant->delivery_time)
                        <div class="flex items-center">
                            <i class="fas fa-clock text-purple-500 text-xs md:text-sm"></i>
                            <span class="ml-1 time-text">{{ $restaurant->delivery_time }} <span class="time-unit">min</span></span>
                        </div>
                    @endif
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-purple-500 text-xs md:text-sm"></i>
                        <span class="ml-1 hidden sm:inline">{{ $restaurant->address ?? 'Location' }}</span>
                        <span class="ml-1 sm:hidden location-text">Location</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center items-center mb-6 md:mb-8 px-4">
                    <!-- WhatsApp Button -->
                    <a href="https://wa.me/966507844079?text=Hello%20{{ urlencode($restaurant->name) }}%20I%20would%20like%20to%20order%20food"
                       target="_blank"
                       class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-full transition-all duration-300 flex items-center justify-center gap-2 text-sm md:text-base shadow-md hover:shadow-lg">
                        <i class="fab fa-whatsapp text-lg md:text-xl"></i>
                        <span id="whatsapp-btn-text">Chat With Us</span>
                    </a>
                </div>
            </div>

            <!-- Menu Items Grid - Adjusted for visible cart -->
            <div class="menu-items-container grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 pr-0 sm:pr-80">
                @forelse($menuItems as $item)
                    <div class="menu-card rounded-2xl p-4 sm:p-6 bg-white">
                        <div class="flex gap-4 sm:flex-col sm:gap-0">
                            <!-- Item Image - Mobile: side, Desktop: top -->
                            <div class="mb-0 sm:mb-4 flex-shrink-0 w-24 h-24 sm:w-full sm:h-auto">
                                @if($item->image && !empty($item->image))
                                    @php
                                        $imageUrl = asset('storage/' . $item->image);
                                    @endphp
                                    <img src="{{ $imageUrl }}"
                                         alt="{{ $item->name }}"
                                         class="menu-item-img w-24 h-24 sm:w-full sm:h-48 object-cover rounded-xl sm:rounded-lg"
                                         style="display: block !important; width: 96px !important; height: 96px !important; max-width: 96px !important; max-height: 96px !important;"
                                         loading="lazy"
                                         onerror="console.error('Image error:', this.src); this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                                         onload="console.log('Image loaded:', this.src); this.style.display='block';">
                                    <div class="w-24 h-24 sm:w-full sm:h-48 bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center rounded-xl sm:rounded-lg fallback-image"
                                         style="display: none;">
                                        <i class="fas fa-utensils text-2xl sm:text-4xl text-white"></i>
                                    </div>
                                @else
                                    <div class="w-24 h-24 sm:w-full sm:h-48 bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center rounded-xl sm:rounded-lg">
                                        <i class="fas fa-utensils text-2xl sm:text-4xl text-white"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Item Details -->
                            <div class="flex-1 flex flex-col min-w-0">
                                <h3 class="text-base sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2 line-clamp-2">{{ $item->name }}</h3>
                                <p class="text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4 flex-1 line-clamp-2 leading-relaxed">{{ $item->description ?? 'Delicious item' }}</p>

                                <!-- Price and Add Button -->
                                <div class="flex items-center justify-between gap-3 mt-auto">
                                    <div class="text-xl sm:text-2xl font-bold text-purple-600 whitespace-nowrap">
                                        {{ number_format($item->price, 2) }} <span class="currency-text text-sm sm:text-base">رس</span>
                                    </div>
                                    <button onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ addslashes(str_replace(["\r", "\n"], ' ', $item->description ?? 'Delicious item')) }}')" class="bg-purple-500 hover:bg-purple-600 active:bg-purple-700 text-white px-5 sm:px-4 py-2.5 sm:py-2 rounded-full transition-all duration-300 flex items-center justify-center gap-2 text-sm sm:text-base shadow-md hover:shadow-lg active:scale-95 add-to-cart-btn flex-shrink-0">
                                        <i class="fas fa-plus text-xs sm:text-sm"></i>
                                        <span class="add-btn-text hidden sm:inline">إضافة</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-center py-12">
                        <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                        <h3 id="no-menu-title" class="text-xl font-semibold text-gray-800 mb-2">No Menu Items Available</h3>
                        <p id="no-menu-text" class="text-gray-600">Check back later for delicious options!</p>
                    </div>
                @endforelse
            </div>

            <!-- Footer -->
            <div class="text-center mt-12">
                <div class="text-gray-500 text-sm">
                    <p><span id="powered-by-text">Powered by</span> <span class="font-semibold" style="color: #cf4823;">AdvFood</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Cart Button (Fixed Bottom) -->
    <div id="mobile-cart-button" class="mobile-cart-button sm:hidden" onclick="toggleCart()">
        <div class="cart-content">
            <div class="view-cart-text">
                عرض السلة (<span id="mobile-cart-count">0</span>)
            </div>
            <div class="price-container">
                <div class="old-price" id="mobile-old-price" style="display: none;">
                    <span class="currency-symbol">SR</span> <span id="mobile-old-price-value">0.00</span>
                </div>
                <div class="current-price">
                    <span class="currency-symbol">SR</span> <span id="mobile-cart-total-value">0.00</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Sidebar - Desktop: Right Side, Mobile: Bottom -->
    <div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full sm:w-80 cart-sidebar transform translate-x-0 sm:translate-x-0 translate-y-full sm:translate-y-0 transition-transform duration-300 z-50 p-4 sm:p-6">
        <div class="flex justify-between items-center mb-4 md:mb-6">
            <h2 id="cart-title" class="text-xl md:text-2xl font-bold text-gray-800">سلة التسوق</h2>
        </div>

        <div id="cart-items" class="space-y-3 md:space-y-4 mb-4 md:mb-6 max-h-[40vh] sm:max-h-80 md:max-h-96 overflow-y-auto">
            <!-- Cart items will be added here -->
        </div>

        <div class="border-t border-gray-200 pt-3 md:pt-4 pb-4 sm:pb-0">
            <div class="flex justify-between items-center mb-1.5 md:mb-2">
                <span id="total-text" class="text-gray-700 text-base md:text-lg font-semibold">المجموع:</span>
                <span id="cart-total" class="text-purple-600 text-lg md:text-xl font-bold">0.00 رس</span>
            </div>
            <div class="space-y-0.5 text-xs md:text-sm text-gray-500 mb-3 md:mb-4">
                <div id="cart-subtotal-row" class="flex justify-between">
                    <span>قيمة المنتجات</span>
                    <span id="cart-subtotal">0.00 رس</span>
                </div>
                <div id="cart-delivery-row" class="flex justify-between">
                    <span>رسوم التوصيل (ثابتة)</span>
                    <span id="cart-delivery-fee">18.00 رس</span>
                </div>
                <div id="cart-grand-row" class="flex justify-between font-semibold text-gray-700">
                    <span>الإجمالي (مع التوصيل)</span>
                    <span id="cart-grand-total">0.00 رس</span>
                </div>
            </div>

            <div class="space-y-2 md:space-y-3">
                <button onclick="payNow()" class="w-full bg-black hover:bg-gray-800 active:bg-gray-900 text-white py-3 md:py-3 rounded-full transition-all duration-300 text-base md:text-base font-semibold shadow-md hover:shadow-lg active:scale-95">
                    <i class="fas fa-credit-card mr-2 pay-icon"></i>
                    <span id="pay-now-btn">ادفع الآن</span>
                </button>
                <button onclick="clearCart()" id="clear-cart-btn" class="w-full bg-gray-200 hover:bg-gray-300 active:bg-gray-400 text-gray-700 py-2.5 md:py-3 rounded-full transition-all duration-300 text-sm md:text-base active:scale-95">
                    إفراغ السلة
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Overlay - For mobile -->
    <div id="cart-overlay" class="fixed inset-0 bg-black/50 z-40 hidden sm:hidden" onclick="closeCart();"></div>

    <script>
        let cart = [];
        let cartTotal = 0; // إجمالي أصناف السلة بدون التوصيل
        const DELIVERY_FEE_FIXED = 18; // رسوم توصيل ثابتة لكل طلب (رس)

        // Translations
        const translations = {
            en: {
                backTextFull: 'Back to Restaurants',
                backTextShort: 'Back',
                cartText: 'Cart',
                timeUnit: 'min',
                locationText: 'Location',
                whatsappBtn: 'Chat With Us',
                addBtn: 'Add',
                noMenuTitle: 'No Menu Items Available',
                noMenuText: 'Check back later for delicious options!',
                poweredBy: 'Powered by',
                cartTitle: 'Shopping Cart',
                totalText: 'Total:',
                payNowBtn: 'Pay Now',
                clearCartBtn: 'Clear Cart',
                emptyCart: 'Your cart is empty',
                addedToCart: 'Added to cart',
                updatedCart: 'Updated in cart'
            },
            ar: {
                backTextFull: 'العودة للمطاعم',
                backTextShort: 'رجوع',
                cartText: 'السلة',
                timeUnit: 'دقيقة',
                locationText: 'الموقع',
                whatsappBtn: 'تواصل معنا',
                addBtn: 'إضافة',
                noMenuTitle: 'لا توجد أصناف متاحة',
                noMenuText: 'تحقق لاحقاً للحصول على خيارات لذيذة!',
                poweredBy: 'مدعوم بواسطة',
                cartTitle: 'سلة التسوق',
                totalText: 'المجموع:',
                payNowBtn: 'ادفع الآن',
                clearCartBtn: 'إفراغ السلة',
                emptyCart: 'سلتك فارغة',
                addedToCart: 'تمت الإضافة للسلة',
                updatedCart: 'تم التحديث في السلة'
            }
        };

        let currentLang = 'ar'; // Default to Arabic

        function addToCart(itemId, itemName, itemPrice, itemDescription) {
            const existingItem = cart.find(item => item.id === itemId);
            const t = translations[currentLang];

            if (existingItem) {
                existingItem.quantity += 1;
                const message = currentLang === 'ar' ? `تم تحديث ${itemName} في السلة` : `${itemName} ${t.updatedCart}`;
                showNotification(message);
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: parseFloat(itemPrice),
                    description: itemDescription,
                    quantity: 1
                });
                const message = currentLang === 'ar' ? `تمت إضافة ${itemName} للسلة` : `${itemName} ${t.addedToCart}`;
                showNotification(message);
            }

            updateCartDisplay();
            updateCartBadge();
            updateMobileCartButton();

            // Open cart sidebar automatically (stays open while adding more items)
            if (window.innerWidth >= 640) {
                openCart(); // Only auto-open on desktop
            }

            // Add pulse animation to cart
            pulseCart();
        }

        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-full shadow-xl z-50 flex items-center gap-2';
            notification.style.animation = 'slideDown 0.3s ease-out';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;

            document.body.appendChild(notification);

            // Remove after 2 seconds
            setTimeout(() => {
                notification.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        }

        function pulseCart() {
            const sidebar = document.getElementById('cart-sidebar');
            sidebar.style.transform = 'translateX(0) scale(1.02)';
            setTimeout(() => {
                sidebar.style.transform = 'translateX(0) scale(1)';
            }, 200);
        }

        function openCart() {
            const sidebar = document.getElementById('cart-sidebar');
            const overlay = document.getElementById('cart-overlay');
            const mobileButton = document.getElementById('mobile-cart-button');

            if (window.innerWidth < 640) {
                // Mobile: slide up from bottom
                if (sidebar) {
                    sidebar.classList.remove('translate-y-full');
                    sidebar.classList.add('translate-y-0');
                }
                if (overlay) {
                    overlay.classList.remove('hidden');
                }
                if (mobileButton) {
                    mobileButton.classList.add('hidden');
                }
            } else {
                // Desktop: show on right
                if (sidebar) {
                    sidebar.classList.remove('translate-x-full');
                    sidebar.classList.add('translate-x-0');
                }
            }
        }

        function closeCart() {
            const sidebar = document.getElementById('cart-sidebar');
            const overlay = document.getElementById('cart-overlay');
            const mobileButton = document.getElementById('mobile-cart-button');

            if (window.innerWidth < 640) {
                // Mobile: slide down
                if (sidebar) {
                    sidebar.classList.add('translate-y-full');
                    sidebar.classList.remove('translate-y-0');
                }
                if (overlay) {
                    overlay.classList.add('hidden');
                }
                if (mobileButton) {
                    mobileButton.classList.remove('hidden');
                }
            }
            // On desktop, cart stays open
        }

        function removeFromCart(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            updateCartDisplay();
            updateCartBadge();
        }

        function updateQuantity(itemId, change) {
            const item = cart.find(item => item.id === itemId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(itemId);
                } else {
                    updateCartDisplay();
                    updateCartBadge();
                }
            }
        }

        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const cartTotalElement = document.getElementById('cart-total');
            const cartSubtotalElement = document.getElementById('cart-subtotal');
            const cartDeliveryFeeElement = document.getElementById('cart-delivery-fee');
            const cartSubtotalRow = document.getElementById('cart-subtotal-row');
            const cartDeliveryRow = document.getElementById('cart-delivery-row');
            const cartGrandRow = document.getElementById('cart-grand-row');
            const cartGrandTotalElement = document.getElementById('cart-grand-total');

            // إجمالي قيمة المنتجات فقط
            cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            // المجموع النهائي المعروض للمستخدم (منتجات + توصيل ثابت)
            const grandTotal = cart.length > 0 ? (cartTotal + DELIVERY_FEE_FIXED) : 0;

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-gray-500 text-center py-8">سلتك فارغة</p>';
                cartTotalElement.textContent = '0.00 رس'; // لا يوجد طلب بعد
                if (cartSubtotalElement) cartSubtotalElement.textContent = '0.00 رس';
                // نظهر رسوم التوصيل الثابتة 18 رس حتى مع عدم وجود أصناف (للتوضيح)
                if (cartDeliveryFeeElement) cartDeliveryFeeElement.textContent = DELIVERY_FEE_FIXED.toFixed(2) + ' رس';
                if (cartSubtotalRow) cartSubtotalRow.style.opacity = '0.6';
                if (cartDeliveryRow) cartDeliveryRow.style.opacity = '0.6';
                if (cartGrandTotalElement) cartGrandTotalElement.textContent = '0.00 رس';
                if (cartGrandRow) cartGrandRow.style.opacity = '0.6';
            } else {
                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item rounded-lg p-3 md:p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-gray-800 font-semibold text-sm md:text-base line-clamp-2">${item.name}</h3>
                            <button onclick="removeFromCart(${item.id})" class="text-gray-500 hover:text-red-500 transition-colors">
                                <i class="fas fa-trash text-xs md:text-sm"></i>
                            </button>
                        </div>
                        <p class="text-gray-600 text-xs md:text-sm mb-2 md:mb-3 line-clamp-2">${item.description}</p>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1 md:gap-2">
                                <button onclick="updateQuantity(${item.id}, -1)" class="bg-gray-200 hover:bg-gray-300 text-gray-700 w-6 h-6 md:w-8 md:h-8 rounded-full flex items-center justify-center transition-all">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="text-gray-800 font-semibold px-2 md:px-3 text-sm md:text-base">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="bg-purple-500 hover:bg-purple-600 text-white w-6 h-6 md:w-8 md:h-8 rounded-full flex items-center justify-center transition-all">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="text-purple-600 font-bold text-sm md:text-base">${(item.price * item.quantity).toFixed(2)} رس</span>
                        </div>
                    </div>
                `).join('');

                // عرض "المجموع" = قيمة المنتجات + رسوم التوصيل الثابتة (18 رس)
                cartTotalElement.textContent = grandTotal.toFixed(2) + ' رس';

                // عرض قيمة المنتجات ورسوم التوصيل الثابتة
                if (cartSubtotalElement) cartSubtotalElement.textContent = cartTotal.toFixed(2) + ' رس';
                if (cartDeliveryFeeElement) cartDeliveryFeeElement.textContent = DELIVERY_FEE_FIXED.toFixed(2) + ' رس';
                if (cartSubtotalRow) cartSubtotalRow.style.opacity = '1';
                if (cartDeliveryRow) cartDeliveryRow.style.opacity = '1';
                // عرض الإجمالي (مع التوصيل) في السطر الجديد
                if (cartGrandTotalElement) cartGrandTotalElement.textContent = grandTotal.toFixed(2) + ' رس';
                if (cartGrandRow) cartGrandRow.style.opacity = '1';
            }
        }

        function updateCartBadge() {
            const badge = document.getElementById('cart-badge');
            const mobileBadge = document.getElementById('mobile-cart-badge');
            const totalItems = cart.reduce((total, item) => total + item.quantity, 0);

            if (totalItems > 0) {
                if (badge) {
                    badge.textContent = totalItems;
                    badge.style.display = 'flex';
                }
                if (mobileBadge) {
                    mobileBadge.textContent = totalItems;
                    mobileBadge.style.display = 'flex';
                }
            } else {
                if (badge) badge.style.display = 'none';
                if (mobileBadge) mobileBadge.style.display = 'none';
            }
        }

        function updateMobileCartButton() {
            const mobileButton = document.getElementById('mobile-cart-button');
            const mobileCartTotal = document.getElementById('mobile-cart-total-value');
            const mobileCartCount = document.getElementById('mobile-cart-count');
            const mobileOldPrice = document.getElementById('mobile-old-price');
            const mobileOldPriceValue = document.getElementById('mobile-old-price-value');
            const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
            const grandTotal = cartTotal + (cart.length > 0 ? DELIVERY_FEE_FIXED : 0);

            if (mobileCartTotal) {
                // إظهار الإجمالي شامل التوصيل في زر السلة للموبايل
                mobileCartTotal.textContent = grandTotal.toFixed(2);
            }

            if (mobileCartCount) {
                mobileCartCount.textContent = totalItems;
            }

            // Calculate original total (without discounts if any)
            // For now, we'll use the same total, but you can add discount logic here
            const originalTotal = cartTotal; // Replace with actual original total if you have discounts

            // Show old price only if there's a discount (originalTotal > cartTotal)
            if (mobileOldPrice && mobileOldPriceValue) {
                if (originalTotal > cartTotal) {
                    mobileOldPriceValue.textContent = originalTotal.toFixed(2);
                    mobileOldPrice.style.display = 'block';
                } else {
                    mobileOldPrice.style.display = 'none';
                }
            }

            // Show button on mobile - always visible
            if (mobileButton && window.innerWidth < 640) {
                // Always show button (remove hidden class if present)
                mobileButton.classList.remove('hidden');
            }
        }

        function toggleCart() {
            const sidebar = document.getElementById('cart-sidebar');

            if (window.innerWidth < 640) {
                // Mobile: toggle slide up/down
                if (sidebar.classList.contains('translate-y-full')) {
                    openCart();
                } else {
                    closeCart();
                }
            } else {
                // Desktop: toggle side
                if (sidebar.classList.contains('translate-x-full')) {
                    openCart();
                } else {
                    closeCart();
                }
            }
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
            updateCartBadge();
            updateMobileCartButton();
            if (window.innerWidth < 640) {
                closeCart();
            }
        }

        function payNow() {
            if (cart.length === 0) {
                alert('سلتك فارغة!');
                return;
            }

            // Store cart data in sessionStorage
            sessionStorage.setItem('cartData', JSON.stringify(cart));
            sessionStorage.setItem('cartTotal', cartTotal.toString());
            sessionStorage.setItem('restaurantId', '{{ $restaurant->id }}');

            // Redirect to customer details page
            window.location.href = '{{ route("checkout.customer-details") }}';
        }

        // Initialize cart display
        updateCartDisplay();

        // Initialize mobile cart button on page load
        updateMobileCartButton();

        // Make sure mobile button shows when items are added
        const originalAddToCart = addToCart;
        window.addToCart = function(...args) {
            originalAddToCart(...args);
            updateMobileCartButton();
        };

        function applyTranslations(lang) {
            currentLang = lang;
            const t = translations[lang];
            const htmlRoot = document.getElementById('html-root');

            // Update direction
            if (lang === 'ar') {
                htmlRoot.setAttribute('lang', 'ar');
                htmlRoot.setAttribute('dir', 'rtl');
            } else {
                htmlRoot.setAttribute('lang', 'en');
                htmlRoot.setAttribute('dir', 'ltr');
            }

            // Update text elements
            const backTextFull = document.getElementById('back-text-full');
            const backTextShort = document.getElementById('back-text-short');
            const cartText = document.getElementById('cart-text');
            const timeUnits = document.querySelectorAll('.time-unit');
            const locationTexts = document.querySelectorAll('.location-text');
            const whatsappBtn = document.getElementById('whatsapp-btn-text');
            const addBtnTexts = document.querySelectorAll('.add-btn-text');
            const noMenuTitle = document.getElementById('no-menu-title');
            const noMenuText = document.getElementById('no-menu-text');
            const poweredByText = document.getElementById('powered-by-text');
            const cartTitle = document.getElementById('cart-title');
            const totalText = document.getElementById('total-text');
            const payNowBtn = document.getElementById('pay-now-btn');
            const clearCartBtn = document.getElementById('clear-cart-btn');

            if (backTextFull) backTextFull.textContent = t.backTextFull;
            if (backTextShort) backTextShort.textContent = t.backTextShort;
            if (cartText) cartText.textContent = t.cartText;
            if (whatsappBtn) whatsappBtn.textContent = t.whatsappBtn;
            if (noMenuTitle) noMenuTitle.textContent = t.noMenuTitle;
            if (noMenuText) noMenuText.textContent = t.noMenuText;
            if (poweredByText) poweredByText.textContent = t.poweredBy;
            if (cartTitle) cartTitle.textContent = t.cartTitle;
            if (totalText) totalText.textContent = t.totalText;
            if (payNowBtn) payNowBtn.textContent = t.payNowBtn;
            if (clearCartBtn) clearCartBtn.textContent = t.clearCartBtn;

            timeUnits.forEach(unit => unit.textContent = t.timeUnit);
            locationTexts.forEach(text => text.textContent = t.locationText);
            addBtnTexts.forEach(btn => btn.textContent = t.addBtn);

            // Update arrow direction
            const backArrow = document.querySelector('.back-arrow');
            if (backArrow) {
                if (lang === 'ar') {
                    backArrow.classList.remove('fa-arrow-left');
                    backArrow.classList.add('fa-arrow-right');
                } else {
                    backArrow.classList.remove('fa-arrow-right');
                    backArrow.classList.add('fa-arrow-left');
                }
            }

            // Update pay icon margin
            const payIcon = document.querySelector('.pay-icon');
            if (payIcon) {
                if (lang === 'ar') {
                    payIcon.classList.remove('mr-2');
                    payIcon.classList.add('ml-2');
                } else {
                    payIcon.classList.remove('ml-2');
                    payIcon.classList.add('mr-2');
                }
            }

            // Update cart display with translated empty cart text
            updateCartDisplay();
        }

        // Override updateCartDisplay to support translations (بدون تغيير منطق الحساب)
        const originalUpdateCartDisplay = updateCartDisplay;
        updateCartDisplay = function() {
            // نفذ منطق الحساب والعرض الأساسي (يشمل رسوم التوصيل والإجمالي)
            originalUpdateCartDisplay();

            // لو السلة فاضية نطبق النص المترجم لرسالة "سلتك فارغة"
            const cartItems = document.getElementById('cart-items');
            if (cartItems && cart.length === 0) {
                const t = translations[currentLang];
                cartItems.innerHTML = `<p class="text-gray-500 text-center py-8">${t.emptyCart}</p>`;
            }

            // تحديث زر السلة في الموبايل
            updateMobileCartButton();
        };

        // Load saved language preference on page load, default to Arabic
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('preferred_language') || 'ar';
            applyTranslations(savedLang);

            // Add event listeners for cart close functionality
            const closeBtn = document.getElementById('close-cart-btn');
            const closeBtnBottom = document.getElementById('close-cart-btn-bottom');
            const overlay = document.getElementById('cart-overlay');

            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Close button (top) clicked via event listener');
                    closeCart();
                });
            }

            if (closeBtnBottom) {
                closeBtnBottom.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Close button (bottom) clicked via event listener');
                    closeCart();
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Overlay clicked via event listener');
                    closeCart();
                });
            }
        });
    </script>
</body>
</html>
