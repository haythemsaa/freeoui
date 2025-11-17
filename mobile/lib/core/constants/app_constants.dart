class AppConstants {
  // API
  static const String apiBaseUrl = 'http://localhost:8000/api';
  static const String apiVersion = 'v1';
  static const int apiTimeout = 30000;

  // Storage Keys
  static const String accessTokenKey = 'access_token';
  static const String refreshTokenKey = 'refresh_token';
  static const String userKey = 'user';
  static const String languageKey = 'language';

  // Proximity
  static const List<int> proximityRadii = [500, 1000, 2000, 5000];
  static const int defaultProximityRadius = 1000;
  static const int maxDailyNotifications = 5;
  static const int minNotificationInterval = 30; // minutes

  // QR Code
  static const int qrCodeValidityHours = 24;

  // Pagination
  static const int defaultPageSize = 20;

  // Languages
  static const String languageFrench = 'fr';
  static const String languageArabic = 'ar';

  // Country Code
  static const String countryCode = '+216';

  // Map
  static const double defaultLatitude = 36.8065;
  static const double defaultLongitude = 10.1815;
  static const double defaultZoom = 12.0;
}
