<!DOCTYPE html>
<html lang="en" id="html-root">
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
                    <span class="hidden sm:inline" id="cart-text">Cart</span>
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

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-2 md:grid-cols-2 gap-3 md:gap-6">
                @forelse($menuItems as $item)
                    <div class="menu-card rounded-xl md:rounded-2xl p-3 md:p-6">
                        <div class="flex flex-col h-full">
                            <!-- Item Image -->
                            <div class="mb-3 md:mb-4">
                                @if($item->image)
                                    <img src="{{ asset('storage/' . $item->image) }}"
                                         alt="{{ $item->name }}"
                                         class="menu-item-image w-full h-24 md:h-48 object-cover rounded-lg"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                @endif
                                <div class="menu-item-image w-full h-24 md:h-48 bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center rounded-lg"
                                     style="{{ $item->image ? 'display: none;' : '' }}">
                                    <i class="fas fa-utensils text-lg md:text-4xl text-white"></i>
                                </div>
                            </div>

                            <!-- Item Details -->
                            <div class="flex-1 flex flex-col">
                                <h3 class="text-sm md:text-xl font-semibold text-gray-800 mb-1 md:mb-2 line-clamp-2">{{ $item->name }}</h3>
                                <p class="text-black text-xs md:text-sm mb-3 md:mb-4 flex-1 line-clamp-2">{{ $item->description ?? 'Delicious item' }}</p>

                                <!-- Price and Add Button -->
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <div class="text-lg md:text-2xl font-bold text-purple-600">
                                        {{ number_format($item->price, 2) }} <span class="currency-text">رس</span>
                                    </div>
                                    <button onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ addslashes(str_replace(["\r", "\n"], ' ', $item->description ?? 'Delicious item')) }}')" class="bg-purple-500 hover:bg-purple-600 text-white px-3 md:px-4 py-2 rounded-full transition-all duration-300 flex items-center justify-center gap-1 md:gap-2 text-sm md:text-base shadow-md hover:shadow-lg add-to-cart-btn">
                                        <i class="fas fa-plus text-xs md:text-sm"></i>
                                        <span class="add-btn-text">Add</span>
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

    <!-- Cart Sidebar -->
    <div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full sm:w-80 cart-sidebar transform translate-x-full transition-transform duration-300 z-50 p-4 sm:p-6">
        <div class="flex justify-between items-center mb-4 md:mb-6">
            <h2 id="cart-title" class="text-xl md:text-2xl font-bold text-gray-800">Shopping Cart</h2>
            <button onclick="toggleCart()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-lg md:text-xl"></i>
            </button>
        </div>

        <div id="cart-items" class="space-y-3 md:space-y-4 mb-4 md:mb-6 max-h-80 md:max-h-96 overflow-y-auto">
            <!-- Cart items will be added here -->
        </div>

        <div class="border-t border-gray-200 pt-3 md:pt-4">
            <div class="flex justify-between items-center mb-3 md:mb-4">
                <span id="total-text" class="text-gray-700 text-base md:text-lg font-semibold">Total:</span>
                <span id="cart-total" class="text-purple-600 text-lg md:text-xl font-bold">0.00 رس</span>
            </div>

            <div class="space-y-2 md:space-y-3">
                <button onclick="payNow()" class="w-full bg-purple-500 hover:bg-purple-600 text-white py-2 md:py-3 rounded-full transition-all duration-300 text-sm md:text-base font-semibold shadow-md hover:shadow-lg">
                    <i class="fas fa-credit-card mr-2 pay-icon"></i>
                    <span id="pay-now-btn">Pay Now</span>
                </button>
                <button onclick="clearCart()" id="clear-cart-btn" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 md:py-3 rounded-full transition-all duration-300 text-sm md:text-base">
                    Clear Cart
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div id="cart-overlay" class="fixed inset-0 bg-black/50 z-40 hidden" onclick="toggleCart()"></div>

    <script>
        let cart = [];
        let cartTotal = 0;

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

        let currentLang = 'en';

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

            // Open cart sidebar automatically (stays open while adding more items)
            openCart();

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

            sidebar.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeCart() {
            const sidebar = document.getElementById('cart-sidebar');
            const overlay = document.getElementById('cart-overlay');

            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
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

            cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="text-gray-500 text-center py-8">Your cart is empty</p>';
                cartTotalElement.textContent = '0.00 رس';
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

                cartTotalElement.textContent = cartTotal.toFixed(2) + ' رس';
            }
        }

        function updateCartBadge() {
            const badge = document.getElementById('cart-badge');
            const totalItems = cart.reduce((total, item) => total + item.quantity, 0);

            if (totalItems > 0) {
                badge.textContent = totalItems;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        function toggleCart() {
            const sidebar = document.getElementById('cart-sidebar');

            if (sidebar.classList.contains('translate-x-full')) {
                openCart();
            } else {
                closeCart();
            }
        }

        function clearCart() {
            cart = [];
            updateCartDisplay();
            updateCartBadge();
        }

        function payNow() {
            if (cart.length === 0) {
                alert('Your cart is empty!');
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

        // Override updateCartDisplay to support translations
        const originalUpdateCartDisplay = updateCartDisplay;
        updateCartDisplay = function() {
            const cartItems = document.getElementById('cart-items');
            const cartTotalElement = document.getElementById('cart-total');

            cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);

            if (cart.length === 0) {
                const t = translations[currentLang];
                cartItems.innerHTML = `<p class="text-gray-500 text-center py-8">${t.emptyCart}</p>`;
                cartTotalElement.textContent = '0.00 رس';
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

                cartTotalElement.textContent = cartTotal.toFixed(2) + ' رس';
            }
        };

        // Load saved language preference on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('preferred_language');
            if (savedLang) {
                applyTranslations(savedLang);
            }
        });
    </script>
</body>
</html>
