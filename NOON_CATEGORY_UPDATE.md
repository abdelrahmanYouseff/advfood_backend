# Noon Payment Category Update

## Summary
Updated the Noon payment integration to fix two critical issues:
1. Changed payment category from `"general"` to `"pay"` as per Noon support instructions
2. Fixed response handling to use correct checkout URL field (`result.checkoutData.postUrl`)

## Changes Made

### 1. Configuration File: `config/noon.php`
- Updated default category from `'general'` to `'pay'`
- Location: Line 31

### 2. Test Controller: `app/Http/Controllers/TestNoonController.php`

**Category Updates** - Changed in all test methods:
- `createPayment()` method - Line 30
- `finalTest()` method - Line 305
- `testNewApiKey()` method - Line 375
- `testWithConfig()` method - Line 596
- `finalDirectTest()` method - Line 660
- `finalEnvConfigTest()` method - Line 734

**Response Handling Fix** - `createPayment()` method (Lines 101-117):
- Changed from checking `data['invoiceUrl']` to `data['result']['checkoutData']['postUrl']`
- This is the correct field returned by Noon API for the payment page URL

## Noon Configuration Details

According to Noon support, the configuration for **adv_food** business identifier is:

| Parameter | Value |
|-----------|-------|
| Business Identifier | adv_food |
| Payment Option | Cards |
| **Category** | **pay** |
| Amount Range | 0-50,000 |
| Currency | SAR |
| Card Schemes | VISA, MASTERCARD, MADA, ApplePay |

## Next Steps

1. **Clear Laravel cache** to ensure the new config is loaded:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test the payment integration** using any of the test endpoints:
   - `/test-noon/create-payment`
   - `/test-noon/final-test`
   - `/test-noon/test-with-config`

3. **Verify the request** is now sending `"category": "pay"` in the payload

## Expected API Request & Response Format

### Request Format
```json
{
  "apiOperation": "INITIATE",
  "order": {
    "amount": 1,
    "currency": "SAR",
    "reference": "ORDER-...",
    "name": "Payment order",
    "category": "pay"
  },
  "configuration": {
    "returnUrl": "...",
    "paymentAction": "AUTHORIZE"
  }
}
```

### Successful Response Format
```json
{
  "resultCode": 0,
  "message": "Processed successfully",
  "result": {
    "order": {
      "status": "INITIATED",
      "id": 9682729789593492,
      "category": "pay"
    },
    "checkoutData": {
      "postUrl": "https://pay-test.sa.noonpayments.com/...",
      "jsUrl": "https://pay-test.sa.noonpayments.com/..."
    },
    "paymentOptions": [...]
  }
}
```

**Important:** The payment checkout URL is in `result.checkoutData.postUrl`, NOT in a root-level `invoiceUrl` field.

## Support Reference

This change was implemented based on Noon support's response on October 1, 2025, which stated:

> "Could you please pass the order category as 'pay' and recheck?"

---

**Date Updated:** October 1, 2025  
**Updated By:** System Administrator  
**Status:** ✅ Completed

