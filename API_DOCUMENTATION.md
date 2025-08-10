# AdvFood API Documentation

## Base URL
```
https://advfoodapp.clarastars.com/api
```

## Authentication
Currently, most endpoints are public. Authentication will be added later using Laravel Sanctum.

## Endpoints

### 1. User Registration
**POST** `/auth/register`

Register a new user with role 'user'.

**Request Body:**
```json
{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "phone_number": "+966501234567",
    "address": "شارع الملك فهد، الرياض",
    "country": "السعودية"
}
```

**Response (Success - 201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 3,
            "name": "أحمد محمد",
            "email": "ahmed@example.com",
            "phone_number": "+966501234567",
            "address": "شارع الملك فهد، الرياض",
            "country": "السعودية",
            "role": "user",
            "created_at": "2025-08-10T08:07:41.000000Z"
        },
        "token": "1|moghQ4V66qPBUnSlCb8NBDNTDeuJhQ1QQQ2OtNHz74cd4faa",
        "token_type": "Bearer"
    }
}
```

**Response (Validation Error - 422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."],
        "password": ["The password confirmation does not match."]
    }
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `email`: required, email, unique, max 255 characters
- `password`: required, string, min 8 characters, must be confirmed
- `phone_number`: optional, string, max 20 characters
- `address`: optional, string, max 1000 characters
- `country`: optional, string, max 100 characters

**Notes:**
- All new users are automatically assigned role 'user'
- Returns a Sanctum token for immediate authentication
- Password is automatically hashed
- Email must be unique in the system

### 2. User Login
**POST** `/auth/login`

Login with existing user credentials.

**Request Body:**
```json
{
    "email": "ahmed@example.com",
    "password": "12345678"
}
```

**Response (Success - 200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 4,
            "name": "أحمد محمد",
            "email": "ahmed@example.com",
            "phone_number": "+966501234567",
            "address": "شارع الملك فهد، الرياض",
            "country": "السعودية",
            "role": "user",
            "created_at": "2025-08-10T08:24:13.000000Z"
        },
        "token": "2|fqaWp3S0ucRIDSrH2RbBqZS7ZuvUvDiUrRHXAVTTba2d1d0c",
        "token_type": "Bearer"
    }
}
```

**Response (Invalid Credentials - 401):**
```json
{
    "success": false,
    "message": "Invalid credentials",
    "error": "Email or password is incorrect"
}
```

**Response (Validation Error - 422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

**Validation Rules:**
- `email`: required, email format
- `password`: required, string

**Notes:**
- Returns user data and Sanctum token for authentication
- Token can be used for subsequent authenticated requests
- Invalid credentials return 401 status code

### 3. Get All Restaurants
**GET** `/restaurants`

Returns a list of all active restaurants with basic information.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "name": "Delawa",
            "logo": "https://advfoodapp.clarastars.com/storage/restaurants/logos/1754565610_Screenshot 1447-02-13 at 2.05.54 PM.png"
        },
        {
            "id": 3,
            "name": "Bakiza",
            "logo": "https://advfoodapp.clarastars.com/storage/restaurants/logos/1754565656_Screenshot 1447-02-13 at 2.20.43 PM.png"
        }
    ]
}
```

**Parameters:**
- None

**Notes:**
- Only returns active restaurants (`is_active = true`)
- Returns only `id`, `name`, and `logo` fields
- Logo URLs are full URLs pointing to the storage directory
- If no logo is set, `logo` will be `null`

## Testing

### Using cURL

#### User Registration
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

#### User Login
```bash
curl -X POST https://advfoodapp.clarastars.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ahmed@example.com",
    "password": "12345678"
  }'
```

#### Get Restaurants
```bash
curl -X GET https://advfoodapp.clarastars.com/api/restaurants
```

### Using Postman

#### User Registration
1. Create a new POST request
2. Set URL to: `https://advfoodapp.clarastars.com/api/auth/register`
3. Set Headers: `Content-Type: application/json`
4. Set Body (raw JSON):
```json
{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "phone_number": "+966501234567",
    "address": "شارع الملك فهد، الرياض",
    "country": "السعودية"
}
```
5. Send the request

#### Get Restaurants
1. Create a new GET request
2. Set URL to: `https://advfoodapp.clarastars.com/api/restaurants`
3. Send the request

## Future Endpoints
- User registration and authentication
- Menu items by restaurant
- Order creation and management
- Invoice generation
- Admin-only endpoints for management 
