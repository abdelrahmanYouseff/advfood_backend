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
        <div id="chatToggle" class="bg-blue-600 hover:bg-blue-700 text-white rounded-full w-16 h-16 flex items-center justify-center cursor-pointer shadow-lg transition-all duration-300 hover:scale-110">
            <i class="fas fa-comments text-xl"></i>
        </div>

        <!-- Chat Window -->
        <div id="chatWindow" class="hidden absolute bottom-20 right-0 w-80 h-96 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
            <!-- Chat Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-robot text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold">AdvFood Assistant</h3>
                        <p class="text-xs text-blue-100">Online now</p>
                    </div>
                </div>
                <button id="closeChat" class="text-white/80 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Chat Messages -->
            <div id="chatMessages" class="h-64 overflow-y-auto p-4 space-y-3">
                <!-- Messages will be added here -->
            </div>

            <!-- Chat Input -->
            <div class="border-t border-gray-200 p-4">
                <div class="flex items-center space-x-2">
                    <input type="text" id="chatInput" placeholder="Type your message..." 
                           class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button id="sendMessage" class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-blue-700 transition-colors">
                        <i class="fas fa-paper-plane text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .chat-message {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #9CA3AF;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
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

            // Add initial welcome message
            addMessage('bot', 'Hello! 👋 Welcome to AdvFood! I\'m here to help you find the perfect restaurant. How can I assist you today?');

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
            
            chatWindow.classList.remove('hidden');
            chatToggle.style.display = 'none';
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
            
            chatWindow.classList.add('hidden');
            chatToggle.style.display = 'flex';
            chatOpen = false;
        }

        function addMessage(sender, message, isTyping = false) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            
            if (isTyping) {
                messageDiv.className = 'chat-message flex justify-start';
                messageDiv.innerHTML = `
                    <div class="flex items-end space-x-2">
                        <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-xs text-white"></i>
                        </div>
                        <div class="bg-gray-100 rounded-2xl px-4 py-2 max-w-xs">
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
                    <div class="flex items-end space-x-2">
                        <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-xs text-white"></i>
                        </div>
                        <div class="bg-gray-100 rounded-2xl px-4 py-2 max-w-xs">
                            <p class="text-sm text-gray-800">${message}</p>
                        </div>
                    </div>
                `;
            } else {
                messageDiv.className = 'chat-message flex justify-end';
                messageDiv.innerHTML = `
                    <div class="flex items-end space-x-2">
                        <div class="bg-blue-600 rounded-2xl px-4 py-2 max-w-xs">
                            <p class="text-sm text-white">${message}</p>
                        </div>
                        <div class="w-6 h-6 bg-gray-400 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-xs text-white"></i>
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

        function getBotResponse(message) {
            const lowerMessage = message.toLowerCase();
            messageCount++;
            
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
