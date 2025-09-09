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
