import 'package:dio/dio.dart';
import '../core/network/dio_client.dart';
import '../models/user.dart';

class AuthService {
  final Dio _dio = DioClient.instance;

  Future<Map<String, dynamic>> register({
    required String phoneNumber,
    required String countryCode,
    required String firstName,
    required String lastName,
    required int cityId,
    required String language,
  }) async {
    try {
      final response = await _dio.post('/auth/register', data: {
        'phone_number': phoneNumber,
        'country_code': countryCode,
        'first_name': firstName,
        'last_name': lastName,
        'city_id': cityId,
        'language': language,
        'accepts_terms': true,
      });

      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> verifyOTP({
    required String phoneNumber,
    required String otpCode,
  }) async {
    try {
      final response = await _dio.post('/auth/verify-otp', data: {
        'phone_number': phoneNumber,
        'otp_code': otpCode,
      });

      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> login({
    required String phoneNumber,
    String? password,
    String? otpCode,
  }) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'phone_number': phoneNumber,
        if (password != null) 'password': password,
        if (otpCode != null) 'otp_code': otpCode,
      });

      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<void> requestOTP(String phoneNumber) async {
    try {
      await _dio.post('/auth/request-otp', data: {
        'phone_number': phoneNumber,
      });
    } catch (e) {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> refreshToken(String refreshToken) async {
    try {
      final response = await _dio.post('/auth/refresh', data: {
        'refresh_token': refreshToken,
      });

      return response.data['data'] as Map<String, dynamic>;
    } catch (e) {
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } catch (e) {
      rethrow;
    }
  }

  Future<User> getProfile() async {
    try {
      final response = await _dio.get('/users/profile');
      return User.fromJson(response.data['data']['user'] as Map<String, dynamic>);
    } catch (e) {
      rethrow;
    }
  }

  Future<User> updateProfile(Map<String, dynamic> data) async {
    try {
      final response = await _dio.put('/users/profile', data: data);
      return User.fromJson(response.data['data']['user'] as Map<String, dynamic>);
    } catch (e) {
      rethrow;
    }
  }

  Future<void> updateFCMToken(String token) async {
    try {
      await _dio.post('/users/fcm-token', data: {
        'fcm_token': token,
      });
    } catch (e) {
      rethrow;
    }
  }
}
