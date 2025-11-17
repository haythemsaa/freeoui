import 'package:dio/dio.dart';
import '../core/network/dio_client.dart';
import '../models/advantage.dart';

class AdvantageService {
  final Dio _dio = DioClient.instance;

  Future<List<Advantage>> getAdvantages({
    double? latitude,
    double? longitude,
    int? radius,
    List<int>? categoryIds,
    int? minDiscount,
    String? sort,
    int page = 1,
    int perPage = 20,
  }) async {
    try {
      final response = await _dio.get('/advantages', queryParameters: {
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
        if (radius != null) 'radius': radius,
        if (categoryIds != null && categoryIds.isNotEmpty)
          'category_ids': categoryIds,
        if (minDiscount != null) 'min_discount': minDiscount,
        if (sort != null) 'sort': sort,
        'page': page,
        'per_page': perPage,
      });

      final data = response.data['data'];
      final advantages = (data['advantages'] as List)
          .map((json) => Advantage.fromJson(json as Map<String, dynamic>))
          .toList();

      return advantages;
    } catch (e) {
      rethrow;
    }
  }

  Future<Advantage> getAdvantageById(
    String id, {
    double? latitude,
    double? longitude,
  }) async {
    try {
      final response = await _dio.get('/advantages/$id', queryParameters: {
        if (latitude != null) 'latitude': latitude,
        if (longitude != null) 'longitude': longitude,
      });

      return Advantage.fromJson(
        response.data['data']['advantage'] as Map<String, dynamic>,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<void> addToFavorites(String advantageId) async {
    try {
      await _dio.post('/advantages/$advantageId/favorite');
    } catch (e) {
      rethrow;
    }
  }

  Future<void> removeFromFavorites(String advantageId) async {
    try {
      await _dio.delete('/advantages/$advantageId/favorite');
    } catch (e) {
      rethrow;
    }
  }

  Future<List<Advantage>> getFavorites({int page = 1}) async {
    try {
      final response = await _dio.get('/favorites', queryParameters: {
        'page': page,
      });

      final data = response.data['data'];
      final favorites = (data['favorites'] as List)
          .map((json) {
            final favorite = json as Map<String, dynamic>;
            return Advantage.fromJson(
              favorite['advantage'] as Map<String, dynamic>,
            );
          })
          .toList();

      return favorites;
    } catch (e) {
      rethrow;
    }
  }

  Future<List<Category>> getCategories() async {
    try {
      final response = await _dio.get('/categories');

      final categories = (response.data['data']['categories'] as List)
          .map((json) => Category.fromJson(json as Map<String, dynamic>))
          .toList();

      return categories;
    } catch (e) {
      rethrow;
    }
  }
}
