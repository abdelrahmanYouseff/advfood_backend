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

    <!-- Chatbot -->
    <div id="chatbot" class="fixed bottom-6 right-6 z-50">
        <!-- Chat Toggle Button -->
        <div id="chatToggle" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-full w-16 h-16 flex items-center justify-center cursor-pointer shadow-2xl transition-all duration-300 hover:scale-110 hover:shadow-blue-500/25">
            <i class="fas fa-comments text-xl"></i>
            <!-- Notification dot -->
            <div id="notificationDot" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                <span class="text-xs text-white font-bold">!</span>
            </div>
        </div>

        <!-- Chat Window -->
        <div id="chatWindow" class="hidden absolute bottom-20 right-0 w-[450px] h-[600px] bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden backdrop-blur-sm">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-blue-700 text-white p-5 flex items-center justify-between relative">
                <!-- Background pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-2 right-2 w-20 h-20 bg-white rounded-full"></div>
                    <div class="absolute bottom-2 left-2 w-16 h-16 bg-white rounded-full"></div>
                </div>
                
                <div class="flex items-center relative z-10">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mr-4 backdrop-blur-sm">
                        <i class="fas fa-robot text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">AdvFood Assistant</h3>
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                            <p class="text-sm text-blue-100">Online now</p>
                        </div>
                    </div>
                </div>
                <button id="closeChat" class="text-white/80 hover:text-white hover:bg-white/20 rounded-full p-2 transition-all duration-200 relative z-10">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Chat Messages -->
            <div id="chatMessages" class="h-96 overflow-y-auto p-6 space-y-4 bg-gradient-to-b from-gray-50 to-white">
                <!-- Messages will be added here -->
            </div>

            <!-- Chat Input -->
            <div class="border-t border-gray-200 p-6 bg-white">
                <div class="flex items-center space-x-4">
                    <div class="flex-1 relative">
                        <input type="text" id="chatInput" placeholder="Type your message..." 
                               class="w-full border-2 border-gray-200 rounded-2xl px-6 py-4 text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 focus:bg-white">
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-smile text-gray-400 hover:text-blue-500 cursor-pointer transition-colors text-lg"></i>
                        </div>
                    </div>
                    <button id="sendMessage" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl w-14 h-14 flex items-center justify-center hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-blue-500/25 hover:scale-105">
                        <i class="fas fa-paper-plane text-base"></i>
                    </button>
                </div>
                <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
                    <span>Press Enter to send</span>
                    <span>Powered by AdvFood AI</span>
                </div>
            </div>
        </div>
    </div>

    <style>
        .chat-message {
            animation: slideIn 0.4s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(45deg, #3B82F6, #8B5CF6);
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        .typing-dot:nth-child(3) { animation-delay: 0s; }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1.2);
                opacity: 1;
            }
        }

        /* Custom scrollbar for chat messages */
        #chatMessages::-webkit-scrollbar {
            width: 6px;
        }
        
        #chatMessages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        #chatMessages::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #3B82F6, #8B5CF6);
            border-radius: 10px;
        }
        
        #chatMessages::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #2563EB, #7C3AED);
        }

        /* Chat window entrance animation */
        #chatWindow {
            animation: chatSlideIn 0.3s ease-out;
        }
        
        @keyframes chatSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Notification dot animation */
        #notificationDot {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>

    <script>
        let chatOpen = false;
        let messageCount = 0;
        let autoOpenTimer;

        // Auto-open chat after 10 seconds
        function startAutoOpen() {
            autoOpenTimer = setTimeout(() => {
                if (!chatOpen) {
                    openChat();
                }
            }, 10000); // 10 seconds
        }

        // Start auto-open when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startAutoOpen();
            initializeChat();
        });

        function initializeChat() {
            const chatToggle = document.getElementById('chatToggle');
            const chatWindow = document.getElementById('chatWindow');
            const closeChat = document.getElementById('closeChat');
            const sendButton = document.getElementById('sendMessage');
            const chatInput = document.getElementById('chatInput');

            // Add initial welcome message with quick options
            addMessage('bot', 'Hello! 👋 Welcome to AdvFood! I\'m here to help you find the perfect restaurant. How can I assist you today?');
            
            // Add quick options
            setTimeout(() => {
                addQuickOptions();
            }, 1000);

            chatToggle.addEventListener('click', toggleChat);
            closeChat.addEventListener('click', closeChatWindow);
            sendButton.addEventListener('click', sendMessage);
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }

        function toggleChat() {
            if (chatOpen) {
                closeChatWindow();
            } else {
                openChat();
            }
        }

        function openChat() {
            const chatWindow = document.getElementById('chatWindow');
            const chatToggle = document.getElementById('chatToggle');
            const notificationDot = document.getElementById('notificationDot');
            
            chatWindow.classList.remove('hidden');
            chatToggle.style.transform = 'scale(0.8)';
            chatToggle.style.opacity = '0.7';
            if (notificationDot) {
                notificationDot.style.display = 'none';
            }
            chatOpen = true;
            
            // Clear auto-open timer
            if (autoOpenTimer) {
                clearTimeout(autoOpenTimer);
            }
            
            // Focus on input
            setTimeout(() => {
                document.getElementById('chatInput').focus();
            }, 100);
        }

        function closeChatWindow() {
            const chatWindow = document.getElementById('chatWindow');
            const chatToggle = document.getElementById('chatToggle');
            const notificationDot = document.getElementById('notificationDot');
            
            chatWindow.classList.add('hidden');
            chatToggle.style.transform = 'scale(1)';
            chatToggle.style.opacity = '1';
            if (notificationDot) {
                notificationDot.style.display = 'flex';
            }
            chatOpen = false;
        }

        function addMessage(sender, message, isTyping = false) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            
            if (isTyping) {
                messageDiv.className = 'chat-message flex justify-start';
                messageDiv.innerHTML = `
                    <div class="flex items-end space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-robot text-sm text-white"></i>
                        </div>
                        <div class="bg-white rounded-2xl rounded-bl-md px-5 py-3 max-w-xs shadow-lg border border-gray-100">
                            <div class="typing-indicator">
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (sender === 'bot') {
                messageDiv.className = 'chat-message flex justify-start';
                messageDiv.innerHTML = `
                    <div class="flex items-end space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-robot text-sm text-white"></i>
                        </div>
                        <div class="bg-white rounded-2xl rounded-bl-md px-5 py-3 max-w-xs shadow-lg border border-gray-100">
                            <p class="text-sm text-gray-800 leading-relaxed">${message}</p>
                            <div class="text-xs text-gray-400 mt-1">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                        </div>
                    </div>
                `;
            } else {
                messageDiv.className = 'chat-message flex justify-end';
                messageDiv.innerHTML = `
                    <div class="flex items-end space-x-3">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl rounded-br-md px-5 py-3 max-w-xs shadow-lg">
                            <p class="text-sm text-white leading-relaxed">${message}</p>
                            <div class="text-xs text-blue-100 mt-1">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                        </div>
                        <div class="w-8 h-8 bg-gradient-to-r from-gray-400 to-gray-500 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-sm text-white"></i>
                        </div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            return messageDiv;
        }

        function sendMessage() {
            const chatInput = document.getElementById('chatInput');
            const message = chatInput.value.trim();
            
            if (message === '') return;
            
            // Add user message
            addMessage('user', message);
            chatInput.value = '';
            
            // Show typing indicator
            const typingMessage = addMessage('bot', '', true);
            
            // Simulate bot response
            setTimeout(() => {
                typingMessage.remove();
                const response = getBotResponse(message);
                addMessage('bot', response);
            }, 1500);
        }

        function addQuickOptions() {
            const chatMessages = document.getElementById('chatMessages');
            const optionsDiv = document.createElement('div');
            optionsDiv.className = 'chat-message flex justify-start';
            optionsDiv.innerHTML = `
                <div class="flex items-end space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-robot text-sm text-white"></i>
                    </div>
                    <div class="bg-white rounded-2xl rounded-bl-md px-5 py-3 max-w-xs shadow-lg border border-gray-100">
                        <p class="text-sm text-gray-800 mb-3">Quick options:</p>
                        <div class="space-y-2">
                            <button onclick="handleQuickOption('order')" class="w-full text-left px-3 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg text-sm text-blue-700 transition-colors">
                                📦 Where is my order?
                            </button>
                            <button onclick="handleQuickOption('menu')" class="w-full text-left px-3 py-2 bg-green-50 hover:bg-green-100 rounded-lg text-sm text-green-700 transition-colors">
                                🍽️ Browse restaurants
                            </button>
                            <button onclick="handleQuickOption('help')" class="w-full text-left px-3 py-2 bg-purple-50 hover:bg-purple-100 rounded-lg text-sm text-purple-700 transition-colors">
                                ❓ Need help?
                            </button>
                        </div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(optionsDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function handleQuickOption(option) {
            if (option === 'order') {
                addMessage('user', 'Where is my order?');
                setTimeout(() => {
                    addMessage('bot', 'I\'d be happy to help you track your order! 📦\n\nPlease provide your order number so I can check the status for you.');
                }, 1000);
            } else if (option === 'menu') {
                addMessage('user', 'Browse restaurants');
                setTimeout(() => {
                    addMessage('bot', 'Great! 🍽️ We have 3 amazing restaurants available:\n\n• Delawa - 45 min delivery\n• Gather Us - 30 min delivery\n• Tant Bakiza - 30 min delivery\n\nClick on any restaurant to view their menu and place an order!');
                }, 1000);
            } else if (option === 'help') {
                addMessage('user', 'Need help?');
                setTimeout(() => {
                    addMessage('bot', 'I\'m here to help! 🤝 I can assist you with:\n\n• 📦 Order tracking\n• 🍽️ Restaurant recommendations\n• 🚚 Delivery information\n• 💰 Pricing details\n• 🛒 Placing orders\n\nWhat would you like to know?');
                }, 1000);
            }
        }

        function getBotResponse(message) {
            const lowerMessage = message.toLowerCase();
            messageCount++;
            
            // Order tracking
            if (lowerMessage.includes('order') && (lowerMessage.includes('where') || lowerMessage.includes('track') || lowerMessage.includes('status'))) {
                return 'I\'d be happy to help you track your order! 📦\n\nPlease provide your order number so I can check the status for you.';
            }
            
            // Check if message is a number (order ID)
            if (/^\d+$/.test(message.trim())) {
                return checkOrderStatus(message.trim());
            }
            
            // Greeting responses
            if (lowerMessage.includes('hello') || lowerMessage.includes('hi') || lowerMessage.includes('hey')) {
                return 'Hello! 😊 I\'m here to help you discover amazing restaurants. What type of food are you craving today?';
            }
            
            // Food recommendations
            if (lowerMessage.includes('pizza') || lowerMessage.includes('italian')) {
                return 'Great choice! 🍕 We have amazing Italian restaurants. Check out our pizza places - they have the best wood-fired pizzas in town!';
            }
            
            if (lowerMessage.includes('burger') || lowerMessage.includes('american')) {
                return 'Burgers are always a good idea! 🍔 We have several burger joints with juicy, delicious options. Would you like me to show you our top-rated burger places?';
            }
            
            if (lowerMessage.includes('arabic') || lowerMessage.includes('middle eastern') || lowerMessage.includes('shawarma')) {
                return 'Perfect! 🥙 We have excellent Middle Eastern restaurants with authentic shawarma, hummus, and traditional dishes. Tant Bakiza is one of our favorites!';
            }
            
            if (lowerMessage.includes('asian') || lowerMessage.includes('chinese') || lowerMessage.includes('japanese')) {
                return 'Asian cuisine is fantastic! 🍜 We have great options for Chinese, Japanese, and other Asian dishes. What specific type are you interested in?';
            }
            
            // Restaurant questions
            if (lowerMessage.includes('restaurant') || lowerMessage.includes('place') || lowerMessage.includes('eat')) {
                return 'We have 3 amazing restaurants available right now! 🍽️ Each offers unique flavors and great service. Would you like to know more about any specific restaurant?';
            }
            
            if (lowerMessage.includes('delivery') || lowerMessage.includes('time')) {
                return 'Our restaurants offer fast delivery! 🚚 Most orders are delivered within 30-45 minutes. You can track your order in real-time once you place it.';
            }
            
            if (lowerMessage.includes('price') || lowerMessage.includes('cost') || lowerMessage.includes('expensive')) {
                return 'We have restaurants for every budget! 💰 From affordable quick bites to premium dining experiences. All our restaurants offer great value for money.';
            }
            
            // Help and support
            if (lowerMessage.includes('help') || lowerMessage.includes('support')) {
                return 'I\'m here to help! 🤝 I can assist you with:\n• Finding restaurants\n• Food recommendations\n• Delivery information\n• Order support\n\nWhat would you like to know?';
            }
            
            if (lowerMessage.includes('order') || lowerMessage.includes('buy') || lowerMessage.includes('purchase')) {
                return 'Great! 🛒 To place an order:\n1. Choose a restaurant from our list\n2. Browse their menu\n3. Add items to your cart\n4. Complete your order\n\nWould you like me to guide you through any specific restaurant?';
            }
            
            // Default responses
            const defaultResponses = [
                'That\'s interesting! 🤔 I\'d love to help you find the perfect restaurant. What type of cuisine are you in the mood for?',
                'I understand! 😊 We have great options for that. Have you tried browsing our restaurant list? Each one has something special to offer.',
                'Thanks for sharing! 💭 Let me help you discover some amazing food options. What\'s your favorite type of cuisine?',
                'I\'m here to make your dining experience amazing! ✨ What can I help you find today?'
            ];
            
            return defaultResponses[messageCount % defaultResponses.length];
        }

        async function checkOrderStatus(orderId) {
            try {
                const response = await fetch(`/api/order/${orderId}`);
                const data = await response.json();
                
                if (!data.success) {
                    return `Sorry, I couldn't find an order with number ${orderId}. 😔\n\nPlease double-check your order number and try again.`;
                }
                
                const order = data.order;
                let statusMessage = '';
                let statusEmoji = '';
                
                // Status messages
                switch(order.status) {
                    case 'pending':
                        statusMessage = 'Your order is being prepared';
                        statusEmoji = '⏳';
                        break;
                    case 'confirmed':
                        statusMessage = 'Your order has been confirmed';
                        statusEmoji = '✅';
                        break;
                    case 'preparing':
                        statusMessage = 'Your order is being prepared';
                        statusEmoji = '👨‍🍳';
                        break;
                    case 'ready':
                        statusMessage = 'Your order is ready for delivery';
                        statusEmoji = '🚚';
                        break;
                    case 'delivered':
                        statusMessage = 'Your order has been delivered';
                        statusEmoji = '🎉';
                        break;
                    case 'cancelled':
                        statusMessage = 'Your order has been cancelled';
                        statusEmoji = '❌';
                        break;
                    default:
                        statusMessage = 'Your order status is being updated';
                        statusEmoji = '📋';
                }
                
                // Build order details
                let orderDetails = `📦 **Order #${order.id}**\n`;
                orderDetails += `👤 Customer: ${order.full_name}\n`;
                orderDetails += `🏪 Restaurant: ${order.restaurant}\n`;
                orderDetails += `📅 Order Date: ${new Date(order.created_at).toLocaleDateString()}\n`;
                orderDetails += `💰 Total: ${order.total.toFixed(2)} رس\n\n`;
                
                // Add items
                orderDetails += `🛒 **Order Items:**\n`;
                order.cart_items.forEach((item, index) => {
                    orderDetails += `${index + 1}. ${item.name} x${item.quantity} - ${(item.price * item.quantity).toFixed(2)} رس\n`;
                });
                
                orderDetails += `\n${statusEmoji} **Status:** ${statusMessage}\n`;
                
                // Add status-specific message
                if (order.status === 'pending' || order.status === 'preparing') {
                    orderDetails += `\n⏰ Your order is under preparation and will be delivered as soon as possible!`;
                } else if (order.status === 'ready') {
                    orderDetails += `\n🚚 Your order is ready and on its way to you!`;
                } else if (order.status === 'delivered') {
                    orderDetails += `\n🎉 Enjoy your meal! Thank you for choosing AdvFood!`;
                }
                
                return orderDetails;
                
            } catch (error) {
                console.error('Error checking order status:', error);
                return `Sorry, I'm having trouble checking your order right now. 😔\n\nPlease try again in a few moments or contact our support team.`;
            }
        }
    </script>

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
