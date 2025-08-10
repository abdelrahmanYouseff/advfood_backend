# AdvFood API Documentation

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
Currently, most endpoints are public. Authentication will be added later using Laravel Sanctum.

## Endpoints

### 1. Get All Restaurants
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
            "logo": "http://127.0.0.1:8000/storage/restaurants/logos/1754565610_Screenshot 1447-02-13 at 2.05.54 PM.png"
        },
        {
            "id": 3,
            "name": "Bakiza",
            "logo": "http://127.0.0.1:8000/storage/restaurants/logos/1754565656_Screenshot 1447-02-13 at 2.20.43 PM.png"
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
```bash
curl -X GET http://127.0.0.1:8000/api/restaurants
```

### Using Postman
1. Create a new GET request
2. Set URL to: `http://127.0.0.1:8000/api/restaurants`
3. Send the request

## Future Endpoints
- User registration and authentication
- Menu items by restaurant
- Order creation and management
- Invoice generation
- Admin-only endpoints for management 
