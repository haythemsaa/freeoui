# FreeOui API Documentation

Version: 1.0.0  
Base URL: `https://api.freeoui.tn/api/v1`

## Authentication

All authenticated endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer {access_token}
```

### Auth Endpoints

#### Register
```http
POST /auth/register
```

Request:
```json
{
  "phone_number": "+21612345678",
  "first_name": "Ahmed",
  "last_name": "Ben Ali",
  "email": "ahmed@example.com",
  "password": "password123",
  "date_of_birth": "1990-01-01"
}
```

#### Login
```http
POST /auth/login
```

Request:
```json
{
  "phone_number": "+21612345678",
  "password": "password123"
}
```

Response:
```json
{
  "status": "success",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbG...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {...}
  }
}
```

---

## Wallet Endpoints

### Get Wallet Balance
```http
GET /wallet
```

Response:
```json
{
  "status": "success",
  "data": {
    "wallet": {
      "balance": 150.50,
      "currency": "TND",
      "status": "active"
    },
    "transactions": [...]
  }
}
```

### Top Up Wallet
```http
POST /wallet/top-up
```

Request:
```json
{
  "amount": 50,
  "payment_provider": "d17"
}
```

### Withdraw from Wallet
```http
POST /wallet/withdraw
```

Request:
```json
{
  "amount": 20,
  "reason": "Bank transfer"
}
```

---

## Payment Endpoints

### Get Payment History
```http
GET /payments
```

### Create Payment
```http
POST /payments
```

Request:
```json
{
  "payment_type": "advantage_purchase",
  "payment_provider": "d17",
  "amount": 25,
  "merchant_id": 1,
  "advantage_id": 5
}
```

### Verify Payment
```http
GET /payments/{paymentNumber}/verify
```

### Payment Webhook (Public)
```http
POST /webhooks/payments/{provider}
```

---

## Boost/Campaign Endpoints (Merchants)

### Get Boosts
```http
GET /boosts
```

### Create Boost Campaign
```http
POST /boosts
```

Request:
```json
{
  "advantage_id": 10,
  "boost_type": "proximity",
  "budget": 500,
  "cost_per_click": 0.5,
  "cost_per_view": 0.1,
  "start_date": "2024-02-01",
  "end_date": "2024-02-28",
  "target_radius_km": 10
}
```

### Pause Boost
```http
POST /boosts/{id}/pause
```

### Resume Boost
```http
POST /boosts/{id}/resume
```

### Record Impression
```http
POST /boosts/{id}/impression
```

### Record Click
```http
POST /boosts/{id}/click
```

---

## Chat Endpoints

### Get Conversations
```http
GET /chat/conversations
```

### Start Conversation
```http
POST /chat/conversations
```

Request:
```json
{
  "merchant_id": 5,
  "message": "Hello, I have a question about your offer."
}
```

### Get Messages
```http
GET /chat/conversations/{conversationId}
```

### Send Message
```http
POST /chat/conversations/{conversationId}/messages
```

Request:
```json
{
  "message": "Thank you for your response!",
  "message_type": "text"
}
```

### Mark as Read
```http
POST /chat/conversations/{conversationId}/read
```

---

## Notification Endpoints

### Get Notifications
```http
GET /notifications
```

Response:
```json
{
  "status": "success",
  "data": {
    "notifications": [...],
    "unread_count": 5
  }
}
```

### Mark as Read
```http
POST /notifications/{id}/read
```

### Mark All as Read
```http
POST /notifications/read-all
```

### Delete Notification
```http
DELETE /notifications/{id}
```

### Get Settings
```http
GET /notifications/settings
```

### Update Settings
```http
PUT /notifications/settings
```

Request:
```json
{
  "proximity_alerts": true,
  "promotional": true,
  "chat": true,
  "system": true
}
```

---

## Social/Sharing Endpoints

### Share Advantage
```http
POST /social/share
```

Request:
```json
{
  "advantage_id": 10,
  "platform": "facebook",
  "referral_code": "USER123"
}
```

### Track Share Click
```http
POST /social/share/{shareId}/click
```

### Track Conversion
```http
POST /social/share/{shareId}/conversion
```

### Get Share History
```http
GET /social/shares
```

### Get Referral Stats
```http
GET /social/referrals
```

---

## Analytics Endpoints

### Get Dashboard (Merchants)
```http
GET /analytics/dashboard?range=30d
```

### Get User Engagement
```http
GET /analytics/engagement
```

Response:
```json
{
  "status": "success",
  "data": {
    "engagement": {
      "total_favorites": 15,
      "total_scans": 42,
      "total_savings": 250.50,
      "level": 5,
      "points": 1250,
      "streak_days": 7
    }
  }
}
```

### Track Custom Event
```http
POST /analytics/track
```

Request:
```json
{
  "event_name": "advantage_viewed",
  "event_category": "engagement",
  "properties": {
    "advantage_id": 10,
    "source": "proximity_alert"
  }
}
```

### Get Trending Advantages
```http
GET /analytics/trending?limit=10&governorate_id=1
```

---

## Offline Sync Endpoints

### Sync Offline Actions
```http
POST /sync
```

Request:
```json
{
  "actions": [
    {
      "action_type": "create",
      "entity_type": "favorite",
      "payload": {
        "advantage_id": 10
      },
      "client_uuid": "uuid-1234",
      "timestamp": "2024-01-22T10:30:00Z"
    }
  ]
}
```

### Process Queue
```http
POST /sync/process
```

### Get Queue Status
```http
GET /sync/status
```

### Retry Failed
```http
POST /sync/retry
```

### Resolve Conflict
```http
POST /sync/{queueId}/resolve
```

Request:
```json
{
  "resolution": "use_server"
}
```

---

## Existing Endpoints (Phase 1)

### Advantages
- `GET /advantages` - List advantages
- `GET /advantages/{id}` - Get advantage details
- `POST /advantages/{id}/favorite` - Add to favorites
- `DELETE /advantages/{id}/favorite` - Remove from favorites
- `GET /favorites` - Get user favorites

### Proximity
- `POST /proximity/location` - Update user location
- `GET /proximity/preferences` - Get proximity preferences
- `PUT /proximity/preferences` - Update preferences
- `GET /proximity/alerts` - Get alerts history

### QR Codes
- `GET /qr-codes` - Get user QR codes
- `POST /qr-codes/generate` - Generate new QR code
- `POST /qr-codes/validate` - Validate QR code
- `DELETE /qr-codes/{id}` - Cancel QR code

### User Profile
- `GET /users/profile` - Get profile
- `PUT /users/profile` - Update profile
- `POST /users/fcm-token` - Update FCM token

### Data
- `GET /categories` - Get categories
- `GET /governorates` - Get governorates
- `GET /cities` - Get cities

---

## Health Check

### Health
```http
GET /health
```

Response:
```json
{
  "status": "ok",
  "timestamp": "2024-01-22T10:00:00Z",
  "service": "FreeOui API"
}
```

### Readiness
```http
GET /ready
```

Response:
```json
{
  "status": "ok",
  "timestamp": "2024-01-22T10:00:00Z",
  "checks": {
    "database": {"status": "ok"},
    "redis": {"status": "ok"},
    "storage": {"status": "ok"}
  }
}
```

---

## Error Responses

All error responses follow this format:

```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

---

## Pagination

List endpoints return paginated results:

```json
{
  "status": "success",
  "data": {
    "items": [...],
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "total_items": 250
    }
  }
}
```

Query parameters:
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)

---

## Rate Limiting

- **Authenticated requests**: 60 requests/minute
- **Public endpoints**: 30 requests/minute
- **Webhook endpoints**: No limit

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642857600
```

---

## Webhooks

### Payment Providers

FreeOui receives webhooks from payment providers to confirm payments.

#### D17 Webhook
```http
POST /webhooks/payments/d17
```

#### Flouci Webhook
```http
POST /webhooks/payments/flouci
```

#### Paymee Webhook
```http
POST /webhooks/payments/paymee
```

All webhooks are secured with HMAC signatures.

---

## SDKs & Tools

- **Postman Collection**: [Download](https://api.freeoui.tn/docs/postman)
- **OpenAPI Spec**: [View](https://api.freeoui.tn/docs/openapi.json)
- **Flutter SDK**: [GitHub](https://github.com/haythemsaa/freeoui-flutter)

---

## Support

- **Email**: api@freeoui.tn
- **Documentation**: https://docs.freeoui.tn
- **Status Page**: https://status.freeoui.tn

---

**Last Updated**: January 2025  
**API Version**: 1.0.0
