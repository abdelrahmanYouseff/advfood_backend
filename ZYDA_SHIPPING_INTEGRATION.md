# Zeada (Zyda) Orders - Shipping API Integration

## Overview

This document explains how Zeada orders are automatically integrated with the shipping company API after location is saved.

---

## 📋 Complete Order Flow

### 1. **Order Scraping** (Python Script)
- Python script (`python/scrap_zyda.py`) scrapes orders from Zeada platform
- Sends order data to API endpoint: `POST /api/zyda-orders`
- Order is saved to `zyda_orders` table

### 2. **Location Update** (User Action)
- User updates location for a Zeada order via frontend
- API endpoint: `PUT /api/zyda-orders/{id}/location`
- Location URL is resolved (if short link) and coordinates are extracted
- Coordinates saved to `zyda_orders` table

### 3. **Order Creation** (Automatic)
When location is provided:
- `ZydaOrderController@createOrderFromZydaOrder()` is called (line 305)
- **BEFORE** creating Order in database, shipping company is contacted (lines 496-557)
- Shipping company validates order and returns `dsp_order_id`
- **ONLY IF** shipping accepts order, Order is created in database with `dsp_order_id` already set
- Order appears in orders table with `source = 'zyda'`

### 4. **Shipping Integration** (Automatic)
**Method 1: Pre-creation shipping (Zeada orders)**
- Shipping company is contacted BEFORE Order creation (ZydaOrderController lines 496-557)
- If shipping returns `dsp_order_id`, Order is created WITH it already set
- Order Model's `created` hook sees `dsp_order_id` exists and skips duplicate API call ✅

**Method 2: Post-creation shipping (Fast-link orders)**
- Order is created WITHOUT `dsp_order_id`
- Order Model's `created` hook fires (Order.php line 123)
- Shipping company is contacted and `dsp_order_id` is returned
- `dsp_order_id` is saved to Order

---

## 🔧 Fixes Applied

### Fix #1: Prevent Duplicate API Calls
**Issue**: Order Model's `created` hook was sending to shipping API even when `dsp_order_id` was already set

**Solution**: Added check in Order.php line 123:
```php
if (!empty($order->shop_id) && empty($order->dsp_order_id)) {
```

**Result**: Zeada orders that already have `dsp_order_id` skip duplicate shipping API calls

### Fix #2: Missing Variable Declaration
**Issue**: Line 261 in Order.php used `$shippingService` without declaring it first

**Solution**: Added variable declaration:
```php
$shippingService = new \App\Services\ShippingService();
```

**Result**: Payment status updates now work correctly

### Fix #3: Better Logging
**Added**: Log message when order is created with existing `dsp_order_id`:
```
ℹ️ Order created with dsp_order_id already set - Skipping shipping API call
```

---

## ✅ How It Works Now

### For Zeada Orders:
```
1. Zyda order scraped → saved to zyda_orders table
2. User updates location → coordinates extracted
3. Contact shipping API (BEFORE creating Order)
   ├─ If SUCCESS: Get dsp_order_id → Create Order with it
   └─ If FAILURE: Throw exception → Order NOT created
4. Order appears in orders page
5. Order Model's created hook sees dsp_order_id exists → Skips duplicate call ✅
```

### For Fast-Link Orders:
```
1. Customer pays → Order created in database (no dsp_order_id)
2. Order Model's created hook fires
3. Contact shipping API → Get dsp_order_id → Save to Order
4. Order appears in orders page
```

---

## 🔍 Verification & Debugging

### Check if Order Was Sent to Shipping

**Method 1: Check Order Table**
```sql
SELECT id, order_number, dsp_order_id, shipping_status, source 
FROM orders 
WHERE source = 'zyda' 
ORDER BY created_at DESC;
```

If `dsp_order_id` is NOT NULL → Order was successfully sent to shipping ✅

**Method 2: Check Shipping Orders Table**
```sql
SELECT order_id, dsp_order_id, shipping_status, created_at 
FROM shipping_orders 
WHERE order_id = {your_order_id};
```

**Method 3: Check Laravel Logs**
```bash
tail -f storage/logs/laravel.log | grep "🚀\|✅\|❌"
```

### Log Messages to Look For

#### ✅ SUCCESS Indicators:
- `✅ STEP 1 SUCCESS: Shipping company returned dsp_order_id`
- `✅✅ STEP 2 SUCCESS: Order created in database with dsp_order_id`
- `✅✅ STEP 3: shipping_orders record inserted`
- `ℹ️ Order created with dsp_order_id already set - Skipping shipping API call`

#### ❌ FAILURE Indicators:
- `❌ Shipping company did not return dsp_order_id`
- `❌ CRITICAL: Failed to get dsp_order_id from shipping company`
- `🔴 VALIDATION ERROR (422) FROM SHIPPING COMPANY:`
- `🔴 AUTHENTICATION ERROR (401) - Invalid API Key`

### Common Issues & Solutions

#### Issue 1: Missing Coordinates
**Symptom**: Error message: "فشل إرسال الطلب إلى شركة الشحن"
**Cause**: Location URL doesn't contain valid coordinates
**Solution**: 
- Ensure location URL is a valid Google Maps link
- Check logs for coordinate extraction errors
- Verify short link resolves correctly

#### Issue 2: Invalid shop_id
**Symptom**: 422 Validation Error with "Invalid shop" message
**Cause**: shop_id not registered with shipping company
**Solution**: 
- Zeada orders use fixed shop_id = '11185'
- Verify this shop_id is registered in shipping company dashboard
- Check `ZydaOrderController.php` line 420 for shop_id configuration

#### Issue 3: Missing Required Fields
**Symptom**: Order created but not sent to shipping
**Cause**: Missing delivery_name, delivery_phone, delivery_address, or coordinates
**Solution**: 
- Check logs for missing fields warning
- Ensure Zeada order has all required data before updating location

---

## 📊 Database Schema

### zyda_orders Table
```
id                 - Primary key
zyda_order_key     - Unique order identifier from Zeada (e.g., "#GD7G-GAWP")
order_id           - Foreign key to orders table (NULL until Order created)
name               - Customer name
phone              - Customer phone
address            - Delivery address
location           - Google Maps URL (resolved from short link)
latitude           - Customer latitude
longitude          - Customer longitude
total_amount       - Order total
items              - JSON array of order items
created_at
updated_at
```

### orders Table
```
id                 - Primary key
order_number       - Unique order number (e.g., "ZYDA-20251122-0001")
user_id            - Fixed: 36 (for Zeada orders)
restaurant_id      - Fixed: 821017372 (for Zeada orders)
shop_id            - Fixed: '11185' (for Zeada orders)
dsp_order_id       - Shipping company order ID
shipping_status    - Status from shipping company
source             - 'zyda' for Zeada orders
payment_status     - 'paid' (always for Zeada)
delivery_name
delivery_phone
delivery_address
customer_latitude
customer_longitude
total
...
```

### shipping_orders Table
```
id
order_id           - Foreign key to orders table
shop_id            - Shop ID
dsp_order_id       - Shipping company order ID
shipping_status    - Status from shipping company
recipient_name
recipient_phone
recipient_address
latitude
longitude
driver_name
driver_phone
driver_latitude
driver_longitude
total
payment_type
notes
created_at
updated_at
```

---

## 🚀 Testing the Integration

### Test Case 1: New Zeada Order with Valid Location

**Steps:**
1. Python script scrapes order from Zeada
2. Order saved to `zyda_orders` table
3. User updates location with valid Google Maps link
4. Check logs for success messages
5. Verify Order appears in orders table with `dsp_order_id`
6. Verify shipping_orders table has entry

**Expected Result:**
- Order created successfully
- `dsp_order_id` is NOT NULL
- `shipping_status` = 'New Order'
- Logs show: `✅ STEP 1 SUCCESS`, `✅✅ STEP 2 SUCCESS`, `✅✅ STEP 3`

### Test Case 2: Zeada Order with Invalid Location

**Steps:**
1. Python script scrapes order
2. User updates location with invalid/broken link
3. Check logs for error messages

**Expected Result:**
- Error logged: `❌ CRITICAL: Failed to get dsp_order_id`
- Order NOT created in orders table
- Exception thrown to user

### Test Case 3: Fast-Link Order (Regular Order)

**Steps:**
1. Customer places order via fast-link
2. Payment completed
3. Order created automatically

**Expected Result:**
- Order created with `source` = fast-link
- Order Model's created hook sends to shipping
- `dsp_order_id` saved after creation
- Logs show: `📞 Contacting shipping company to get dsp_order_id`

---

## 📝 Configuration

### Required Environment Variables
```
SHIPPING_API_URL=https://your-shipping-api.com
SHIPPING_API_KEY=your-api-key-here
SHIPPING_API_VERIFY_SSL=true
```

### Zeada Order Configuration
**File**: `app/Http/Controllers/Api/ZydaOrderController.php`

**Fixed Values** (lines 399-400):
```php
$userId = 36;              // Fixed user for Zeada orders
$restaurantId = 821017372; // Fixed restaurant for Zeada orders
```

**Fixed shop_id** (line 420):
```php
$shopId = '11185';  // Fixed shop_id for all Zeada orders
```

**⚠️ IMPORTANT**: All Zeada orders MUST use shop_id = '11185' as required by the shipping company.

---

## 🔄 Order Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│                      ZEADA ORDER LIFECYCLE                       │
└─────────────────────────────────────────────────────────────────┘

1. SCRAPING
   Python Script → API: POST /api/zyda-orders
   ↓
   Save to zyda_orders table (order_id = NULL)

2. LOCATION UPDATE
   User Action → API: PUT /api/zyda-orders/{id}/location
   ↓
   Resolve short link → Extract coordinates → Save to zyda_orders

3. SHIPPING API (BEFORE ORDER CREATION)
   ZydaOrderController → ShippingService → Shipping Company API
   ↓
   Success: Get dsp_order_id
   Failure: Throw exception (Order NOT created)

4. ORDER CREATION (ONLY IF SHIPPING SUCCESS)
   Create Order with dsp_order_id already set
   ↓
   Order Model's created hook sees dsp_order_id → Skip duplicate call
   ↓
   Insert shipping_orders record
   ↓
   Link zyda_order to order (set order_id)

5. ORDER APPEARS IN SYSTEM
   Orders page shows order with shipping status
   ↓
   User can track order
   ↓
   Shipping company sends webhook updates
```

---

## 🛠️ Maintenance

### Adding New Fields to Shipping Payload
Edit: `app/Services/ShippingService.php` (lines 272-322)

### Changing Zeada Order Configuration
Edit: `app/Http/Controllers/Api/ZydaOrderController.php` (lines 399-420)

### Updating Order Lifecycle Hooks
Edit: `app/Models/Order.php` (lines 95-302)

---

## 📞 Support

For issues with:
- **Shipping API**: Check `storage/logs/laravel.log` for detailed error messages
- **Coordinate Extraction**: Check logs for URL parsing errors
- **Order Creation**: Check logs for validation errors

All logs use emoji prefixes for easy filtering:
- 🚀 Starting process
- ✅ Success
- ❌ Error
- ⚠️ Warning
- 📞 API call
- 📊 Status update
- 🔍 Debug info

---

## Summary

✅ **Zeada orders are now fully integrated with the shipping API**
✅ **Orders are sent to shipping BEFORE creation to ensure data validity**
✅ **Duplicate API calls are prevented**
✅ **Comprehensive logging for debugging**
✅ **All orders appear in the orders page automatically**

The system ensures that Zeada orders are treated exactly like paid fast-link orders, with automatic shipping integration and real-time status tracking.

