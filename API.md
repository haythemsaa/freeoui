# FreeOui API Documentation

Version: 1.0.0
Base URL: `https://api.freeoui.tn/api/v1`

## Authentication

All API requests require authentication using JWT tokens.

### Request Headers

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Token Refresh

Tokens expire after 60 minutes. Use the refresh endpoint to get a new token.

## Endpoints

### Authentication

#### Register User

```http
POST /auth/register
```

**Body:**
```json
{
  "phone_number": "20123456",
  "full_name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": { ...},
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

#### Login

```http
POST /auth/login
```

**Body:**
```json
{
  "phone_number": "20123456",
  "password": "password123"
}
```

#### Refresh Token

```http
POST /auth/refresh
```

**Body:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

---

### Advantages

#### List Advantages

```http
GET /advantages
```

**Query Parameters:**
- `category_id` (optional): Filter by category
- `city_id` (optional): Filter by city
- `latitude` (optional): User latitude for distance calculation
- `longitude` (optional): User longitude
- `radius` (optional): Search radius in meters (default: 5000)
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Results per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "20% de réduction",
      "description": "Sur tous les plats",
      "type": "percentage",
      "discount_percentage": 20,
      "distance": 523.45,
      "merchant": {
        "id": 1,
        "business_name": "Restaurant Le Bon Goût",
        "latitude": 36.8065,
        "longitude": 10.1815
      },
      "category": {
        "id": 1,
        "name": "Restaurant",
        "icon": "restaurant"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

#### Get Nearby Advantages

```http
GET /advantages/nearby
```

**Query Parameters:**
- `latitude` (required): User latitude
- `longitude` (required): User longitude
- `radius` (optional): Search radius in meters (default: 1000)

#### Get Advantage Details

```http
GET /advantages/{id}
```

#### Add to Favorites

```http
POST /advantages/{id}/favorite
```

#### Remove from Favorites

```http
DELETE /advantages/{id}/favorite
```

---

### QR Codes

#### Generate QR Code

```http
POST /qr-codes/generate
```

**Body:**
```json
{
  "advantage_id": 1,
  "original_amount": 50.000
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "code": "QR-ABC123XYZ",
    "advantage_id": 1,
    "original_amount": 50.000,
    "discounted_amount": 40.000,
    "generated_at": "2024-01-15T10:30:00Z",
    "expires_at": "2024-01-15T12:30:00Z",
    "status": "active",
    "signature": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b..."
  }
}
```

#### Get QR Code History

```http
GET /qr-codes/history
```

**Query Parameters:**
- `status` (optional): Filter by status (active, used, expired, cancelled)
- `page` (optional)
- `per_page` (optional)

---

### User Profile

#### Get Profile

```http
GET /user/profile
```

#### Update Profile

```http
PUT /user/profile
```

**Body:**
```json
{
  "full_name": "John Doe",
  "email": "john@example.com",
  "city_id": 1
}
```

#### Update Location

```http
POST /user/location
```

**Body:**
```json
{
  "latitude": 36.8065,
  "longitude": 10.1815
}
```

---

### Merchants (Admin/Merchant endpoints)

#### Login Merchant

```http
POST /merchants/auth/login
```

**Body:**
```json
{
  "email": "merchant@example.com",
  "password": "password123"
}
```

#### Get Dashboard Stats

```http
GET /merchants/analytics/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "today_scans": 45,
    "week_scans": 312,
    "month_scans": 1205,
    "daily_scans": [
      { "date": "2024-01-15", "count": 45 },
      { "date": "2024-01-14", "count": 52 }
    ],
    "top_advantages": [
      { "title": "20% réduction", "scans": 150 }
    ]
  }
}
```

#### Validate QR Code

```http
POST /merchants/qr-codes/validate
```

**Body:**
```json
{
  "code": "QR-ABC123XYZ",
  "signature": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b..."
}
```

#### List Advantages

```http
GET /merchants/advantages
```

#### Create Advantage

```http
POST /merchants/advantages
```

**Body:**
```json
{
  "title": "20% de réduction",
  "description": "Sur tous les plats",
  "type": "percentage",
  "discount_percentage": 20,
  "category_id": 1,
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "days_available": [1, 2, 3, 4, 5],
  "is_active": true
}
```

---

## Error Responses

### 400 Bad Request

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 401 Unauthorized

```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### 404 Not Found

```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 429 Too Many Requests

```json
{
  "success": false,
  "message": "Trop de requêtes. Veuillez réessayer plus tard.",
  "retry_after": 60
}
```

### 500 Server Error

```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## Rate Limiting

- **General API**: 60 requests per minute per user
- **Login endpoint**: 5 requests per minute per IP
- **QR validation**: 10 requests per second per merchant

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests

---

## Webhooks

Merchants can configure webhooks to receive real-time notifications.

### Configuration

Set webhook URL in merchant settings:
```json
{
  "webhook_url": "https://your-server.com/webhook",
  "webhook_secret": "your_secret_key"
}
```

### Signature Verification

All webhooks include an `X-Webhook-Signature` header with HMAC SHA-256 signature.

```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
```

### Available Events

- `qr_code.generated`
- `qr_code.validated`
- `qr_code.expired`
- `advantage.created`
- `advantage.updated`
- `subscription.renewed`

### Webhook Payload

```json
{
  "event": "qr_code.validated",
  "timestamp": "2024-01-15T10:30:00Z",
  "data": {
    "qr_code_id": 123,
    "advantage_id": 1,
    "user_id": 456,
    "original_amount": 50.000,
    "discounted_amount": 40.000
  }
}
```

---

## OpenAPI Specification

Full OpenAPI 3.0 specification available at:
`/backend/storage/api-docs/openapi.yaml`

---

## SDKs & Libraries

Coming soon:
- JavaScript/TypeScript SDK
- PHP SDK
- Python SDK

---

## Support

- Email: api@freeoui.tn
- Documentation: https://docs.freeoui.tn
- Status: https://status.freeoui.tn
