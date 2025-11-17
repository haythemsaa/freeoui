import 'package:dio/dio.dart';
import '../core/network/dio_client.dart';
import '../models/proximity_preference.dart';

class ProximityService {
  final Dio _dio = DioClient.instance;

  Future<void> updateLocation({
    required double latitude,
    required double longitude,
    double? accuracyMeters,
    double? altitude,
    double? speedMps,
    double? headingDegrees,
    String source = 'app',
  }) async {
    try {
      await _dio.post('/proximity/location', data: {
        'latitude': latitude,
        'longitude': longitude,
        if (accuracyMeters != null) 'accuracy_meters': accuracyMeters,
        if (altitude != null) 'altitude': altitude,
        if (speedMps != null) 'speed_mps': speedMps,
        if (headingDegrees != null) 'heading_degrees': headingDegrees,
        'source': source,
      });
    } catch (e) {
      rethrow;
    }
  }

  Future<ProximityPreference> getPreferences() async {
    try {
      final response = await _dio.get('/proximity/preferences');
      return ProximityPreference.fromJson(
        response.data['data']['preferences'] as Map<String, dynamic>,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> updatePreferences(
    ProximityPreference preferences,
  ) async {
    try {
      final response = await _dio.put(
        '/proximity/preferences',
        data: preferences.toJson(),
      );

      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<List<dynamic>> getAlertsHistory({int page = 1}) async {
    try {
      final response = await _dio.get('/proximity/alerts', queryParameters: {
        'page': page,
      });

      return response.data['data']['alerts'] as List<dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<void> markAlertOpened(String alertId) async {
    try {
      await _dio.post('/proximity/alerts/$alertId/opened');
    } catch (e) {
      rethrow;
    }
  }
}
