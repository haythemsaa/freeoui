class ProximityPreference {
  final bool proximityAlertsEnabled;
  final int proximityRadiusMeters;
  final List<int> interestedCategoryIds;
  final String? quietHoursStart;
  final String? quietHoursEnd;
  final int maxDailyNotifications;
  final bool notifyOnlyWhenMoving;
  final bool notifyForFeaturedOnly;
  final int minimumDiscountPercentage;

  ProximityPreference({
    required this.proximityAlertsEnabled,
    required this.proximityRadiusMeters,
    required this.interestedCategoryIds,
    this.quietHoursStart,
    this.quietHoursEnd,
    required this.maxDailyNotifications,
    required this.notifyOnlyWhenMoving,
    required this.notifyForFeaturedOnly,
    required this.minimumDiscountPercentage,
  });

  factory ProximityPreference.fromJson(Map<String, dynamic> json) {
    return ProximityPreference(
      proximityAlertsEnabled: json['proximity_alerts_enabled'] as bool,
      proximityRadiusMeters: json['proximity_radius_meters'] as int,
      interestedCategoryIds: (json['interested_category_ids'] as List<dynamic>).cast<int>(),
      quietHoursStart: json['quiet_hours_start'] as String?,
      quietHoursEnd: json['quiet_hours_end'] as String?,
      maxDailyNotifications: json['max_daily_notifications'] as int,
      notifyOnlyWhenMoving: json['notify_only_when_moving'] as bool? ?? false,
      notifyForFeaturedOnly: json['notify_for_featured_only'] as bool? ?? false,
      minimumDiscountPercentage: json['minimum_discount_percentage'] as int? ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'proximity_alerts_enabled': proximityAlertsEnabled,
      'proximity_radius_meters': proximityRadiusMeters,
      'interested_category_ids': interestedCategoryIds,
      'quiet_hours_start': quietHoursStart,
      'quiet_hours_end': quietHoursEnd,
      'max_daily_notifications': maxDailyNotifications,
      'notify_only_when_moving': notifyOnlyWhenMoving,
      'notify_for_featured_only': notifyForFeaturedOnly,
      'minimum_discount_percentage': minimumDiscountPercentage,
    };
  }

  ProximityPreference copyWith({
    bool? proximityAlertsEnabled,
    int? proximityRadiusMeters,
    List<int>? interestedCategoryIds,
    String? quietHoursStart,
    String? quietHoursEnd,
    int? maxDailyNotifications,
    bool? notifyOnlyWhenMoving,
    bool? notifyForFeaturedOnly,
    int? minimumDiscountPercentage,
  }) {
    return ProximityPreference(
      proximityAlertsEnabled: proximityAlertsEnabled ?? this.proximityAlertsEnabled,
      proximityRadiusMeters: proximityRadiusMeters ?? this.proximityRadiusMeters,
      interestedCategoryIds: interestedCategoryIds ?? this.interestedCategoryIds,
      quietHoursStart: quietHoursStart ?? this.quietHoursStart,
      quietHoursEnd: quietHoursEnd ?? this.quietHoursEnd,
      maxDailyNotifications: maxDailyNotifications ?? this.maxDailyNotifications,
      notifyOnlyWhenMoving: notifyOnlyWhenMoving ?? this.notifyOnlyWhenMoving,
      notifyForFeaturedOnly: notifyForFeaturedOnly ?? this.notifyForFeaturedOnly,
      minimumDiscountPercentage: minimumDiscountPercentage ?? this.minimumDiscountPercentage,
    );
  }

  String get radiusDisplay {
    if (proximityRadiusMeters < 1000) {
      return '${proximityRadiusMeters}m';
    }
    return '${proximityRadiusMeters ~/ 1000}km';
  }
}
