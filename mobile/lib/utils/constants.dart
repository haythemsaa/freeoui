/// Application-wide constants

class AppConstants {
  // API Configuration
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8000/api/v1',
  );

  static const String apiTimeout = '30000'; // milliseconds

  // Advantage Types
  static const String advantageTypePercentage = 'percentage';
  static const String advantageTypeFixedAmount = 'fixed_amount';
  static const String advantageTypeTwoForOne = '2for1';
  static const String advantageTypeFreeItem = 'free_item';

  // QR Code Status
  static const String qrStatusActive = 'active';
  static const String qrStatusUsed = 'used';
  static const String qrStatusExpired = 'expired';
  static const String qrStatusCancelled = 'cancelled';

  // User Levels
  static const String levelBronze = 'bronze';
  static const String levelSilver = 'silver';
  static const String levelGold = 'gold';
  static const String levelPlatinum = 'platinum';

  // Languages
  static const String languageFrench = 'fr';
  static const String languageArabic = 'ar';

  // Proximity Settings
  static const int defaultRadiusMeters = 1000;
  static const int minRadiusMeters = 500;
  static const int maxRadiusMeters = 5000;
  static const int maxAlertsPerDay = 5;
  static const int minIntervalMinutes = 30;

  // QR Code Settings
  static const int qrValidityHours = 2;
  static const int maxQrPerUserPerDay = 10;

  // Pagination
  static const int defaultPageSize = 15;
  static const int maxPageSize = 100;

  // Location Update
  static const int locationUpdateIntervalSeconds = 60;
  static const int locationMinDistanceMeters = 50;

  // Cache Duration
  static const int cacheDurationMinutes = 5;

  // Animation Durations
  static const int shortAnimationMs = 200;
  static const int mediumAnimationMs = 300;
  static const int longAnimationMs = 500;

  // Image Settings
  static const int maxImageSizeMB = 5;
  static const int imageQuality = 85;

  // Storage Keys
  static const String storageKeyToken = 'auth_token';
  static const String storageKeyRefreshToken = 'refresh_token';
  static const String storageKeyUser = 'user_data';
  static const String storageKeyLanguage = 'app_language';
  static const String storageKeyTheme = 'app_theme';

  // Push Notification Topics
  static const String notificationTopicProximity = 'proximity_alerts';
  static const String notificationTopicPromotions = 'promotions';
  static const String notificationTopicNews = 'news';

  // Error Messages
  static const String errorNetworkConnection = 'Erreur de connexion réseau';
  static const String errorServerError = 'Erreur serveur';
  static const String errorUnauthorized = 'Session expirée, veuillez vous reconnecter';
  static const String errorNotFound = 'Ressource introuvable';
  static const String errorValidation = 'Données invalides';
  static const String errorUnknown = 'Une erreur inattendue s\'est produite';

  // Success Messages
  static const String successSaved = 'Enregistré avec succès';
  static const String successUpdated = 'Mis à jour avec succès';
  static const String successDeleted = 'Supprimé avec succès';

  // Tunisia Cities IDs (matching backend seeder)
  static const Map<String, int> tunisiaCities = {
    'Tunis': 1,
    'Sfax': 2,
    'Sousse': 3,
    'Kairouan': 4,
    'Bizerte': 5,
    'Gabès': 6,
    'Ariana': 7,
    'Gafsa': 8,
    'Monastir': 9,
    'Ben Arous': 10,
  };

  // Tunisia Country Code
  static const String tunisiaCountryCode = '+216';

  // Map Settings
  static const double defaultMapZoom = 13.0;
  static const double detailMapZoom = 15.0;
  static const double tunisiaLatitude = 36.8065;
  static const double tunisiaLongitude = 10.1815;
}

class AppRoutes {
  static const String splash = '/';
  static const String login = '/login';
  static const String register = '/register';
  static const String otpVerification = '/otp-verification';
  static const String home = '/home';
  static const String advantageDetail = '/advantage-detail';
  static const String map = '/map';
  static const String qrScanner = '/qr-scanner';
  static const String profile = '/profile';
  static const String proximitySettings = '/proximity-settings';
  static const String myQrCodes = '/my-qr-codes';
  static const String favorites = '/favorites';
  static const String notifications = '/notifications';
}

class AppAssets {
  static const String logo = 'assets/images/logo.png';
  static const String logoWhite = 'assets/images/logo_white.png';
  static const String emptyState = 'assets/images/empty_state.png';
  static const String errorState = 'assets/images/error_state.png';
  static const String onboarding1 = 'assets/images/onboarding_1.png';
  static const String onboarding2 = 'assets/images/onboarding_2.png';
  static const String onboarding3 = 'assets/images/onboarding_3.png';
}

class CategoryIcons {
  static const Map<String, String> icons = {
    'Restaurant': '🍽️',
    'Café': '☕',
    'Shopping': '🛍️',
    'Sport': '⚽',
    'Beauté': '💄',
    'Santé': '⚕️',
    'Loisirs': '🎮',
    'Voyage': '✈️',
    'Éducation': '📚',
    'Services': '🔧',
  };
}
