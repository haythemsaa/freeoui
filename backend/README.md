# FreeOui Backend API

Laravel-based REST API for the FreeOui platform.

## Features

- **Authentication**: JWT-based authentication with SMS OTP verification
- **Proximity Alerts**: Geolocation-based notifications using PostGIS
- **QR Code System**: Secure QR code generation and validation
- **Analytics**: Geospatial analytics with heatmaps
- **Multi-language**: Support for French and Arabic

## Tech Stack

- Laravel 11.x
- PHP 8.3+
- PostgreSQL 16 + PostGIS
- Redis 7.x
- Laravel Queues
- Laravel Reverb (WebSockets)

## Installation

### Prerequisites

- PHP 8.3+
- Composer
- PostgreSQL 16 with PostGIS extension
- Redis

### Setup

1. **Install dependencies**
```bash
composer install
```

2. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Database setup**
```bash
php artisan migrate:fresh --seed
```

4. **Start development server**
```bash
php artisan serve
```

## API Endpoints

### Authentication
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/verify-otp` - Verify OTP code
- `POST /api/v1/auth/login` - Login
- `POST /api/v1/auth/refresh` - Refresh JWT token
- `POST /api/v1/auth/logout` - Logout

### Users
- `GET /api/v1/users/profile` - Get user profile
- `PUT /api/v1/users/profile` - Update profile
- `POST /api/v1/users/location` - Update GPS location
- `PUT /api/v1/users/proximity-preferences` - Update proximity alert settings

### Advantages
- `GET /api/v1/advantages` - List advantages with filters
- `GET /api/v1/advantages/{id}` - Get advantage details
- `POST /api/v1/advantages/{id}/favorite` - Add to favorites
- `DELETE /api/v1/advantages/{id}/favorite` - Remove from favorites

### QR Codes
- `POST /api/v1/qr-codes/generate` - Generate QR code
- `POST /api/v1/qr-codes/validate` - Validate QR code (merchant)
- `DELETE /api/v1/qr-codes/{id}` - Cancel QR code

### Merchants
- `POST /api/v1/merchants/register` - Register merchant
- `GET /api/v1/merchants/{id}` - Get merchant details
- `GET /api/v1/merchants/{id}/stats` - Get merchant statistics

## Database Schema

See `/database/migrations/` for the complete PostgreSQL schema with PostGIS extensions.

Key tables:
- `users` - Application users
- `merchants` - Business merchants
- `advantages` - Promotional offers
- `user_locations` - GPS tracking (PostGIS GEOGRAPHY)
- `proximity_alerts_log` - Alert notification history
- `qr_codes` - QR code management
- `transactions` - Completed transactions

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
```

## Queue Workers

```bash
# Start queue worker
php artisan queue:work

# With specific queue
php artisan queue:work --queue=proximity-alerts,default
```

## Scheduled Tasks

The following tasks run via Laravel's scheduler:

```bash
# Add to crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Tasks:
- Proximity alert detection (every 30 seconds)
- Expired QR code cleanup (hourly)
- Refresh materialized views (hourly)
- Send subscription renewal reminders (daily)

## API Documentation

API documentation is available at `/api/documentation` when running the application.

Generated using Swagger/OpenAPI specifications.

## Security

- All API endpoints use HTTPS only
- JWT tokens with RS256 signing
- Rate limiting per endpoint
- Input validation and sanitization
- QR code HMAC signatures
- Geolocation data never shared with third parties

## License

Proprietary - FreeOui Platform
