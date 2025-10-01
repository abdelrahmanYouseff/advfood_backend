<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details - Checkout</title>
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
        .continue-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .continue-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .map-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .map-container {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            height: 70%;
            max-height: 500px;
            position: relative;
            overflow: hidden;
        }
        .map-header {
            background: #667eea;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .map-content {
            height: calc(100% - 120px);
            position: relative;
        }
        .map-actions {
            background: white;
            padding: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
            height: 60px;
            align-items: center;
        }
        .map-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .map-btn-cancel {
            background: #e5e7eb;
            color: #374151;
        }
        .map-btn-confirm {
            background: #10b981;
            color: white;
        }
        .map-btn:hover {
            transform: translateY(-1px);
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
                    <i class="fas fa-user text-3xl text-purple-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Customer Details</h1>
                <p class="text-white/80 text-lg">Please provide your information</p>
            </div>

            <!-- Form -->
            <div class="form-card rounded-2xl p-6">
                <form id="customerForm" onsubmit="submitForm(event)">
                    <div class="space-y-4">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-user mr-2"></i>Full Name
                            </label>
                            <input type="text"
                                   name="full_name"
                                   required
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter your full name">
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-phone mr-2"></i>Phone Number
                            </label>
                            <input type="tel"
                                   name="phone_number"
                                   required
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter your phone number">
                        </div>

                        <!-- Building No -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-building mr-2"></i>Building No
                            </label>
                            <input type="text"
                                   name="building_no"
                                   required
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter building number">
                        </div>

                        <!-- Floor -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-layer-group mr-2"></i>Floor
                            </label>
                            <input type="text"
                                   name="floor"
                                   required
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter floor number">
                        </div>

                        <!-- Apartment Number -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-home mr-2"></i>Apartment Number
                            </label>
                            <input type="text"
                                   name="apartment_number"
                                   required
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter apartment number">
                        </div>

                        <!-- Street -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-road mr-2"></i>Street
                            </label>
                            <input type="text"
                                   name="street"
                                   required
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="Enter street name">
                        </div>

                        <!-- Note -->
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">
                                <i class="fas fa-sticky-note mr-2"></i>Note (Optional)
                            </label>
                            <textarea name="note"
                                      rows="3"
                                      class="form-input w-full px-4 py-3 rounded-lg resize-none"
                                      placeholder="Any additional notes or special instructions..."></textarea>
                        </div>

                        <!-- Get Location Button -->
                        <div class="pt-4">
                            <button type="button"
                                    onclick="getCurrentLocation()"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 font-semibold">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Get My Location</span>
                            </button>
                            <p class="text-white/60 text-xs text-center mt-2">
                                Allow location access to auto-fill address fields
                            </p>
                        </div>
                    </div>

                    <!-- Continue Button -->
                    <div class="mt-8">
                        <button type="submit" class="continue-button w-full text-white py-3 rounded-lg font-semibold text-lg">
                            <i class="fas fa-arrow-right mr-2"></i>
                            Continue
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

    <!-- Map Popup -->
    <div id="mapPopup" class="map-popup" style="display: none;">
        <div class="map-container">
            <div class="map-header">
                <h3 class="text-lg font-semibold">Select Your Location</h3>
                <button onclick="closeMapPopup()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="map-content">
                <div id="map" style="width: 100%; height: 100%;"></div>
            </div>
            <div class="map-actions">
                <button onclick="closeMapPopup()" class="map-btn map-btn-cancel">Cancel</button>
                <button onclick="confirmLocation()" class="map-btn map-btn-confirm">Confirm Location</button>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        function goBack() {
            // Check if there's a previous page in history
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // If no history, redirect to restaurant menu or rest-link
                window.location.href = '/rest-link';
            }
        }

        function submitForm(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const customerData = Object.fromEntries(formData.entries());

            // Get cart data from sessionStorage
            const cartData = JSON.parse(sessionStorage.getItem('cartData') || '[]');
            const cartTotal = parseFloat(sessionStorage.getItem('cartTotal') || '0');
            const restaurantId = sessionStorage.getItem('restaurantId') || '1';

            if (cartData.length === 0 || cartTotal <= 0) {
                alert('عربة التسوق فارغة! الرجاء إضافة منتجات قبل المتابعة.');
                window.location.href = '/rest-link';
                return;
            }

            // Show loading message
            const submitButton = event.target.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>جاري التحميل...';

            // Prepare payment data
            const paymentData = {
                restaurant_id: restaurantId,
                full_name: customerData.full_name,
                phone_number: customerData.phone_number,
                building_no: customerData.building_no,
                floor: customerData.floor,
                apartment_number: customerData.apartment_number,
                street: customerData.street,
                note: customerData.note || '',
                total: cartTotal,
                cart_items: cartData
            };

            // Send request to initiate payment
            fetch('{{ route("checkout.initiate-payment") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(paymentData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.checkout_url) {
                    // Redirect to Noon payment page
                    window.location.href = data.checkout_url;
                } else {
                    alert('خطأ في إنشاء الدفع: ' + (data.message || 'حدث خطأ غير متوقع'));
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء معالجة طلبك. الرجاء المحاولة مرة أخرى.');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        }

        let map;
        let selectedMarker;
        let selectedLatLng;
        let userCurrentLocation;

        function getCurrentLocation() {
            // Show map popup
            document.getElementById('mapPopup').style.display = 'flex';

            // Initialize map after a short delay to ensure the popup is visible
            setTimeout(initializeMap, 100);
        }

        function initializeMap() {
            // Default center (Riyadh, Saudi Arabia)
            const defaultCenter = [24.7136, 46.6753];

            // Initialize map
            map = L.map('map').setView(defaultCenter, 13);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLatLng = [position.coords.latitude, position.coords.longitude];
                        userCurrentLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
                        map.setView(userLatLng, 16);

                        // Add marker for user's current location
                        const userMarker = L.marker(userLatLng, {
                            icon: L.divIcon({
                                className: 'user-location-marker',
                                html: '<div style="background: #3b82f6; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            })
                        }).addTo(map);

                        userMarker.bindPopup('Your current location').openPopup();
                    },
                    function(error) {
                        console.log('Could not get current location:', error);
                        userCurrentLocation = null;
                        // Map will stay at default center
                    }
                );
            } else {
                userCurrentLocation = null;
            }

            // Add click event to map
            map.on('click', function(e) {
                const latlng = e.latlng;

                // Remove previous marker
                if (selectedMarker) {
                    map.removeLayer(selectedMarker);
                }

                // Add new marker
                selectedMarker = L.marker(latlng, {
                    icon: L.divIcon({
                        className: 'selected-location-marker',
                        html: '<div style="background: #10b981; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    })
                }).addTo(map);

                selectedLatLng = latlng;

                // Show popup with coordinates
                selectedMarker.bindPopup(`
                    <div style="text-align: center;">
                        <strong>Selected Location</strong><br>
                        Lat: ${latlng.lat.toFixed(6)}<br>
                        Lng: ${latlng.lng.toFixed(6)}
                    </div>
                `).openPopup();
            });
        }

        function closeMapPopup() {
            document.getElementById('mapPopup').style.display = 'none';
            if (map) {
                map.remove();
                map = null;
            }
            selectedMarker = null;
            selectedLatLng = null;
        }

        function confirmLocation() {
            let locationToUse;

            if (selectedLatLng) {
                // Use selected location
                locationToUse = selectedLatLng;
                showSuccessMessage('Using selected location...');
            } else if (userCurrentLocation) {
                // Use current location if no location was selected
                locationToUse = userCurrentLocation;
                showSuccessMessage('Using your current location...');
            } else {
                alert('Please select a location on the map or allow location access.');
                return;
            }

            // Get address from location
            reverseGeocode(locationToUse.lat, locationToUse.lng);
            closeMapPopup();
        }

        function reverseGeocode(lat, lng) {
            // Using OpenStreetMap Nominatim API for reverse geocoding
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;

            // Show loading message
            showSuccessMessage('Getting address details...');

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.address) {
                        fillAddressFields(data.address);
                        showSuccessMessage('Location confirmed! Address fields have been auto-filled.');
                    } else {
                        alert('Could not get address details from location.');
                    }
                })
                .catch(error => {
                    console.error('Reverse geocoding error:', error);
                    alert('Error getting address details. Please enter manually.');
                });
        }

        function fillAddressFields(address) {
            // Fill street
            if (address.road || address.street) {
                document.querySelector('input[name="street"]').value = address.road || address.street || '';
            }

            // Fill building number (house number)
            if (address.house_number) {
                document.querySelector('input[name="building_no"]').value = address.house_number;
            }

            // For floor and apartment, we can't get this from geocoding
            // So we'll leave them empty for manual entry

            // Show a message about what was filled
            const filledFields = [];
            if (address.road || address.street) filledFields.push('Street');
            if (address.house_number) filledFields.push('Building Number');

            if (filledFields.length > 0) {
                showSuccessMessage(`Auto-filled: ${filledFields.join(', ')}. Please complete the remaining fields manually.`);
            }
        }

        function showSuccessMessage(message = 'Location detected! Address fields have been auto-filled.') {
            // Create a temporary success message
            const successDiv = document.createElement('div');
            successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            successDiv.innerHTML = `<i class="fas fa-check mr-2"></i>${message}`;
            document.body.appendChild(successDiv);

            // Remove after 3 seconds
            setTimeout(() => {
                successDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>
