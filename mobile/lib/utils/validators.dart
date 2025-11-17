/// Validation utilities for form inputs
class Validators {
  /// Validate email format
  static String? email(String? value) {
    if (value == null || value.isEmpty) {
      return 'L\'email est requis';
    }

    final emailRegex = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');
    if (!emailRegex.hasMatch(value)) {
      return 'Email invalide';
    }

    return null;
  }

  /// Validate Tunisian phone number
  static String? phoneNumber(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le numéro de téléphone est requis';
    }

    // Remove spaces and country code if present
    final cleaned = value.replaceAll(RegExp(r'[\s\-\+]'), '');

    // Accept formats: 20123456, 50123456, 90123456, etc.
    // Or with country code: 21620123456
    final phoneRegex = RegExp(r'^(216)?[2-9]\d{7}$');

    if (!phoneRegex.hasMatch(cleaned)) {
      return 'Numéro de téléphone invalide';
    }

    return null;
  }

  /// Validate password strength
  /// At least 8 characters, one uppercase, one lowercase, one number
  static String? password(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le mot de passe est requis';
    }

    if (value.length < 8) {
      return 'Le mot de passe doit contenir au moins 8 caractères';
    }

    if (!value.contains(RegExp(r'[A-Z]'))) {
      return 'Le mot de passe doit contenir au moins une majuscule';
    }

    if (!value.contains(RegExp(r'[a-z]'))) {
      return 'Le mot de passe doit contenir au moins une minuscule';
    }

    if (!value.contains(RegExp(r'[0-9]'))) {
      return 'Le mot de passe doit contenir au moins un chiffre';
    }

    return null;
  }

  /// Validate required field
  static String? required(String? value, {String? fieldName}) {
    if (value == null || value.trim().isEmpty) {
      return '${fieldName ?? 'Ce champ'} est requis';
    }
    return null;
  }

  /// Validate minimum length
  static String? minLength(String? value, int length, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return null; // Use required validator separately
    }

    if (value.length < length) {
      return '${fieldName ?? 'Ce champ'} doit contenir au moins $length caractères';
    }

    return null;
  }

  /// Validate maximum length
  static String? maxLength(String? value, int length, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return null;
    }

    if (value.length > length) {
      return '${fieldName ?? 'Ce champ'} ne peut pas dépasser $length caractères';
    }

    return null;
  }

  /// Validate numeric value
  static String? numeric(String? value, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return null;
    }

    if (double.tryParse(value) == null) {
      return '${fieldName ?? 'Ce champ'} doit être un nombre';
    }

    return null;
  }

  /// Validate numeric range
  static String? range(
    String? value,
    double min,
    double max, {
    String? fieldName,
  }) {
    if (value == null || value.isEmpty) {
      return null;
    }

    final number = double.tryParse(value);
    if (number == null) {
      return '${fieldName ?? 'Ce champ'} doit être un nombre';
    }

    if (number < min || number > max) {
      return '${fieldName ?? 'Ce champ'} doit être entre $min et $max';
    }

    return null;
  }

  /// Validate OTP code (6 digits)
  static String? otpCode(String? value) {
    if (value == null || value.isEmpty) {
      return 'Le code OTP est requis';
    }

    if (value.length != 6) {
      return 'Le code OTP doit contenir 6 chiffres';
    }

    if (!RegExp(r'^\d{6}$').hasMatch(value)) {
      return 'Le code OTP doit contenir uniquement des chiffres';
    }

    return null;
  }

  /// Validate latitude
  static String? latitude(String? value) {
    if (value == null || value.isEmpty) {
      return null;
    }

    final number = double.tryParse(value);
    if (number == null) {
      return 'Latitude invalide';
    }

    if (number < -90 || number > 90) {
      return 'La latitude doit être entre -90 et 90';
    }

    return null;
  }

  /// Validate longitude
  static String? longitude(String? value) {
    if (value == null || value.isEmpty) {
      return null;
    }

    final number = double.tryParse(value);
    if (number == null) {
      return 'Longitude invalide';
    }

    if (number < -180 || number > 180) {
      return 'La longitude doit être entre -180 et 180';
    }

    return null;
  }

  /// Combine multiple validators
  static String? Function(String?) combine(
    List<String? Function(String?)> validators,
  ) {
    return (String? value) {
      for (final validator in validators) {
        final result = validator(value);
        if (result != null) {
          return result;
        }
      }
      return null;
    };
  }
}
