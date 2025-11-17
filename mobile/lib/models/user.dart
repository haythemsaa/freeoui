class User {
  final String id;
  final String phoneNumber;
  final String firstName;
  final String lastName;
  final String? email;
  final String? avatarUrl;
  final String preferredLanguage;
  final int pointsBalance;
  final int level;
  final double totalSavingsTnd;
  final bool proximityAlertsEnabled;

  User({
    required this.id,
    required this.phoneNumber,
    required this.firstName,
    required this.lastName,
    this.email,
    this.avatarUrl,
    required this.preferredLanguage,
    required this.pointsBalance,
    required this.level,
    required this.totalSavingsTnd,
    required this.proximityAlertsEnabled,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as String,
      phoneNumber: json['phone_number'] as String,
      firstName: json['first_name'] as String,
      lastName: json['last_name'] as String,
      email: json['email'] as String?,
      avatarUrl: json['avatar_url'] as String?,
      preferredLanguage: json['preferred_language'] as String,
      pointsBalance: json['points_balance'] as int? ?? 0,
      level: json['level'] as int? ?? 1,
      totalSavingsTnd: (json['total_savings_tnd'] as num?)?.toDouble() ?? 0.0,
      proximityAlertsEnabled: json['proximity_alerts_enabled'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'phone_number': phoneNumber,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'avatar_url': avatarUrl,
      'preferred_language': preferredLanguage,
      'points_balance': pointsBalance,
      'level': level,
      'total_savings_tnd': totalSavingsTnd,
      'proximity_alerts_enabled': proximityAlertsEnabled,
    };
  }

  String get fullName => '$firstName $lastName';

  User copyWith({
    String? id,
    String? phoneNumber,
    String? firstName,
    String? lastName,
    String? email,
    String? avatarUrl,
    String? preferredLanguage,
    int? pointsBalance,
    int? level,
    double? totalSavingsTnd,
    bool? proximityAlertsEnabled,
  }) {
    return User(
      id: id ?? this.id,
      phoneNumber: phoneNumber ?? this.phoneNumber,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      email: email ?? this.email,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      preferredLanguage: preferredLanguage ?? this.preferredLanguage,
      pointsBalance: pointsBalance ?? this.pointsBalance,
      level: level ?? this.level,
      totalSavingsTnd: totalSavingsTnd ?? this.totalSavingsTnd,
      proximityAlertsEnabled: proximityAlertsEnabled ?? this.proximityAlertsEnabled,
    );
  }
}
