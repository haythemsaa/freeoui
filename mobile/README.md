# FreeOui Mobile App

Flutter-based mobile application for iOS and Android.

## Features

- 📱 Cross-platform (iOS & Android)
- 🗺️ Proximity-based alerts with background geolocation
- 🔔 Push notifications (Firebase Cloud Messaging)
- 📍 Interactive maps (Mapbox/Google Maps)
- 📲 QR code generation and scanning
- 🌐 Multi-language support (French/Arabic)
- 🎨 Beautiful Material Design UI
- ⚡ Fast and responsive

## Tech Stack

- **Framework**: Flutter 3.19+
- **Language**: Dart 3.3+
- **State Management**: Riverpod 2.x
- **Networking**: Dio + Retrofit
- **Local Storage**: Sqflite + Hive
- **Maps**: Mapbox SDK / Google Maps SDK
- **Geolocation**: flutter_background_geolocation
- **Notifications**: Firebase Cloud Messaging
- **Analytics**: Firebase Analytics

## Prerequisites

- Flutter SDK 3.19 or higher
- Dart SDK 3.3 or higher
- Android Studio / Xcode
- Firebase account (for FCM)
- Mapbox account (for maps)

## Installation

1. **Install Flutter dependencies**
```bash
flutter pub get
```

2. **Configure Firebase**
   - Place `google-services.json` in `android/app/`
   - Place `GoogleService-Info.plist` in `ios/Runner/`

3. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your API keys
```

4. **Run the app**
```bash
# Development mode
flutter run

# Release mode
flutter run --release
```

## Project Structure

```
mobile/
├── lib/
│   ├── main.dart
│   ├── core/
│   │   ├── constants/
│   │   ├── theme/
│   │   ├── utils/
│   │   └── network/
│   ├── features/
│   │   ├── auth/
│   │   ├── home/
│   │   ├── advantages/
│   │   ├── proximity/
│   │   ├── qr_code/
│   │   ├── profile/
│   │   └── merchants/
│   ├── models/
│   ├── providers/
│   ├── repositories/
│   ├── services/
│   └── widgets/
├── assets/
│   ├── images/
│   ├── icons/
│   └── translations/
├── test/
└── integration_test/
```

## Key Features Implementation

### Proximity Alerts

The app uses background geolocation to track user position and detect nearby offers:

```dart
// Initialize background geolocation
await BackgroundGeolocation.ready(Config(
  desiredAccuracy: LocationAccuracy.HIGH,
  distanceFilter: 50.0,
  stopOnTerminate: false,
  startOnBoot: true,
));

// Start tracking
BackgroundGeolocation.start();
```

### Push Notifications

Firebase Cloud Messaging integration:

```dart
// Initialize FCM
await FirebaseMessaging.instance.requestPermission();
String? token = await FirebaseMessaging.instance.getToken();

// Listen to notifications
FirebaseMessaging.onMessage.listen((RemoteMessage message) {
  // Handle proximity alert
});
```

### QR Code Generation

```dart
// Generate QR code for advantage
final qrCode = await apiService.generateQRCode(advantageId);

// Display QR code
QrImage(
  data: qrCode.code,
  version: QrVersions.auto,
  size: 300.0,
);
```

## Configuration

### API Endpoints

Configure API base URL in `.env`:

```env
API_BASE_URL=https://api.freeoui.tn
API_VERSION=v1
```

### Maps

Choose between Mapbox or Google Maps:

```env
MAP_PROVIDER=mapbox  # or google_maps
MAPBOX_ACCESS_TOKEN=your_token_here
GOOGLE_MAPS_API_KEY=your_key_here
```

## Building for Production

### Android

```bash
# Build APK
flutter build apk --release

# Build App Bundle
flutter build appbundle --release
```

### iOS

```bash
# Build iOS app
flutter build ios --release

# Create archive in Xcode
open ios/Runner.xcworkspace
```

## Testing

```bash
# Run unit tests
flutter test

# Run integration tests
flutter test integration_test

# Run with coverage
flutter test --coverage
```

## Localization

The app supports French and Arabic:

```dart
// Use in widgets
Text(AppLocalizations.of(context).translate('key'))

// Available languages
const availableLocales = [
  Locale('fr', 'TN'), // French (Tunisia)
  Locale('ar', 'TN'), // Arabic (Tunisia)
];
```

## Performance

- **App size**: ~15MB (compressed)
- **Cold start**: <2s
- **Hot reload**: <1s
- **Battery usage**: Optimized for background location tracking

## Security

- API requests over HTTPS only
- JWT token storage in secure storage
- Certificate pinning for API calls
- Encrypted local database
- No sensitive data in logs

## License

Proprietary - FreeOui Platform
