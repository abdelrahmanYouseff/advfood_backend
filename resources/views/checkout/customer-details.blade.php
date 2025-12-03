<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيانات العميل - الدفع</title>
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
        .form-card {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .form-card:hover {
            border-color: #667eea;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.1);
        }
        .back-button {
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            color: #374151;
        }
        .back-button:hover {
            background: #e5e7eb;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        .form-input {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            color: #374151;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            background: #ffffff;
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-input::placeholder {
            color: #9ca3af;
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
        <div class="max-w-2xl mx-auto">
            <!-- Back Button -->
            <div class="mb-6">
                <button onclick="goBack()" class="inline-flex items-center gap-2 back-button rounded-full px-4 py-2">
                    <i class="fas fa-arrow-right back-arrow"></i>
                    <span id="back-text">رجوع</span>
                </button>
            </div>

            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto h-20 w-20 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas fa-user text-3xl text-white"></i>
                </div>
                <h1 id="page-title" class="text-3xl font-bold text-gray-800 mb-2">بيانات العميل</h1>
                <p id="page-subtitle" class="text-gray-600 text-lg">يرجى تقديم معلوماتك</p>
            </div>

            <!-- Form -->
            <div class="form-card rounded-2xl p-6">
                <form id="customerForm" onsubmit="submitForm(event)">
                    <div class="space-y-4">
                        <!-- Row 1: Full Name and Phone Number -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Full Name -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    <i class="fas fa-user ml-2"></i><span id="label-fullname">الاسم الكامل</span>
                                </label>
                                <input type="text"
                                       name="full_name"
                                       required
                                       class="form-input w-full px-4 py-2.5 rounded-lg"
                                       id="input-fullname"
                                       placeholder="أدخل اسمك الكامل">
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    <i class="fas fa-phone ml-2"></i><span id="label-phone">رقم الهاتف</span>
                                </label>
                                <input type="tel"
                                       name="phone_number"
                                       required
                                       class="form-input w-full px-4 py-2.5 rounded-lg"
                                       id="input-phone"
                                       placeholder="أدخل رقم هاتفك">
                            </div>
                        </div>

                        <!-- Row 2: Building No and Floor -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Building No -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    <i class="fas fa-building ml-2"></i><span id="label-building">رقم المبنى</span>
                                </label>
                                <input type="text"
                                       name="building_no"
                                       required
                                       class="form-input w-full px-4 py-2.5 rounded-lg"
                                       id="input-building"
                                       placeholder="أدخل رقم المبنى">
                            </div>

                            <!-- Floor -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    <i class="fas fa-layer-group ml-2"></i><span id="label-floor">الطابق</span>
                                </label>
                                <input type="text"
                                       name="floor"
                                       required
                                       class="form-input w-full px-4 py-2.5 rounded-lg"
                                       id="input-floor"
                                       placeholder="أدخل رقم الطابق">
                            </div>
                        </div>

                        <!-- Row 3: Apartment Number and Street -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Apartment Number -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    <i class="fas fa-home ml-2"></i><span id="label-apartment">رقم الشقة</span>
                                </label>
                                <input type="text"
                                       name="apartment_number"
                                       required
                                       class="form-input w-full px-4 py-2.5 rounded-lg"
                                       id="input-apartment"
                                       placeholder="أدخل رقم الشقة">
                            </div>

                            <!-- Street -->
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">
                                    <i class="fas fa-road ml-2"></i><span id="label-street">الشارع</span>
                                </label>
                                <input type="text"
                                       name="street"
                                       required
                                       class="form-input w-full px-4 py-2.5 rounded-lg"
                                       id="input-street"
                                       placeholder="أدخل اسم الشارع">
                            </div>
                        </div>

                        <!-- Note -->
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                <i class="fas fa-sticky-note ml-2"></i><span id="label-note">ملاحظات (اختياري)</span>
                            </label>
                            <textarea name="note"
                                      rows="2"
                                      class="form-input w-full px-4 py-2.5 rounded-lg resize-none"
                                      id="input-note"
                                      placeholder="أي ملاحظات أو تعليمات خاصة..."></textarea>
                        </div>

                        <!-- Get Location Button - سيتم التوجيه تلقائياً بعد تأكيد الموقع -->
                        <div class="pt-2">
                            <button type="button"
                                    onclick="getCurrentLocation()"
                                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2.5 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 font-semibold">
                                    <i class="fas fa-map-marker-alt"></i>
                                <span id="location-btn-text">احصل على موقعي</span>
                            </button>
                            <p id="location-hint" class="text-gray-500 text-xs text-center mt-2">
                                اختر موقعك لتعبئة البيانات تلقائياً والمتابعة للدفع
                            </p>
                        </div>
                    </div>

                    <!-- Continue Button - مخفي الآن، سيتم التوجيه تلقائياً بعد اختيار الموقع -->
                    <div class="mt-6" style="display: none;">
                        <button type="submit" class="continue-button w-full text-white py-3 rounded-lg font-semibold text-lg">
                            <i class="fas fa-arrow-left ml-2 continue-arrow"></i>
                            <span id="continue-btn-text">متابعة</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <div class="text-gray-500 text-sm">
                    <p><span id="powered-by-text">مدعوم بواسطة</span> <span class="font-semibold" style="color: #cf4823;">AdvFood</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Popup - يظهر أثناء تحضير رابط الدفع -->
    <div id="loadingPopup" class="fixed inset-0 bg-black bg-opacity-60 z-[9999] hidden items-center justify-center" style="display: none;">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl transform transition-all">
            <div class="text-center">
                <div class="mb-6 flex justify-center">
                    <div class="inline-block animate-spin rounded-full h-20 w-20 border-t-4 border-b-4 border-purple-600"></div>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">جاري تحضير رابط الدفع...</h3>
                <p class="text-gray-600 text-base">يرجى الانتظار بينما نجهز رابط الدفع الخاص بك</p>
                <div class="mt-4 flex justify-center space-x-1">
                    <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                    <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                    <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Out-of-Range Popup (خارج نطاق التوصيل) -->
    <div id="distancePopup" class="fixed inset-0 bg-black/60 z-[9999] hidden items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-red-600 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>خارج نطاق التوصيل</span>
                </h3>
                <button type="button" onclick="closeDistancePopup()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <p id="distancePopupMessage" class="text-gray-700 leading-relaxed mb-4">
                عذراً، موقعك الحالي خارج نطاق التوصيل المسموح به.
            </p>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 mb-4 text-sm text-gray-600">
                <ul class="list-disc pr-5 space-y-1">
                    <li>نطاق التوصيل الأقصى هو 14 كم من موقع المطعم.</li>
                    <li>يمكنك اختيار موقع أقرب أو التواصل معنا للاستفسار.</li>
                </ul>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDistancePopup()" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold">
                    حسنًا
                </button>
            </div>
        </div>
    </div>

    <!-- Map Popup -->
    <div id="mapPopup" class="map-popup" style="display: none;">
        <div class="map-container">
            <div class="map-header">
                <h3 id="map-title" class="text-lg font-semibold">اختر موقعك</h3>
                <button onclick="closeMapPopup()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="map-content">
                <div id="map" style="width: 100%; height: 100%;"></div>
            </div>
            <div class="map-actions">
                <button onclick="closeMapPopup()" id="map-cancel-btn" class="map-btn map-btn-cancel">إلغاء</button>
                <button onclick="confirmLocation()" id="map-confirm-btn" class="map-btn map-btn-confirm">تأكيد الموقع</button>
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

        function submitFormAutomatically() {
            // Get form data
            const form = document.getElementById('customerForm');
            const formData = new FormData(form);
            const customerData = Object.fromEntries(formData.entries());

            // Call the same submit logic
            submitFormData(customerData);
        }

        function submitForm(event) {
            if (event) {
                event.preventDefault();
            }

            const form = event ? event.target : document.getElementById('customerForm');
            const formData = new FormData(form);
            const customerData = Object.fromEntries(formData.entries());

            submitFormData(customerData);
        }

        function submitFormData(customerData) {
            // Get cart data from sessionStorage
            const cartData = JSON.parse(sessionStorage.getItem('cartData') || '[]');
            const cartTotal = parseFloat(sessionStorage.getItem('cartTotal') || '0');
            const restaurantId = sessionStorage.getItem('restaurantId') || '1';

            if (cartData.length === 0 || cartTotal <= 0) {
                alert('عربة التسوق فارغة! الرجاء إضافة منتجات قبل المتابعة.');
                window.location.href = '/rest-link';
                return;
            }

            // Validate required fields
            if (!customerData.full_name || !customerData.phone_number || !customerData.building_no ||
                !customerData.floor || !customerData.apartment_number || !customerData.street) {
                alert('يرجى ملء جميع الحقول المطلوبة');
                return;
            }

            // ✅ تحقق من المسافة قبل المتابعة للدفع (حماية إضافية)
            if (savedCustomerLatitude !== null && savedCustomerLongitude !== null) {
                const distanceKm = calculateDistanceKm(
                    savedCustomerLatitude,
                    savedCustomerLongitude,
                    DELIVERY_CENTER_LAT,
                    DELIVERY_CENTER_LNG
                );

                if (distanceKm > MAX_DELIVERY_DISTANCE_KM) {
                    showDistancePopup(distanceKm);
                    return;
                }
            }

            // Show loading popup
            showLoadingPopup();

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
                cart_items: cartData,
                customer_latitude: savedCustomerLatitude || null,
                customer_longitude: savedCustomerLongitude || null
            };

            // Debug: Log the coordinates being sent
            console.log('📍 Customer coordinates being sent:', {
                latitude: savedCustomerLatitude,
                longitude: savedCustomerLongitude,
                hasCoordinates: savedCustomerLatitude !== null && savedCustomerLongitude !== null
            });

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
                hideLoadingPopup();
                if (data.success && data.checkout_url) {
                    // Show success message before redirect
                    showSuccessMessage('تم تحضير رابط الدفع بنجاح! جاري التوجيه...');
                    // Redirect to Noon payment page after a short delay
                    setTimeout(() => {
                        window.location.href = data.checkout_url;
                    }, 500);
                } else {
                    alert('خطأ في إنشاء الدفع: ' + (data.message || 'حدث خطأ غير متوقع'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                hideLoadingPopup();
                alert('حدث خطأ أثناء معالجة طلبك. الرجاء المحاولة مرة أخرى.');
            });
        }

        let map;
        let selectedMarker;
        let selectedLatLng;
        let userCurrentLocation;
        let savedCustomerLatitude = null;
        let savedCustomerLongitude = null;

        // مركز التوصيل (إحداثيات GatherUs من Google Maps)
        // https://www.google.com/maps/place/GatherUs... -> lat: 24.7560922, lng: 46.6749848
        const DELIVERY_CENTER_LAT = 24.7560922;
        const DELIVERY_CENTER_LNG = 46.6749848;
        const MAX_DELIVERY_DISTANCE_KM = 14;  // أقصى مسافة توصيل بالكيلومتر

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
                showSuccessMessage('تم اختيار الموقع...');
            } else if (userCurrentLocation) {
                // Use current location if no location was selected
                locationToUse = userCurrentLocation;
                showSuccessMessage('جارٍ استخدام موقعك الحالي...');
            } else {
                alert('يرجى اختيار موقع على الخريطة أو السماح بالوصول إلى الموقع.');
                return;
            }

            // Save latitude and longitude for later use
            savedCustomerLatitude = locationToUse.lat;
            savedCustomerLongitude = locationToUse.lng;

            // Debug: Log saved coordinates
            console.log('✅ Location saved:', {
                latitude: savedCustomerLatitude,
                longitude: savedCustomerLongitude
            });

            // تحقق من المسافة من مركز التوصيل قبل إكمال العملية
            const distanceKm = calculateDistanceKm(
                savedCustomerLatitude,
                savedCustomerLongitude,
                DELIVERY_CENTER_LAT,
                DELIVERY_CENTER_LNG
            );

            if (distanceKm > MAX_DELIVERY_DISTANCE_KM) {
                showDistancePopup(distanceKm);
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
            showSuccessMessage('جارٍ الحصول على تفاصيل العنوان...');

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.address) {
                        fillAddressFields(data.address);
                    } else {
                        alert('تعذر الحصول على تفاصيل العنوان من الموقع.');
                    }
                })
                .catch(error => {
                    console.error('Reverse geocoding error:', error);
                    alert('حدث خطأ في الحصول على تفاصيل العنوان. يرجى إدخال البيانات يدوياً.');
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
            // Set default values if not available
            if (!document.querySelector('input[name="floor"]').value) {
                document.querySelector('input[name="floor"]').value = '1';
            }
            if (!document.querySelector('input[name="apartment_number"]').value) {
                document.querySelector('input[name="apartment_number"]').value = '1';
            }

            // Check if required fields are filled
            const fullName = document.querySelector('input[name="full_name"]').value;
            const phoneNumber = document.querySelector('input[name="phone_number"]').value;
            const buildingNo = document.querySelector('input[name="building_no"]').value;
            const floor = document.querySelector('input[name="floor"]').value;
            const apartment = document.querySelector('input[name="apartment_number"]').value;
            const street = document.querySelector('input[name="street"]').value;

            // If name and phone are not filled, prompt user to fill them
            if (!fullName || !phoneNumber) {
                showSuccessMessage('يرجى إدخال الاسم الكامل ورقم الهاتف');
                // Focus on name field if empty
                if (!fullName) {
                    document.querySelector('input[name="full_name"]').focus();
                } else if (!phoneNumber) {
                    document.querySelector('input[name="phone_number"]').focus();
                }
                return;
            }

            // Check if all required address fields are filled
            if (buildingNo && floor && apartment && street) {
                // All fields are filled, auto-submit after a short delay
                showSuccessMessage('تم ملء البيانات بنجاح! جاري التوجيه للدفع...');
                setTimeout(() => {
                    submitFormAutomatically();
                }, 1500);
            } else {
                showSuccessMessage('تم ملء بعض البيانات. يرجى إكمال باقي الحقول');
            }
        }

        function showLoadingPopup() {
            const popup = document.getElementById('loadingPopup');
            if (popup) {
                popup.style.display = 'flex';
                popup.classList.remove('hidden');
                // Prevent body scroll when popup is shown
                document.body.style.overflow = 'hidden';
            }
        }

        function hideLoadingPopup() {
            const popup = document.getElementById('loadingPopup');
            if (popup) {
                popup.style.display = 'none';
                popup.classList.add('hidden');
                // Restore body scroll
                document.body.style.overflow = '';
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

        // دالة حساب المسافة بين نقطتين (Haversine formula) بالكيلومتر
        function calculateDistanceKm(lat1, lon1, lat2, lon2) {
            const toRad = (value) => (value * Math.PI) / 180;
            const R = 6371; // نصف قطر الأرض بالكيلومتر

            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);

            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);

            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function showDistancePopup(distanceKm) {
            const popup = document.getElementById('distancePopup');
            const messageEl = document.getElementById('distancePopupMessage');

            if (messageEl && typeof distanceKm === 'number' && !isNaN(distanceKm)) {
                const rounded = distanceKm.toFixed(1);
                messageEl.textContent = `عذراً، موقعك الحالي يبعد تقريباً ${rounded} كم عن المطعم، وهو خارج نطاق التوصيل المسموح به (${MAX_DELIVERY_DISTANCE_KM} كم كحد أقصى).`;
            }

            if (popup) {
                popup.classList.remove('hidden');
                popup.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDistancePopup() {
            const popup = document.getElementById('distancePopup');
            if (popup) {
                popup.classList.add('hidden');
                popup.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        // Translations
        const translations = {
            en: {
                backText: 'Back',
                pageTitle: 'Customer Details',
                pageSubtitle: 'Please provide your information',
                labelFullname: 'Full Name',
                labelPhone: 'Phone Number',
                labelBuilding: 'Building No',
                labelFloor: 'Floor',
                labelApartment: 'Apartment Number',
                labelStreet: 'Street',
                labelNote: 'Note (Optional)',
                placeholderFullname: 'Enter your full name',
                placeholderPhone: 'Enter your phone number',
                placeholderBuilding: 'Enter building number',
                placeholderFloor: 'Enter floor number',
                placeholderApartment: 'Enter apartment number',
                placeholderStreet: 'Enter street name',
                placeholderNote: 'Any additional notes or special instructions...',
                locationBtnText: 'Get My Location',
                locationHint: 'Allow location access to auto-fill address fields',
                continueBtnText: 'Continue',
                poweredBy: 'Powered by',
                mapTitle: 'Select Your Location',
                mapCancel: 'Cancel',
                mapConfirm: 'Confirm Location'
            },
            ar: {
                backText: 'رجوع',
                pageTitle: 'بيانات العميل',
                pageSubtitle: 'يرجى تقديم معلوماتك',
                labelFullname: 'الاسم الكامل',
                labelPhone: 'رقم الهاتف',
                labelBuilding: 'رقم المبنى',
                labelFloor: 'الطابق',
                labelApartment: 'رقم الشقة',
                labelStreet: 'الشارع',
                labelNote: 'ملاحظات (اختياري)',
                placeholderFullname: 'أدخل اسمك الكامل',
                placeholderPhone: 'أدخل رقم هاتفك',
                placeholderBuilding: 'أدخل رقم المبنى',
                placeholderFloor: 'أدخل رقم الطابق',
                placeholderApartment: 'أدخل رقم الشقة',
                placeholderStreet: 'أدخل اسم الشارع',
                placeholderNote: 'أي ملاحظات أو تعليمات خاصة...',
                locationBtnText: 'احصل على موقعي',
                locationHint: 'السماح بالوصول إلى الموقع لملء حقول العنوان تلقائياً',
                continueBtnText: 'متابعة',
                poweredBy: 'مدعوم بواسطة',
                mapTitle: 'اختر موقعك',
                mapCancel: 'إلغاء',
                mapConfirm: 'تأكيد الموقع'
            }
        };

        function applyTranslations(lang) {
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
            const backText = document.getElementById('back-text');
            const pageTitle = document.getElementById('page-title');
            const pageSubtitle = document.getElementById('page-subtitle');
            const labelFullname = document.getElementById('label-fullname');
            const labelPhone = document.getElementById('label-phone');
            const labelBuilding = document.getElementById('label-building');
            const labelFloor = document.getElementById('label-floor');
            const labelApartment = document.getElementById('label-apartment');
            const labelStreet = document.getElementById('label-street');
            const labelNote = document.getElementById('label-note');
            const locationBtnText = document.getElementById('location-btn-text');
            const locationHint = document.getElementById('location-hint');
            const continueBtnText = document.getElementById('continue-btn-text');
            const poweredByText = document.getElementById('powered-by-text');

            if (backText) backText.textContent = t.backText;
            if (pageTitle) pageTitle.textContent = t.pageTitle;
            if (pageSubtitle) pageSubtitle.textContent = t.pageSubtitle;
            if (labelFullname) labelFullname.textContent = t.labelFullname;
            if (labelPhone) labelPhone.textContent = t.labelPhone;
            if (labelBuilding) labelBuilding.textContent = t.labelBuilding;
            if (labelFloor) labelFloor.textContent = t.labelFloor;
            if (labelApartment) labelApartment.textContent = t.labelApartment;
            if (labelStreet) labelStreet.textContent = t.labelStreet;
            if (labelNote) labelNote.textContent = t.labelNote;
            if (locationBtnText) locationBtnText.textContent = t.locationBtnText;
            if (locationHint) locationHint.textContent = t.locationHint;
            if (continueBtnText) continueBtnText.textContent = t.continueBtnText;
            if (poweredByText) poweredByText.textContent = t.poweredBy;

            // Update map popup elements
            const mapTitle = document.getElementById('map-title');
            const mapCancelBtn = document.getElementById('map-cancel-btn');
            const mapConfirmBtn = document.getElementById('map-confirm-btn');

            if (mapTitle) mapTitle.textContent = t.mapTitle;
            if (mapCancelBtn) mapCancelBtn.textContent = t.mapCancel;
            if (mapConfirmBtn) mapConfirmBtn.textContent = t.mapConfirm;

            // Update placeholders
            const inputFullname = document.getElementById('input-fullname');
            const inputPhone = document.getElementById('input-phone');
            const inputBuilding = document.getElementById('input-building');
            const inputFloor = document.getElementById('input-floor');
            const inputApartment = document.getElementById('input-apartment');
            const inputStreet = document.getElementById('input-street');
            const inputNote = document.getElementById('input-note');

            if (inputFullname) inputFullname.placeholder = t.placeholderFullname;
            if (inputPhone) inputPhone.placeholder = t.placeholderPhone;
            if (inputBuilding) inputBuilding.placeholder = t.placeholderBuilding;
            if (inputFloor) inputFloor.placeholder = t.placeholderFloor;
            if (inputApartment) inputApartment.placeholder = t.placeholderApartment;
            if (inputStreet) inputStreet.placeholder = t.placeholderStreet;
            if (inputNote) inputNote.placeholder = t.placeholderNote;

            // Update arrow direction
            const backArrow = document.querySelector('.back-arrow');
            const continueArrow = document.querySelector('.continue-arrow');

            if (backArrow) {
                if (lang === 'ar') {
                    backArrow.classList.remove('fa-arrow-left');
                    backArrow.classList.add('fa-arrow-right');
                } else {
                    backArrow.classList.remove('fa-arrow-right');
                    backArrow.classList.add('fa-arrow-left');
                }
            }

            if (continueArrow) {
                if (lang === 'ar') {
                    continueArrow.classList.remove('mr-2');
                    continueArrow.classList.add('ml-2');
                } else {
                    continueArrow.classList.remove('ml-2');
                    continueArrow.classList.add('mr-2');
                }
            }
        }

        // Load saved language preference on page load, default to Arabic
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('preferred_language') || 'ar';
            applyTranslations(savedLang);
        });
    </script>
</body>
</html>
