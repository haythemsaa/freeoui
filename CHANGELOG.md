# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased] - 2024-11-17

### Added - Backend Improvements

#### Middleware & Security
- **JwtMiddleware**: JWT authentication middleware for user routes
  - Token validation and expiration checking
  - Automatic user resolution from JWT payload
  - Error handling with proper HTTP status codes

- **MerchantJwtMiddleware**: Dedicated JWT middleware for merchant authentication
  - Type-specific token validation (merchant vs user)
  - Active merchant check
  - Enhanced security with token type verification

#### Form Validation
- **CreateAdvantageRequest**: Comprehensive form validation for advantage creation
  - Multi-type discount validation (percentage, fixed_amount, 2for1, free_item)
  - Date range validation
  - Days availability validation
  - Custom French error messages
  - Automatic HTTP 422 responses on validation failure

- **UpdateLocationRequest**: Location update validation
  - Latitude/longitude range validation (-90/90, -180/180)
  - Optional fields for accuracy, altitude, speed, heading
  - Source tracking (foreground vs background)

#### Database Seeders
- **CategorySeeder**: 10 predefined categories with icons and colors
  - Restaurant, Shopping, Loisirs, Beauté, Santé, Sport, Éducation, Services, Voyage, Divertissement
  - Bilingual support (French/Arabic)

- **MerchantSeeder**: 5 sample merchants across Tunisia
  - Real coordinates for Tunis, Sfax, Sousse, Ariana
  - Different subscription tiers (basic, premium)
  - PostGIS geometry integration

- **UserSeeder**: 3 test users with different profiles
  - Various points balances and levels (silver, gold, platinum)
  - Phone verification pre-completed

- **AdvantageSeeder**: 5 diverse promotional offers
  - Different discount types and time restrictions
  - Realistic usage limits and conversion tracking
  - Bilingual content

#### Background Jobs
- **SendProximityAlertJob**: Asynchronous proximity alert delivery
  - Queue-based push notification sending
  - Retry mechanism (3 attempts)
  - Comprehensive logging
  - Graceful error handling

### Added - Web Admin Improvements

#### UI Components Library
- **Button**: Reusable button component with variants
  - Variants: primary, secondary, outline, ghost, danger
  - Sizes: sm, md, lg
  - Loading state support
  - Full accessibility

- **Card System**: Complete card component set
  - Card, CardHeader, CardTitle, CardDescription
  - CardContent, CardFooter
  - Consistent spacing and styling

- **Input**: Form input with validation
  - Label and error message support
  - Required field indicator
  - Helper text
  - Focus states

- **Select**: Dropdown select component
  - Options array support
  - Validation and error states
  - Placeholder handling

- **Textarea**: Multi-line text input
  - Resize control
  - Character counting support
  - Validation integration

- **Modal**: Flexible modal dialog system
  - Sizes: sm, md, lg, xl
  - Backdrop click handling
  - Header with close button
  - Content area

#### Feature Components
- **AdvantageFormModal**: Complete advantage creation/editing form
  - Dynamic form based on discount type
  - Days of week selection with visual buttons
  - Category dropdown integration
  - Time range pickers
  - Real-time validation
  - Create and update modes
  - React Query integration for data fetching

#### Enhanced Pages
- **AdvantagesPage**: Updated with modal integration
  - Create and edit functionality
  - Improved button components
  - State management for modal visibility
  - Edit mode with pre-populated data

- **AnalyticsPageAdvanced**: Complete analytics dashboard
  - **Daily Scans Line Chart**: Trend visualization over time
  - **Top 10 Offers Bar Chart**: Performance ranking with conversion rates
  - **Hourly Distribution**: Peak hours analysis
  - **Category Distribution Pie Chart**: Usage breakdown by category
  - **Performance Metrics Cards**: Key statistics summary
  - **Date Range Selector**: Custom period analysis
  - Recharts integration with responsive design
  - Color-coded visualizations

### Added - Mobile Improvements

#### Common Widgets
- **LoadingIndicator**: Customizable loading spinner
  - Size and color customization
  - Center alignment by default

- **FullScreenLoading**: Full-page loading state
  - Optional message display
  - Consistent branding

- **EmptyState**: Empty list placeholder
  - Customizable icon, title, message
  - Optional action button
  - Centered layout

- **ErrorView**: Error state display
  - Error message display
  - Retry button support
  - User-friendly design

- **CustomButton**: Enhanced button widget
  - 4 variants: primary, secondary, outline, text
  - Loading state with spinner
  - Icon support
  - Disabled state handling
  - Width customization

- **CustomTextField**: Improved text input
  - Label and hint support
  - Prefix/suffix icons
  - Error message display
  - Character counting
  - Input formatters support
  - Tap callback

#### Provider Fixes
- **location_provider.dart**: Re-export file for backward compatibility
  - Maintains API consistency
  - Prevents breaking changes

### Technical Improvements

#### Backend
- JWT middleware now properly handles token expiration
- Form validation centralized in Request classes
- Database seeding for faster development and testing
- Background job infrastructure for scalable notifications
- Type-safe token validation for merchants and users

#### Web Admin
- Component-based architecture for better reusability
- Consistent design system with Tailwind CSS
- React Hook Form integration for better form handling
- Zod schema validation
- React Query for efficient data fetching
- Recharts for professional data visualization

#### Mobile
- Widget library for consistent UI
- Improved error handling
- Better loading states
- More accessible components
- Code reusability improvements

### Developer Experience

#### Backend
- Automatic validation error responses
- French error messages for better UX
- Seeded database for quick testing
- Background job monitoring support

#### Web Admin
- TypeScript strict mode compliance
- Reusable component library
- Consistent prop interfaces
- Better code organization

#### Mobile
- Common widget library
- Consistent styling
- Better state management
- Improved code structure

### Files Added/Modified

#### Backend (10 new files)
- app/Http/Middleware/JwtMiddleware.php
- app/Http/Middleware/MerchantJwtMiddleware.php
- app/Http/Requests/CreateAdvantageRequest.php
- app/Http/Requests/UpdateLocationRequest.php
- app/Jobs/SendProximityAlertJob.php
- database/seeders/DatabaseSeeder.php
- database/seeders/CategorySeeder.php
- database/seeders/MerchantSeeder.php
- database/seeders/UserSeeder.php
- database/seeders/AdvantageSeeder.php
- database/seeders/GovernorateSeeder.php

#### Web Admin (13 new files)
- src/components/ui/Button.tsx
- src/components/ui/Card.tsx
- src/components/ui/Input.tsx
- src/components/ui/Select.tsx
- src/components/ui/Textarea.tsx
- src/components/ui/Modal.tsx
- src/components/advantages/AdvantageFormModal.tsx
- src/pages/AnalyticsPageAdvanced.tsx
- src/pages/AdvantagesPage.tsx (modified)

#### Mobile (6 new files)
- lib/widgets/common/loading_indicator.dart
- lib/widgets/common/empty_state.dart
- lib/widgets/common/error_view.dart
- lib/widgets/common/custom_button.dart
- lib/widgets/common/custom_text_field.dart
- lib/providers/location_provider.dart

### Next Steps

1. **Testing**: Add unit and integration tests
2. **Documentation**: API documentation with Swagger/OpenAPI
3. **Performance**: Implement caching strategies
4. **Monitoring**: Add application monitoring (Sentry, etc.)
5. **CI/CD**: Complete GitHub Actions pipeline
6. **Deployment**: Production deployment scripts

### Breaking Changes

None - All changes are backward compatible.

### Migration Guide

#### Using New Form Requests
```php
// Old way
$request->validate([...]);

// New way
public function store(CreateAdvantageRequest $request) {
    $validated = $request->validated();
}
```

#### Using New Middleware
```php
// In routes/api.php
Route::middleware(['jwt'])->group(function () {
    // User routes
});

Route::middleware(['merchant.jwt'])->group(function () {
    // Merchant routes
});
```

#### Using New Widgets (Mobile)
```dart
// Old way
Center(child: CircularProgressIndicator())

// New way
LoadingIndicator()

// Or with customization
LoadingIndicator(size: 60, color: Colors.blue)
```

---

## [1.0.0] - 2024-11-17

### Initial Release
- Complete FreeOui platform implementation
- Laravel backend with PostGIS
- Flutter mobile application
- React web admin portal
- Docker infrastructure
- Comprehensive documentation
