import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// Service for caching data locally
class CacheService {
  static final CacheService _instance = CacheService._internal();
  factory CacheService() => _instance;
  CacheService._internal();

  SharedPreferences? _prefs;

  /// Initialize the cache service
  Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
  }

  /// Get cached data
  Future<T?> get<T>(String key) async {
    if (_prefs == null) await init();

    final data = _prefs!.getString(key);
    if (data == null) return null;

    try {
      final cached = jsonDecode(data);

      // Check expiration
      if (cached['expiresAt'] != null) {
        final expiresAt = DateTime.parse(cached['expiresAt']);
        if (DateTime.now().isAfter(expiresAt)) {
          // Expired, remove it
          await remove(key);
          return null;
        }
      }

      return cached['data'] as T;
    } catch (e) {
      return null;
    }
  }

  /// Set cached data with optional expiration
  Future<void> set(
    String key,
    dynamic data, {
    Duration? expiresIn,
  }) async {
    if (_prefs == null) await init();

    final cached = {
      'data': data,
      'cachedAt': DateTime.now().toIso8601String(),
      if (expiresIn != null)
        'expiresAt': DateTime.now().add(expiresIn).toIso8601String(),
    };

    await _prefs!.setString(key, jsonEncode(cached));
  }

  /// Remove cached data
  Future<void> remove(String key) async {
    if (_prefs == null) await init();
    await _prefs!.remove(key);
  }

  /// Clear all cached data
  Future<void> clear() async {
    if (_prefs == null) await init();

    // Get all keys that start with 'cache_'
    final keys = _prefs!.getKeys().where((k) => k.startsWith('cache_'));

    for (final key in keys) {
      await _prefs!.remove(key);
    }
  }

  /// Check if a key exists and is not expired
  Future<bool> has(String key) async {
    final data = await get(key);
    return data != null;
  }

  /// Get or fetch data (cache-aside pattern)
  Future<T> getOrFetch<T>(
    String key,
    Future<T> Function() fetcher, {
    Duration expiresIn = const Duration(minutes: 5),
  }) async {
    // Try to get from cache first
    final cached = await get<T>(key);
    if (cached != null) {
      return cached;
    }

    // Fetch fresh data
    final data = await fetcher();

    // Cache it
    await set(key, data, expiresIn: expiresIn);

    return data;
  }

  /// Remember data forever (no expiration)
  Future<void> forever(String key, dynamic data) async {
    await set(key, data);
  }

  /// Get cache statistics
  Future<Map<String, dynamic>> getStats() async {
    if (_prefs == null) await init();

    final keys = _prefs!.getKeys().where((k) => k.startsWith('cache_'));
    int totalSize = 0;
    int expiredCount = 0;

    for (final key in keys) {
      final data = _prefs!.getString(key);
      if (data != null) {
        totalSize += data.length;

        try {
          final cached = jsonDecode(data);
          if (cached['expiresAt'] != null) {
            final expiresAt = DateTime.parse(cached['expiresAt']);
            if (DateTime.now().isAfter(expiresAt)) {
              expiredCount++;
            }
          }
        } catch (_) {}
      }
    }

    return {
      'totalKeys': keys.length,
      'totalSize': totalSize,
      'expiredCount': expiredCount,
    };
  }

  /// Clean up expired entries
  Future<void> cleanExpired() async {
    if (_prefs == null) await init();

    final keys = _prefs!.getKeys().where((k) => k.startsWith('cache_'));

    for (final key in keys) {
      final data = _prefs!.getString(key);
      if (data != null) {
        try {
          final cached = jsonDecode(data);
          if (cached['expiresAt'] != null) {
            final expiresAt = DateTime.parse(cached['expiresAt']);
            if (DateTime.now().isAfter(expiresAt)) {
              await _prefs!.remove(key);
            }
          }
        } catch (_) {
          // Invalid data, remove it
          await _prefs!.remove(key);
        }
      }
    }
  }
}

/// Cache key constants
class CacheKeys {
  static const String advantages = 'cache_advantages';
  static const String categories = 'cache_categories';
  static const String userProfile = 'cache_user_profile';
  static const String proximitySettings = 'cache_proximity_settings';

  static String advantageDetail(int id) => 'cache_advantage_$id';
  static String merchantDetail(int id) => 'cache_merchant_$id';
}
