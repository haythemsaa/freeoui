import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import '../models/proximity_preference.dart';
import '../services/proximity_service.dart';

final proximityServiceProvider =
    Provider<ProximityService>((ref) => ProximityService());

class LocationState {
  final Position? currentPosition;
  final bool isTracking;
  final String? error;

  const LocationState({
    this.currentPosition,
    this.isTracking = false,
    this.error,
  });

  LocationState copyWith({
    Position? currentPosition,
    bool? isTracking,
    String? error,
  }) {
    return LocationState(
      currentPosition: currentPosition ?? this.currentPosition,
      isTracking: isTracking ?? this.isTracking,
      error: error,
    );
  }
}

class ProximityState {
  final ProximityPreference? preferences;
  final List<dynamic> alertsHistory;
  final bool isLoading;
  final String? error;

  const ProximityState({
    this.preferences,
    this.alertsHistory = const [],
    this.isLoading = false,
    this.error,
  });

  ProximityState copyWith({
    ProximityPreference? preferences,
    List<dynamic>? alertsHistory,
    bool? isLoading,
    String? error,
  }) {
    return ProximityState(
      preferences: preferences ?? this.preferences,
      alertsHistory: alertsHistory ?? this.alertsHistory,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class LocationNotifier extends StateNotifier<LocationState> {
  final ProximityService _proximityService;

  LocationNotifier(this._proximityService) : super(const LocationState());

  Future<void> startTracking() async {
    try {
      // Check permissions
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          state = state.copyWith(
            error: 'Location permissions are denied',
          );
          return;
        }
      }

      if (permission == LocationPermission.deniedForever) {
        state = state.copyWith(
          error: 'Location permissions are permanently denied',
        );
        return;
      }

      state = state.copyWith(isTracking: true);

      // Get current position
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      state = state.copyWith(currentPosition: position);

      // Send to backend
      await _proximityService.updateLocation(
        latitude: position.latitude,
        longitude: position.longitude,
        accuracyMeters: position.accuracy,
        altitude: position.altitude,
        speedMps: position.speed,
        headingDegrees: position.heading,
      );

      // Listen to position updates
      Geolocator.getPositionStream(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: 50, // Update every 50 meters
        ),
      ).listen((Position position) {
        state = state.copyWith(currentPosition: position);

        // Send to backend
        _proximityService.updateLocation(
          latitude: position.latitude,
          longitude: position.longitude,
          accuracyMeters: position.accuracy,
          altitude: position.altitude,
          speedMps: position.speed,
          headingDegrees: position.heading,
        );
      });
    } catch (e) {
      state = state.copyWith(
        isTracking: false,
        error: e.toString(),
      );
      rethrow;
    }
  }

  void stopTracking() {
    state = state.copyWith(isTracking: false);
  }

  Future<Position> getCurrentPosition() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      state = state.copyWith(currentPosition: position);
      return position;
    } catch (e) {
      rethrow;
    }
  }
}

class ProximityNotifier extends StateNotifier<ProximityState> {
  final ProximityService _proximityService;

  ProximityNotifier(this._proximityService) : super(const ProximityState());

  Future<void> loadPreferences() async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final preferences = await _proximityService.getPreferences();
      state = state.copyWith(
        preferences: preferences,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  Future<void> updatePreferences(ProximityPreference preferences) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      await _proximityService.updatePreferences(preferences);
      state = state.copyWith(
        preferences: preferences,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  Future<void> loadAlertsHistory({int page = 1}) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final alerts = await _proximityService.getAlertsHistory(page: page);
      state = state.copyWith(
        alertsHistory: alerts,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  Future<void> markAlertOpened(String alertId) async {
    try {
      await _proximityService.markAlertOpened(alertId);
    } catch (e) {
      // Silent fail
    }
  }
}

final locationProvider =
    StateNotifierProvider<LocationNotifier, LocationState>((ref) {
  final proximityService = ref.watch(proximityServiceProvider);
  return LocationNotifier(proximityService);
});

final proximityProvider =
    StateNotifierProvider<ProximityNotifier, ProximityState>((ref) {
  final proximityService = ref.watch(proximityServiceProvider);
  return ProximityNotifier(proximityService);
});
