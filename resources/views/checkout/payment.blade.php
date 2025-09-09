<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Details - Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .form-card:hover {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
        .form-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .pay-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }
        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .payment-method {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        .payment-method.selected {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <button onclick="goBack()" class="inline-flex items-center gap-2 back-button rounded-full px-4 py-2 text-white hover:text-white">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </button>
            </div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center mb-6 shadow-lg">
                    <i class="fas fa-credit-card text-3xl text-green-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Payment Details</h1>
                <p class="text-white/80 text-lg">Complete your payment</p>
            </div>

            <!-- Order Summary -->
            <div class="form-card rounded-2xl p-6 mb-6">
                <h3 class="text-white text-lg font-semibold mb-4">
                    <i class="fas fa-receipt mr-2"></i>Order Summary
                </h3>
                <div id="orderSummary" class="space-y-2 text-white/80">
                    <!-- Order items will be displayed here -->
                </div>
                <div class="border-t border-white/20 pt-4 mt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-white text-lg font-semibold">Total:</span>
                        <span id="orderTotal" class="text-white text-xl font-bold">0.00 رس</span>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="form-card rounded-2xl p-6">
                <form id="paymentForm" onsubmit="processPayment(event)">
                    <!-- Payment Method -->
                    <div class="mb-6">
                        <label class="block text-white text-sm font-medium mb-4">
                            <i class="fas fa-credit-card mr-2"></i>Payment Method
                        </label>
                        <div class="space-y-3">
                            <div class="payment-method rounded-lg p-4 cursor-pointer" onclick="selectPaymentMethod('card')">
                                <div class="flex items-center">
                                    <input type="radio" name="payment_method" value="card" class="mr-3" checked>
                                    <i class="fas fa-credit-card text-white mr-3"></i>
                                    <span class="text-white">Credit/Debit Card</span>
                                </div>
                            </div>
                            <div class="payment-method rounded-lg p-4 cursor-pointer" onclick="selectPaymentMethod('cash')">
                                <div class="flex items-center">
                                    <input type="radio" name="payment_method" value="cash" class="mr-3">
                                    <i class="fas fa-money-bill-wave text-white mr-3"></i>
                                    <span class="text-white">Cash on Delivery</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Details (shown when card is selected) -->
                    <div id="cardDetails" class="space-y-4">
                        <!-- Card Number -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-credit-card mr-2"></i>Card Number
                            </label>
                            <input type="text"
                                   name="card_number"
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="1234 5678 9012 3456"
                                   maxlength="19">
                        </div>

                        <!-- Expiry Date and CVV -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-white text-sm font-medium mb-2">
                                    <i class="fas fa-calendar mr-2"></i>Expiry Date
                                </label>
                                <input type="text"
                                       name="expiry_date"
                                       class="form-input w-full px-4 py-3 rounded-lg"
                                       placeholder="MM/YY"
                                       maxlength="5">
                            </div>
                            <div>
                                <label class="block text-white text-sm font-medium mb-2">
                                    <i class="fas fa-lock mr-2"></i>CVV
                                </label>
                                <input type="text"
                                       name="cvv"
                                       class="form-input w-full px-4 py-3 rounded-lg"
                                       placeholder="123"
                                       maxlength="4">
                            </div>
                        </div>

                        <!-- Cardholder Name -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-user mr-2"></i>Cardholder Name
                            </label>
                            <input type="text"
                                   name="cardholder_name"
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter cardholder name">
                        </div>
                    </div>

                    <!-- Pay Now Button -->
                    <div class="mt-8">
                        <button type="submit" class="pay-button w-full text-white py-3 rounded-lg font-semibold text-lg">
                            <i class="fas fa-lock mr-2"></i>
                            Pay Now
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <div class="text-white/60 text-sm">
                    <p>Powered by <span class="font-semibold">AdvFood</span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedPaymentMethod = 'card';

        function goBack() {
            window.history.back();
        }

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;

            // Update radio buttons
            document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                radio.checked = radio.value === method;
            });

            // Update visual selection
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Show/hide card details
            const cardDetails = document.getElementById('cardDetails');
            if (method === 'card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        }

        function processPayment(event) {
            event.preventDefault();

            if (selectedPaymentMethod === 'card') {
                // Validate card details
                const cardNumber = document.querySelector('input[name="card_number"]').value;
                const expiryDate = document.querySelector('input[name="expiry_date"]').value;
                const cvv = document.querySelector('input[name="cvv"]').value;
                const cardholderName = document.querySelector('input[name="cardholder_name"]').value;

                if (!cardNumber || !expiryDate || !cvv || !cardholderName) {
                    alert('Please fill in all card details');
                    return;
                }
            }

            // Get customer data and cart data
            const customerData = JSON.parse(sessionStorage.getItem('customerData') || '{}');
            const cartData = JSON.parse(sessionStorage.getItem('cartData') || '[]');
            const cartTotal = parseFloat(sessionStorage.getItem('cartTotal') || '0');

            if (cartData.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Show processing message
            alert('Processing payment...');

            // Save order to database
            saveOrderToDatabase(customerData, cartData, cartTotal);
        }

        function saveOrderToDatabase(customerData, cartData, cartTotal) {
            // Get restaurant ID from URL or session
            const restaurantId = getRestaurantIdFromSession();

            const orderData = {
                restaurant_id: restaurantId,
                full_name: customerData.full_name,
                phone_number: customerData.phone_number,
                building_no: customerData.building_no,
                floor: customerData.floor,
                apartment_number: customerData.apartment_number,
                street: customerData.street,
                note: customerData.note || '',
                total: cartTotal,
                cart_items: cartData,
                _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };

            fetch('{{ route("checkout.save-order") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment successful!\n\nOrder #' + data.order_id + ' has been placed.\n\nThank you for your order!');

                    // Clear session data
                    sessionStorage.removeItem('customerData');
                    sessionStorage.removeItem('cartData');
                    sessionStorage.removeItem('cartTotal');

                    // Redirect to home
                    window.location.href = '/rest-link';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your order. Please try again.');
            });
        }

        function getRestaurantIdFromSession() {
            // Get restaurant ID from sessionStorage
            return sessionStorage.getItem('restaurantId') || 14;
        }

        // Format card number input
        document.addEventListener('DOMContentLoaded', function() {
            const cardNumberInput = document.querySelector('input[name="card_number"]');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    e.target.value = formattedValue;
                });
            }

            // Format expiry date input
            const expiryInput = document.querySelector('input[name="expiry_date"]');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            }

            // Load order summary from sessionStorage
            loadOrderSummary();
        });

        function loadOrderSummary() {
            const orderSummary = document.getElementById('orderSummary');
            const orderTotal = document.getElementById('orderTotal');

            // Load cart data from sessionStorage
            const cartData = JSON.parse(sessionStorage.getItem('cartData') || '[]');
            const cartTotal = parseFloat(sessionStorage.getItem('cartTotal') || '0');

            if (cartData.length === 0) {
                orderSummary.innerHTML = '<p class="text-white/70 text-center py-4">No items in cart</p>';
                orderTotal.textContent = '0.00 رس';
                return;
            }

            let total = 0;
            orderSummary.innerHTML = cartData.map(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                return `
                    <div class="flex justify-between items-center">
                        <span>${item.name} x${item.quantity}</span>
                        <span>${itemTotal.toFixed(2)} رس</span>
                    </div>
                `;
            }).join('');

            orderTotal.textContent = total.toFixed(2) + ' رس';
        }
    </script>
</body>
</html>
