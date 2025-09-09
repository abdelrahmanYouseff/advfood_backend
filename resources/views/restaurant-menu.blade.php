<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $restaurant->name }} - Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .menu-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .cart-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .cart-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
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
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button and Cart -->
            <div class="mb-4 md:mb-6 flex justify-between items-center">
                <a href="{{ route('rest-link') }}" class="inline-flex items-center gap-1 md:gap-2 back-button rounded-full px-3 md:px-4 py-2 text-white hover:text-white text-sm md:text-base">
                    <i class="fas fa-arrow-left text-sm md:text-base"></i>
                    <span class="hidden sm:inline">Back to Restaurants</span>
                    <span class="sm:hidden">Back</span>
                </a>

                <!-- Cart Button -->
                <button onclick="toggleCart()" class="relative inline-flex items-center gap-1 md:gap-2 back-button rounded-full px-3 md:px-4 py-2 text-white hover:text-white text-sm md:text-base">
                    <i class="fas fa-shopping-cart text-sm md:text-base"></i>
                    <span class="hidden sm:inline">Cart</span>
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

                <h1 class="text-2xl md:text-4xl font-bold text-white mb-2">{{ $restaurant->name }}</h1>
                <p class="text-white/80 text-sm md:text-lg mb-3 md:mb-4 px-4">{{ $restaurant->description ?? 'Delicious food awaits you' }}</p>

                <div class="flex justify-center items-center space-x-3 md:space-x-6 text-white/70 mb-4 md:mb-6 text-xs md:text-sm">
                    @if($restaurant->rating)
                        <div class="flex items-center">
                            <i class="fas fa-star text-yellow-400 text-xs md:text-sm"></i>
                            <span class="ml-1">{{ number_format($restaurant->rating, 1) }}</span>
                        </div>
                    @endif
                    @if($restaurant->delivery_time)
                        <div class="flex items-center">
                            <i class="fas fa-clock text-white/60 text-xs md:text-sm"></i>
                            <span class="ml-1">{{ $restaurant->delivery_time }} min</span>
                        </div>
                    @endif
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-white/60 text-xs md:text-sm"></i>
                        <span class="ml-1 hidden sm:inline">{{ $restaurant->address ?? 'Location' }}</span>
                        <span class="ml-1 sm:hidden">Location</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-center items-center space-y-2 sm:space-y-0 sm:space-x-4 mb-6 md:mb-8 px-4">
                    <!-- Rating Button -->
                    <button class="w-full sm:w-auto bg-white/20 hover:bg-white/30 text-white px-4 md:px-6 py-2 md:py-3 rounded-full transition-all duration-300 flex items-center justify-center gap-2 backdrop-blur-sm text-sm md:text-base">
                        <i class="fas fa-star text-yellow-400 text-sm md:text-base"></i>
                        <span>Rate Restaurant</span>
                    </button>

                    <!-- WhatsApp Button -->
                    <a href="https://wa.me/966501234567?text=Hello%20{{ urlencode($restaurant->name) }}%20I%20would%20like%20to%20order%20food"
                       target="_blank"
                       class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white px-4 md:px-6 py-2 md:py-3 rounded-full transition-all duration-300 flex items-center justify-center gap-2 text-sm md:text-base">
                        <i class="fab fa-whatsapp text-lg md:text-xl"></i>
                        <span>Chat With Us</span>
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
                                <h3 class="text-sm md:text-xl font-semibold text-white mb-1 md:mb-2 line-clamp-2">{{ $item->name }}</h3>
                                <p class="text-white/70 text-xs md:text-sm mb-3 md:mb-4 flex-1 line-clamp-2">{{ $item->description ?? 'Delicious item' }}</p>

                                <!-- Price and Add Button -->
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                    <div class="text-lg md:text-2xl font-bold text-white">
                                        {{ number_format($item->price, 2) }} رس
                                    </div>
                                    <button onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ addslashes($item->description ?? 'Delicious item') }}')" class="bg-white/20 hover:bg-white/30 text-white px-3 md:px-4 py-2 rounded-full transition-all duration-300 flex items-center justify-center gap-1 md:gap-2 text-sm md:text-base">
                                        <i class="fas fa-plus text-xs md:text-sm"></i>
                                        <span>Add</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-center py-12">
                        <i class="fas fa-utensils text-6xl text-white/40 mb-4"></i>
                        <h3 class="text-xl font-semibold text-white mb-2">No Menu Items Available</h3>
                        <p class="text-white/70">Check back later for delicious options!</p>
                    </div>
                @endforelse
            </div>

            <!-- Footer -->
            <div class="text-center mt-12">
                <div class="text-white/60 text-sm">
                    <p>Powered by <span class="font-semibold">AdvFood</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full sm:w-80 cart-sidebar transform translate-x-full transition-transform duration-300 z-50 p-4 sm:p-6">
        <div class="flex justify-between items-center mb-4 md:mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-white">Shopping Cart</h2>
            <button onclick="toggleCart()" class="text-white/70 hover:text-white">
                <i class="fas fa-times text-lg md:text-xl"></i>
            </button>
        </div>

        <div id="cart-items" class="space-y-3 md:space-y-4 mb-4 md:mb-6 max-h-80 md:max-h-96 overflow-y-auto">
            <!-- Cart items will be added here -->
        </div>

        <div class="border-t border-white/20 pt-3 md:pt-4">
            <div class="flex justify-between items-center mb-3 md:mb-4">
                <span class="text-white text-base md:text-lg font-semibold">Total:</span>
                <span id="cart-total" class="text-white text-lg md:text-xl font-bold">0.00 رس</span>
            </div>

            <div class="space-y-2 md:space-y-3">
                <button onclick="payNow()" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 md:py-3 rounded-full transition-all duration-300 text-sm md:text-base font-semibold">
                    <i class="fas fa-credit-card mr-2"></i>
                    Pay Now
                </button>
                <button onclick="clearCart()" class="w-full bg-white/20 hover:bg-white/30 text-white py-2 md:py-3 rounded-full transition-all duration-300 text-sm md:text-base">
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

        function addToCart(itemId, itemName, itemPrice, itemDescription) {
            const existingItem = cart.find(item => item.id === itemId);

            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: parseFloat(itemPrice),
                    description: itemDescription,
                    quantity: 1
                });
            }

            updateCartDisplay();
            updateCartBadge();
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
                cartItems.innerHTML = '<p class="text-white/70 text-center py-8">Your cart is empty</p>';
                cartTotalElement.textContent = '0.00 رس';
            } else {
                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item rounded-lg p-3 md:p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-white font-semibold text-sm md:text-base line-clamp-2">${item.name}</h3>
                            <button onclick="removeFromCart(${item.id})" class="text-white/70 hover:text-white">
                                <i class="fas fa-trash text-xs md:text-sm"></i>
                            </button>
                        </div>
                        <p class="text-white/70 text-xs md:text-sm mb-2 md:mb-3 line-clamp-2">${item.description}</p>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-1 md:gap-2">
                                <button onclick="updateQuantity(${item.id}, -1)" class="bg-white/20 hover:bg-white/30 text-white w-6 h-6 md:w-8 md:h-8 rounded-full flex items-center justify-center">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <span class="text-white font-semibold px-2 md:px-3 text-sm md:text-base">${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)" class="bg-white/20 hover:bg-white/30 text-white w-6 h-6 md:w-8 md:h-8 rounded-full flex items-center justify-center">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                            <span class="text-white font-bold text-sm md:text-base">${(item.price * item.quantity).toFixed(2)} رس</span>
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
            const overlay = document.getElementById('cart-overlay');

            if (sidebar.classList.contains('translate-x-full')) {
                sidebar.classList.remove('translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('hidden');
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
    </script>
</body>
</html>
