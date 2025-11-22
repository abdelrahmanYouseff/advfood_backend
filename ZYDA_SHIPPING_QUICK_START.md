# Zeada Shipping Integration - Quick Start Guide

## ✅ What Was Fixed

### Issue #1: Duplicate API Calls (FIXED ✅)
**Problem**: Order Model's `created` hook was sending orders to shipping API even when they already had `dsp_order_id` (causing duplicate API calls for Zeada orders)

**Solution**: Added check in `app/Models/Order.php` line 123:
```php
// OLD CODE:
if (!empty($order->shop_id)) {

// NEW CODE:
if (!empty($order->shop_id) && empty($order->dsp_order_id)) {
```

### Issue #2: Missing Variable (FIXED ✅)
**Problem**: Missing `$shippingService` declaration in payment status update

**Solution**: Added variable declaration at line 271 in `app/Models/Order.php`

### Issue #3: Better Logging (ADDED ✅)
**Added**: Clear log message when order is skipped due to existing `dsp_order_id`

---

## 🎯 How It Works Now

### Zeada Order Flow:
```
1. Python scrapes order → Saved to zyda_orders table
2. User updates location → Coordinates extracted
3. 🚀 Contact Shipping API FIRST (before creating Order)
   ├─ Success → Get dsp_order_id
   └─ Failure → Throw exception, Order NOT created
4. ✅ Create Order WITH dsp_order_id already set
5. Order Model's created hook sees dsp_order_id → Skip duplicate call ✅
6. Order appears in orders page with shipping status
```

**Key Point**: Zeada orders contact shipping API **BEFORE** Order creation to ensure data validity.

---

## 🧪 Testing the Integration

### Run the Test Command:
```bash
# Test all orders
php artisan zyda:test-shipping

# Test recent orders only
php artisan zyda:test-shipping --check-recent
```

### What the Test Checks:
- ✅ Environment configuration (API URL, API Key)
- ✅ Database tables (zyda_orders, orders, shipping_orders)
- ✅ Order statistics (sent vs not sent)
- ✅ Recent order details with shipping status

### Expected Output:
```
✅ ALL ZEADA ORDERS SENT TO SHIPPING SUCCESSFULLY!
```

---

## 🔍 Quick Verification

### 1. Check if Orders Have dsp_order_id:
```sql
SELECT id, order_number, dsp_order_id, shipping_status 
FROM orders 
WHERE source = 'zyda' 
ORDER BY created_at DESC 
LIMIT 10;
```

**Expected**: `dsp_order_id` should NOT be NULL ✅

### 2. Check Laravel Logs:
```bash
# Show only important messages (success/error)
tail -f storage/logs/laravel.log | grep "🚀\|✅\|❌"
```

**Look for**:
- ✅ `STEP 1 SUCCESS: Shipping company returned dsp_order_id`
- ✅ `STEP 2 SUCCESS: Order created in database with dsp_order_id`
- ℹ️ `Order created with dsp_order_id already set - Skipping shipping API call`

### 3. Check Shipping Orders Table:
```sql
SELECT order_id, dsp_order_id, shipping_status 
FROM shipping_orders 
WHERE order_id IN (
    SELECT id FROM orders WHERE source = 'zyda'
);
```

---

## 🐛 Troubleshooting

### Problem: Orders Created But NOT Sent to Shipping

**Check logs for**:
```bash
grep "❌" storage/logs/laravel.log | tail -20
```

**Common causes**:
1. **Missing Coordinates**: Location URL invalid or coordinates not extracted
2. **Invalid shop_id**: shop_id '11185' not registered with shipping company
3. **Missing Required Fields**: name, phone, address, or coordinates missing
4. **API Credentials**: SHIPPING_API_URL or SHIPPING_API_KEY incorrect

**Solutions**:
```bash
# 1. Check environment configuration
php artisan config:clear
cat .env | grep SHIPPING

# 2. Check recent errors in logs
tail -100 storage/logs/laravel.log | grep "❌"

# 3. Test a specific order
php artisan zyda:test-shipping --check-recent
```

### Problem: Location Not Extracting Coordinates

**Log messages to look for**:
- `⚠️ Could not extract coordinates from URL`
- `⚠️ Missing required fields for shipping: coordinates`

**Solution**: 
- Ensure location is a valid Google Maps link
- Check if short link resolves correctly
- Test URL manually: `curl -L "your-short-link"`

### Problem: 422 Validation Error

**Log message**: `🔴 VALIDATION ERROR (422) FROM SHIPPING COMPANY`

**Causes**:
- Invalid shop_id (not registered)
- Missing required fields
- Invalid data format

**Solution**:
- Verify shop_id '11185' is registered with shipping company
- Check logs for specific validation errors
- Ensure all required fields are present

---

## 📋 Configuration

### Required Environment Variables:
```env
SHIPPING_API_URL=https://your-shipping-api.com
SHIPPING_API_KEY=your-api-key-here
SHIPPING_API_VERIFY_SSL=true
```

### Zeada Order Settings:
**File**: `app/Http/Controllers/Api/ZydaOrderController.php`

```php
// Lines 399-400: Fixed user and restaurant
$userId = 36;              // Fixed user for all Zeada orders
$restaurantId = 821017372; // Fixed restaurant for all Zeada orders

// Line 420: Fixed shop_id
$shopId = '11185';  // REQUIRED: All Zeada orders use this shop_id
```

**⚠️ IMPORTANT**: Do NOT change shop_id '11185' - it's required by the shipping company.

---

## 📊 Database Queries for Debugging

### Find Orders NOT Sent to Shipping:
```sql
SELECT id, order_number, delivery_name, delivery_phone, delivery_address, 
       customer_latitude, customer_longitude, created_at
FROM orders 
WHERE source = 'zyda' 
  AND dsp_order_id IS NULL
ORDER BY created_at DESC;
```

### Check Zeada Orders Without Order Link:
```sql
SELECT id, phone, name, address, location, latitude, longitude, created_at
FROM zyda_orders 
WHERE order_id IS NULL
ORDER BY created_at DESC;
```

### Match Zeada Order to Order:
```sql
SELECT 
    zo.id as zyda_id,
    zo.phone,
    zo.name,
    o.id as order_id,
    o.order_number,
    o.dsp_order_id,
    o.shipping_status
FROM zyda_orders zo
LEFT JOIN orders o ON zo.order_id = o.id
ORDER BY zo.created_at DESC
LIMIT 10;
```

---

## 🎉 Success Indicators

### ✅ Integration Working Correctly If:
1. All Zeada orders have `dsp_order_id` in orders table
2. All Zeada orders have matching records in `shipping_orders` table
3. Logs show: `✅ STEP 1 SUCCESS`, `✅✅ STEP 2 SUCCESS`, `✅✅ STEP 3`
4. Test command shows: `✅ ALL ZEADA ORDERS SENT TO SHIPPING SUCCESSFULLY!`
5. Orders appear in shipping company dashboard

### ❌ Integration NOT Working If:
1. `dsp_order_id` is NULL for Zeada orders
2. Logs show errors: `❌ Shipping company did not return dsp_order_id`
3. Orders created but not in `shipping_orders` table
4. Shipping company dashboard doesn't show the orders

---

## 📖 Full Documentation

For detailed information, see:
- **Complete guide**: `ZYDA_SHIPPING_INTEGRATION.md`
- **Test command**: `php artisan zyda:test-shipping`
- **Logs**: `storage/logs/laravel.log`

---

## 🔄 Order Flow Diagram

```
ZEADA ORDER → SHIPPING API INTEGRATION FLOW
═════════════════════════════════════════════

┌──────────────────────┐
│  Python Script       │
│  Scrapes Zeada       │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  zyda_orders table   │
│  (order_id = NULL)   │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  User Updates        │
│  Location            │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────────────────────────┐
│  ZydaOrderController                     │
│  1. Resolve short link                   │
│  2. Extract coordinates                  │
│  3. Save to zyda_orders                  │
└──────┬───────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────┐
│  🚀 STEP 1: Contact Shipping API         │
│  (BEFORE creating Order)                 │
│  - Send order data to shipping company   │
│  - Get dsp_order_id                      │
└──────┬───────────────────────────────────┘
       │
       ├──── Success ✅ ─────┐
       │                     │
       │                     ▼
       │             ┌──────────────────────┐
       │             │  Create Order with   │
       │             │  dsp_order_id set    │
       │             └──────┬───────────────┘
       │                    │
       │                    ▼
       │             ┌──────────────────────┐
       │             │  Order Model         │
       │             │  created hook        │
       │             │  (skips duplicate)   │
       │             └──────┬───────────────┘
       │                    │
       │                    ▼
       │             ┌──────────────────────┐
       │             │  Insert              │
       │             │  shipping_orders     │
       │             └──────┬───────────────┘
       │                    │
       │                    ▼
       │             ┌──────────────────────┐
       │             │  ✅ Order appears    │
       │             │  in orders page      │
       │             └──────────────────────┘
       │
       └──── Failure ❌ ─────┐
                            │
                            ▼
                     ┌─────────────────────┐
                     │  Throw Exception    │
                     │  Order NOT created  │
                     └─────────────────────┘
```

---

## Summary

✅ **Zeada orders NOW automatically integrate with shipping API**
✅ **Orders sent to shipping BEFORE database creation**
✅ **Duplicate API calls prevented**
✅ **Comprehensive error logging**
✅ **Test command available for verification**

**Run test**: `php artisan zyda:test-shipping`

**Check logs**: `tail -f storage/logs/laravel.log | grep "🚀\|✅\|❌"`

