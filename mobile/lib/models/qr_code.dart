class QRCodeModel {
  final String id;
  final String code;
  final String? qrImageData;
  final DateTime validFrom;
  final DateTime validUntil;
  final String status;
  final String advantageTitle;
  final String merchantName;

  QRCodeModel({
    required this.id,
    required this.code,
    this.qrImageData,
    required this.validFrom,
    required this.validUntil,
    required this.status,
    required this.advantageTitle,
    required this.merchantName,
  });

  factory QRCodeModel.fromJson(Map<String, dynamic> json) {
    return QRCodeModel(
      id: json['id'] as String,
      code: json['code'] as String,
      qrImageData: json['qr_image_data'] as String?,
      validFrom: DateTime.parse(json['valid_from'] as String),
      validUntil: DateTime.parse(json['valid_until'] as String),
      status: json['status'] as String,
      advantageTitle: json['advantage']?['title'] as String? ?? '',
      merchantName: json['advantage']?['merchant_name'] as String? ?? '',
    );
  }

  bool get isValid {
    final now = DateTime.now();
    return status == 'active' &&
           validFrom.isBefore(now) &&
           validUntil.isAfter(now);
  }

  bool get isExpired {
    return DateTime.now().isAfter(validUntil);
  }

  Duration get timeRemaining {
    return validUntil.difference(DateTime.now());
  }

  String get timeRemainingDisplay {
    if (isExpired) return 'Expiré';

    final hours = timeRemaining.inHours;
    if (hours > 0) return 'Expire dans ${hours}h';

    final minutes = timeRemaining.inMinutes;
    return 'Expire dans ${minutes}min';
  }
}
