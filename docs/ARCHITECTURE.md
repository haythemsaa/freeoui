# FreeOui - Architecture Documentation

## System Overview

FreeOui is a proximity-based promotional platform built with a microservices-inspired architecture, utilizing modern technologies for scalability and performance.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT APPLICATIONS                       │
├─────────────────────────────────────────────────────────────┤
│  Mobile App (Flutter)  │  Web Admin (React)  │  Future Web  │
└──────────┬─────────────┴──────────┬───────────┴──────────────┘
           │                        │
           └────────────┬───────────┘
                        │ HTTPS/REST API
                        ↓
           ┌────────────────────────┐
           │   Nginx (Reverse Proxy) │
           └────────────┬───────────┘
                        │
           ┌────────────────────────┐
           │   Laravel API Gateway   │
           │  - Authentication       │
           │  - Rate Limiting        │
           │  - Request Validation   │
           └────────────┬───────────┘
                        │
        ┌───────────────┼───────────────┐
        ↓               ↓               ↓
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   User       │ │  Merchant    │ │  Proximity   │
│   Service    │ │  Service     │ │  Service     │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       └────────────────┼────────────────┘
                        │
        ┌───────────────┼───────────────┐
        ↓               ↓               ↓
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  PostgreSQL  │ │    Redis     │ │    MinIO     │
│  + PostGIS   │ │   (Cache)    │ │  (Storage)   │
└──────────────┘ └──────────────┘ └──────────────┘
```

## Technology Stack

### Backend
- **Framework**: Laravel 11.x
- **Language**: PHP 8.3+
- **Database**: PostgreSQL 16 + PostGIS
- **Cache**: Redis 7.x
- **Queue**: Laravel Queues (Redis)
- **Storage**: MinIO (S3-compatible)
- **Real-time**: Laravel Reverb

### Frontend Mobile
- **Framework**: Flutter 3.19+
- **Language**: Dart 3.3+
- **State Management**: Riverpod
- **Networking**: Dio + Retrofit
- **Local DB**: Sqflite + Hive
- **Maps**: Mapbox/Google Maps
- **Notifications**: Firebase Cloud Messaging

### Frontend Web
- **Framework**: React 18
- **Language**: TypeScript
- **Build Tool**: Vite
- **UI Library**: Ant Design
- **State**: Zustand + React Query
- **Maps**: Mapbox GL JS

### Infrastructure
- **Container**: Docker + Docker Compose
- **Web Server**: Nginx
- **CI/CD**: GitHub Actions
- **Monitoring**: Sentry + Grafana

## Core Components

### 1. Proximity Alert System

The heart of the application - detects users near merchants with active offers.

**Flow:**

1. **GPS Tracking**
   - Mobile app sends GPS coordinates every 30s
   - Stored in `user_locations` table with PostGIS geometry

2. **Proximity Detection**
   - Background job queries PostGIS for nearby merchants
   - Uses spatial indexing for performance
   - Applies user preferences (radius, categories, quiet hours)

3. **Smart Filtering**
   - Anti-spam rules (max alerts per day, minimum interval)
   - Relevance scoring algorithm
   - Check if user already used the offer

4. **Notification**
   - Send push via Firebase Cloud Messaging
   - Log in `proximity_alerts_log` table
   - Track opens and conversions

**PostGIS Query Example:**

```sql
SELECT
    a.id,
    a.title,
    m.business_name,
    ST_Distance(
        ST_MakePoint(36.8065, 10.1815)::geography,
        m.geom
    ) AS distance_meters
FROM advantages a
JOIN merchants m ON a.merchant_id = m.id
WHERE
    a.status = 'active'
    AND ST_DWithin(
        ST_MakePoint(36.8065, 10.1815)::geography,
        m.geom,
        1000  -- 1km radius
    )
ORDER BY distance_meters ASC;
```

### 2. QR Code System

Secure QR code generation and validation for offer redemption.

**Security Features:**
- Unique random code generation
- HMAC signature for validation
- Time-limited validity (24h default)
- One-time use enforcement
- Merchant verification

**Generation Flow:**

```php
function generateSecureQRCode($userId, $advantageId) {
    $code = Str::random(20);
    $signature = hash_hmac(
        'sha256',
        $userId . $advantageId . $code . now()->timestamp,
        config('app.qr_secret')
    );

    return QRCode::create([
        'user_id' => $userId,
        'advantage_id' => $advantageId,
        'code' => $code,
        'signature' => $signature,
        'valid_until' => now()->addHours(24),
    ]);
}
```

### 3. Analytics Engine

Real-time and historical analytics for merchants.

**Metrics Tracked:**
- Proximity alerts sent/opened
- Offer views and conversions
- Transaction volume and value
- Customer demographics
- Geospatial heatmaps

**Data Sources:**
- `proximity_alerts_log` - Alert history
- `transactions` - Completed transactions
- `user_locations` - Customer movement patterns
- `advantages` - Offer performance

**Materialized Views:**

```sql
CREATE MATERIALIZED VIEW mv_merchant_stats AS
SELECT
    m.id AS merchant_id,
    COUNT(DISTINCT t.id) AS total_transactions,
    SUM(t.discount_amount) AS total_discounts,
    AVG(r.rating) AS average_rating
FROM merchants m
LEFT JOIN transactions t ON m.id = t.merchant_id
LEFT JOIN reviews r ON m.id = r.merchant_id
GROUP BY m.id;
```

Refreshed hourly via cron job.

### 4. Authentication & Authorization

JWT-based authentication with role-based access control.

**User Types:**
- **Consumer**: Regular app users
- **Merchant**: Business owners
- **Admin**: Platform administrators

**Authentication Flow:**

1. User registers with phone number
2. OTP sent via SMS (Twilio)
3. User verifies OTP
4. JWT tokens issued (access + refresh)
5. Tokens used for API requests

**JWT Structure:**

```json
{
  "sub": "uuid-user-id",
  "role": "user",
  "iat": 1700224800,
  "exp": 1700228400
}
```

### 5. Notification System

Multi-channel notification delivery.

**Channels:**
- **Push**: Firebase Cloud Messaging (primary)
- **SMS**: Twilio (critical notifications)
- **Email**: SMTP (marketing, receipts)

**Notification Types:**
- `proximity_alert` - Nearby offer detected
- `offer_expiring` - Offer expires soon
- `qr_validated` - QR code successfully used
- `transaction_complete` - Transaction confirmed

## Database Schema

### Key Tables

**users**
- User accounts and profiles
- Authentication credentials
- Preferences and settings

**user_locations** (PostGIS)
- GPS tracking history
- Spatial geometry for proximity queries

**merchants** (PostGIS)
- Business information
- Location with spatial geometry
- Subscription details

**advantages**
- Promotional offers
- Validity and conditions
- Performance statistics

**proximity_alerts_log**
- Alert notification history
- Conversion tracking
- User engagement metrics

**qr_codes**
- Generated QR codes
- Validation status
- Usage tracking

**transactions**
- Completed redemptions
- Amount and discount details
- Points earned

## API Design

### RESTful Endpoints

**Base URL:** `https://api.freeoui.tn/api/v1`

**Authentication:**
```
Authorization: Bearer {jwt_token}
```

**Rate Limiting:**
- 1000 requests/hour per user
- 100 requests/hour for sensitive endpoints
- 10000 requests/hour for merchants (premium plan)

**Response Format:**

```json
{
  "status": "success|error",
  "data": { ... },
  "message": "Optional message",
  "meta": {
    "pagination": { ... }
  }
}
```

### Key Endpoints

**Auth:**
- `POST /auth/register` - Register user
- `POST /auth/verify-otp` - Verify OTP
- `POST /auth/login` - Login
- `POST /auth/refresh` - Refresh token

**Advantages:**
- `GET /advantages` - List offers (with filters)
- `GET /advantages/{id}` - Get offer details
- `POST /advantages` - Create offer (merchant)
- `PUT /advantages/{id}` - Update offer (merchant)

**Proximity:**
- `POST /users/location` - Update GPS location
- `PUT /users/proximity-preferences` - Update settings

**QR Codes:**
- `POST /qr-codes/generate` - Generate QR code
- `POST /qr-codes/validate` - Validate QR code

## Performance Optimizations

### Database
- PostGIS spatial indexes for proximity queries
- Redis caching for frequently accessed data
- Materialized views for complex analytics
- Query optimization with proper indexes

### API
- Response caching (Redis)
- Rate limiting to prevent abuse
- Eager loading to avoid N+1 queries
- Database connection pooling

### Mobile App
- Image caching
- Offline-first with Hive/Sqflite
- Background location tracking optimization
- Lazy loading lists

### Web Admin
- Code splitting
- Lazy loading routes
- Virtual scrolling for large lists
- Debounced search inputs

## Security Measures

### Data Protection
- HTTPS only (SSL/TLS)
- Encrypted sensitive data (AES-256)
- Password hashing (Bcrypt)
- JWT signing (RS256)

### Privacy
- Geolocation never shared with merchants
- Anonymized analytics
- GDPR compliance
- Data retention policies (GPS: 30 days)

### Application Security
- Input validation and sanitization
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF protection
- Rate limiting
- QR code HMAC signatures

## Scalability Strategy

### Horizontal Scaling
- Stateless API servers
- Load balancing (Nginx/HAProxy)
- Database read replicas
- Redis cluster for caching

### Vertical Scaling
- Optimize database queries
- Increase server resources
- Upgrade to managed services (AWS RDS, ElastiCache)

### CDN
- Static assets via Cloudflare
- Image optimization
- Global distribution

## Monitoring & Logging

### Application Monitoring
- **Sentry**: Error tracking
- **Grafana**: Metrics visualization
- **Prometheus**: Time-series data

### Infrastructure Monitoring
- Server health (CPU, RAM, Disk)
- Database performance
- API latency
- Queue depth

### Business Metrics
- Active users
- Alerts sent/opened
- Conversion rates
- Revenue per merchant

## Deployment Strategy

### Environments
- **Development**: Local Docker Compose
- **Staging**: VPS (pre-production testing)
- **Production**: Cloud provider (DigitalOcean/AWS)

### CI/CD Pipeline
1. Code pushed to GitHub
2. Automated tests run
3. Docker images built
4. Deploy to staging
5. Manual approval
6. Deploy to production
7. Health checks
8. Rollback if needed

### Zero-Downtime Deployment
- Blue-green deployment
- Database migrations run before deploy
- Health checks before routing traffic
- Gradual rollout (canary deployment)

## Future Enhancements

- **Machine Learning**: Personalized offer recommendations
- **Blockchain**: Loyalty points on blockchain
- **AR**: Augmented reality for finding stores
- **Voice**: Voice-activated search
- **Multi-region**: Expansion beyond Tunisia
- **API**: Public API for third-party integrations

---

**Version**: 1.0
**Last Updated**: November 2025
**Maintained By**: FreeOui Engineering Team
