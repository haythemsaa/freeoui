class Advantage {
  final String id;
  final String title;
  final String? shortDescription;
  final String? description;
  final String type;
  final double? discountPercentage;
  final double? discountAmount;
  final String? mainImageUrl;
  final List<String> images;
  final DateTime startDate;
  final DateTime endDate;
  final String status;
  final bool isFeatured;
  final bool isExclusive;
  final int viewsCount;
  final int savesCount;
  final int usesCount;
  final double? conversionRate;
  final Merchant merchant;
  final Category category;
  final double? distanceMeters;
  final bool isFavorited;

  Advantage({
    required this.id,
    required this.title,
    this.shortDescription,
    this.description,
    required this.type,
    this.discountPercentage,
    this.discountAmount,
    this.mainImageUrl,
    required this.images,
    required this.startDate,
    required this.endDate,
    required this.status,
    required this.isFeatured,
    required this.isExclusive,
    required this.viewsCount,
    required this.savesCount,
    required this.usesCount,
    this.conversionRate,
    required this.merchant,
    required this.category,
    this.distanceMeters,
    this.isFavorited = false,
  });

  factory Advantage.fromJson(Map<String, dynamic> json) {
    return Advantage(
      id: json['id'] as String,
      title: json['title'] as String,
      shortDescription: json['short_description'] as String?,
      description: json['description'] as String?,
      type: json['type'] as String,
      discountPercentage: (json['discount_percentage'] as num?)?.toDouble(),
      discountAmount: (json['discount_amount'] as num?)?.toDouble(),
      mainImageUrl: json['main_image_url'] as String?,
      images: (json['images'] as List<dynamic>?)?.cast<String>() ?? [],
      startDate: DateTime.parse(json['start_date'] as String),
      endDate: DateTime.parse(json['end_date'] as String),
      status: json['status'] as String,
      isFeatured: json['is_featured'] as bool,
      isExclusive: json['is_exclusive'] as bool? ?? false,
      viewsCount: json['views_count'] as int? ?? 0,
      savesCount: json['saves_count'] as int? ?? 0,
      usesCount: json['uses_count'] as int? ?? 0,
      conversionRate: (json['conversion_rate'] as num?)?.toDouble(),
      merchant: Merchant.fromJson(json['merchant'] as Map<String, dynamic>),
      category: Category.fromJson(json['category'] as Map<String, dynamic>),
      distanceMeters: (json['distance_meters'] as num?)?.toDouble(),
      isFavorited: json['is_favorited'] as bool? ?? false,
    );
  }

  String get discountDisplay {
    if (type == 'percentage' && discountPercentage != null) {
      return '-${discountPercentage!.toInt()}%';
    } else if (type == 'fixed_amount' && discountAmount != null) {
      return '-${discountAmount!.toStringAsFixed(3)} TND';
    }
    return 'Offre spéciale';
  }

  String get distanceDisplay {
    if (distanceMeters == null) return '';
    if (distanceMeters! < 1000) {
      return '${distanceMeters!.toInt()}m';
    }
    return '${(distanceMeters! / 1000).toStringAsFixed(1)}km';
  }

  bool get isActive {
    final now = DateTime.now();
    return status == 'active' &&
           startDate.isBefore(now) &&
           endDate.isAfter(now);
  }
}

class Merchant {
  final String id;
  final String businessName;
  final String? logoUrl;
  final String? coverImageUrl;
  final double latitude;
  final double longitude;
  final String? addressLine1;
  final Category? category;
  final double? averageRating;
  final int? reviewsCount;

  Merchant({
    required this.id,
    required this.businessName,
    this.logoUrl,
    this.coverImageUrl,
    required this.latitude,
    required this.longitude,
    this.addressLine1,
    this.category,
    this.averageRating,
    this.reviewsCount,
  });

  factory Merchant.fromJson(Map<String, dynamic> json) {
    return Merchant(
      id: json['id'] as String,
      businessName: json['business_name'] as String,
      logoUrl: json['logo_url'] as String?,
      coverImageUrl: json['cover_image_url'] as String?,
      latitude: (json['latitude'] as num).toDouble(),
      longitude: (json['longitude'] as num).toDouble(),
      addressLine1: json['address_line1'] as String?,
      category: json['category'] != null
          ? Category.fromJson(json['category'] as Map<String, dynamic>)
          : null,
      averageRating: (json['average_rating'] as num?)?.toDouble(),
      reviewsCount: json['reviews_count'] as int?,
    );
  }
}

class Category {
  final int id;
  final String nameFr;
  final String? nameAr;
  final String slug;
  final String? icon;
  final String? color;

  Category({
    required this.id,
    required this.nameFr,
    this.nameAr,
    required this.slug,
    this.icon,
    this.color,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] as int,
      nameFr: json['name_fr'] as String,
      nameAr: json['name_ar'] as String?,
      slug: json['slug'] as String,
      icon: json['icon'] as String?,
      color: json['color'] as String?,
    );
  }

  String getName(String language) {
    return language == 'ar' && nameAr != null ? nameAr! : nameFr;
  }
}
