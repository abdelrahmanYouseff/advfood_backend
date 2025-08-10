# AdvFood API - Quick Start

## 🚀 API Endpoints

### 1. User Registration
**POST** `/api/auth/register`

Register a new user (role: user)

```bash
curl -X POST https://advfoodapp.clarastars.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد",
    "email": "ahmed@example.com", 
    "password": "12345678",
    "password_confirmation": "12345678",
    "phone_number": "+966501234567",
    "address": "شارع الملك فهد، الرياض",
    "country": "السعودية"
  }'
```

### 2. Get Restaurants
**GET** `/api/restaurants`

Get all active restaurants

```bash
curl -X GET https://advfoodapp.clarastars.com/api/restaurants
```

## 📋 Features

- ✅ User registration with role 'user'
- ✅ Sanctum token authentication
- ✅ Validation for all fields
- ✅ Arabic text support
- ✅ Error handling
- ✅ JSON responses

## 🔧 Setup

1. **Import Postman Collection**: `AdvFood_API.postman_collection.json`
2. **Set Environment Variable**: `base_url = https://advfoodapp.clarastars.com/api`
3. **Test Endpoints**

## 📚 Full Documentation

See `API_DOCUMENTATION.md` for complete details.

## 🧪 Testing

### Local Development
```bash
# User Registration
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"12345678","password_confirmation":"12345678"}'

# Get Restaurants  
curl -X GET http://127.0.0.1:8000/api/restaurants
```

### Production
```bash
# User Registration
curl -X POST https://advfoodapp.clarastars.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"12345678","password_confirmation":"12345678"}'

# Get Restaurants
curl -X GET https://advfoodapp.clarastars.com/api/restaurants
``` 
