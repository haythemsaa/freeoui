import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'dart:async';

/// Enhanced location state
class LocationState {
  final Position? position;
  final bool isLoading;
  final String? error;
  final bool isTracking;
  final DateTime? lastUpdate;

  LocationState({
    this.position,
    this.isLoading = false,
    this.error,
    this.isTracking = false,
    this.lastUpdate,
  });

  LocationState copyWith({
    Position? position,
    bool? isLoading,
    String? error,
    bool? isTracking,
    DateTime? lastUpdate,
  }) {
    return LocationState(
      position: position ?? this.position,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      isTracking: isTracking ?? this.isTracking,
      lastUpdate: lastUpdate ?? this.lastUpdate,
    );
  }
}

/// Enhanced location provider with better error handling and tracking
class EnhancedLocationNotifier extends StateNotifier<LocationState> {
  StreamSubscription<Position>? _positionStreamSubscription;
  Timer? _timeoutTimer;

  EnhancedLocationNotifier() : super(LocationState());

  /// Check and request location permissions
  Future<bool> checkPermissions() async {
    bool serviceEnabled;
    LocationPermission permission;

    // Check if location services are enabled
    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      state = state.copyWith(
        error: 'Les services de localisation sont désactivés',
        isLoading: false,
      );
      return false;
    }

    // Check permission status
    permission = await Geolocator.checkPermission();

    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        state = state.copyWith(
          error: 'Permission de localisation refusée',
          isLoading: false,
        );
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      state = state.copyWith(
        error: 'Permission de localisation refusée définitivement',
        isLoading: false,
      );
      return false;
    }

    return true;
  }

  /// Get current position once
  Future<Position?> getCurrentPosition({
    Duration timeout = const Duration(seconds: 15),
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final hasPermission = await checkPermissions();
      if (!hasPermission) {
        return null;
      }

      // Set timeout
      _timeoutTimer?.cancel();
      _timeoutTimer = Timer(timeout, () {
        if (state.isLoading) {
          state = state.copyWith(
            error: 'Délai d\'attente de localisation expiré',
            isLoading: false,
          );
        }
      });

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: timeout,
      );

      _timeoutTimer?.cancel();

      state = state.copyWith(
        position: position,
        isLoading: false,
        error: null,
        lastUpdate: DateTime.now(),
      );

      return position;
    } catch (e) {
      _timeoutTimer?.cancel();

      state = state.copyWith(
        error: 'Erreur de localisation: ${e.toString()}',
        isLoading: false,
      );

      return null;
    }
  }

  /// Start tracking location updates
  Future<void> startTracking({
    int distanceFilter = 50,
    int timeInterval = 60,
  }) async {
    if (state.isTracking) {
      return;
    }

    final hasPermission = await checkPermissions();
    if (!hasPermission) {
      return;
    }

    try {
      final locationSettings = LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: distanceFilter,
        timeLimit: Duration(seconds: timeInterval),
      );

      _positionStreamSubscription = Geolocator.getPositionStream(
        locationSettings: locationSettings,
      ).listen(
        (Position position) {
          state = state.copyWith(
            position: position,
            isTracking: true,
            error: null,
            lastUpdate: DateTime.now(),
          );
        },
        onError: (error) {
          state = state.copyWith(
            error: 'Erreur de suivi: ${error.toString()}',
            isTracking: false,
          );
        },
      );

      state = state.copyWith(isTracking: true, error: null);
    } catch (e) {
      state = state.copyWith(
        error: 'Impossible de démarrer le suivi: ${e.toString()}',
        isTracking: false,
      );
    }
  }

  /// Stop tracking location updates
  void stopTracking() {
    _positionStreamSubscription?.cancel();
    _positionStreamSubscription = null;
    _timeoutTimer?.cancel();
    _timeoutTimer = null;

    state = state.copyWith(isTracking: false);
  }

  /// Calculate distance between two positions
  double calculateDistance(
    double startLatitude,
    double startLongitude,
    double endLatitude,
    double endLongitude,
  ) {
    return Geolocator.distanceBetween(
      startLatitude,
      startLongitude,
      endLatitude,
      endLongitude,
    );
  }

  /// Check if position is within radius
  bool isWithinRadius(
    Position userPosition,
    double targetLatitude,
    double targetLongitude,
    double radiusMeters,
  ) {
    final distance = calculateDistance(
      userPosition.latitude,
      userPosition.longitude,
      targetLatitude,
      targetLongitude,
    );

    return distance <= radiusMeters;
  }

  /// Get accuracy status
  String getAccuracyStatus() {
    if (state.position == null) {
      return 'Aucune position';
    }

    final accuracy = state.position!.accuracy;

    if (accuracy < 10) {
      return 'Excellente';
    } else if (accuracy < 50) {
      return 'Bonne';
    } else if (accuracy < 100) {
      return 'Moyenne';
    } else {
      return 'Faible';
    }
  }

  /// Format distance for display
  String formatDistance(double meters) {
    if (meters < 1000) {
      return '${meters.round()} m';
    } else {
      return '${(meters / 1000).toStringAsFixed(1)} km';
    }
  }

  @override
  void dispose() {
    stopTracking();
    super.dispose();
  }
}

/// Enhanced location provider
final enhancedLocationProvider =
    StateNotifierProvider<EnhancedLocationNotifier, LocationState>((ref) {
  return EnhancedLocationNotifier();
});

/// Convenience provider for current position
final currentPositionProvider = Provider<Position?>((ref) {
  return ref.watch(enhancedLocationProvider).position;
});

/// Convenience provider for location error
final locationErrorProvider = Provider<String?>((ref) {
  return ref.watch(enhancedLocationProvider).error;
});

/// Convenience provider for tracking status
final isTrackingLocationProvider = Provider<bool>((ref) {
  return ref.watch(enhancedLocationProvider).isTracking;
});
