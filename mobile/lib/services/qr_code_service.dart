import 'package:dio/dio.dart';
import '../core/network/dio_client.dart';
import '../models/qr_code.dart';

class QRCodeService {
  final Dio _dio = DioClient.instance;

  Future<QRCodeModel> generateQRCode(String advantageId) async {
    try {
      final response = await _dio.post('/qr-codes/generate', data: {
        'advantage_id': advantageId,
      });

      return QRCodeModel.fromJson(
        response.data['data']['qr_code'] as Map<String, dynamic>,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> validateQRCode({
    required String qrCode,
    required String merchantId,
    double? originalAmount,
    double? latitude,
    double? longitude,
    String? notes,
  }) async {
    try {
      final response = await _dio.post('/qr-codes/validate', data: {
        'qr_code': qrCode,
        'merchant_id': merchantId,
        if (originalAmount != null) 'original_amount': originalAmount,
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
        if (notes != null) 'notes': notes,
      });

      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<void> cancelQRCode(String qrCodeId) async {
    try {
      await _dio.delete('/qr-codes/$qrCodeId');
    } catch (e) {
      rethrow;
    }
  }

  Future<List<QRCodeModel>> getQRCodes({int page = 1}) async {
    try {
      final response = await _dio.get('/qr-codes', queryParameters: {
        'page': page,
      });

      final qrCodes = (response.data['data']['qr_codes'] as List)
          .map((json) => QRCodeModel.fromJson(json as Map<String, dynamic>))
          .toList();

      return qrCodes;
    } catch (e) {
      rethrow;
    }
  }
}
