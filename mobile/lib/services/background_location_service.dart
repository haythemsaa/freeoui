import 'package:geolocator/geolocator.dart';
import 'dart:async';
import '../core/network/dio_client.dart';

class BackgroundLocationService {
  static final BackgroundLocationService _instance =
      BackgroundLocationService._internal();

  factory BackgroundLocationService() => _instance;

  BackgroundLocationService._internal();

  StreamSubscription<Position>? _positionStreamSubscription;
  bool _isTracking = false;

  bool get isTracking => _isTracking;

  Future<bool> checkPermissions() async {
    bool serviceEnabled;
    LocationPermission permission;

    // Check if location services are enabled
    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return false;
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return false;
    }

    return true;
  }

  Future<void> startTracking() async {
    if (_isTracking) return;

    final hasPermission = await checkPermissions();
    if (!hasPermission) {
      throw Exception('Location permissions not granted');
    }

    _isTracking = true;

    // Define location settings
    const LocationSettings locationSettings = LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 50, // Update every 50 meters
    );

    // Start listening to position updates
    _positionStreamSubscription = Geolocator.getPositionStream(
      locationSettings: locationSettings,
    ).listen(
      (Position position) {
        _sendLocationUpdate(position);
      },
      onError: (error) {
        print('Location error: $error');
      },
    );
  }

  Future<void> stopTracking() async {
    if (!_isTracking) return;

    await _positionStreamSubscription?.cancel();
    _positionStreamSubscription = null;
    _isTracking = false;
  }

  Future<void> _sendLocationUpdate(Position position) async {
    try {
      final dio = DioClient.instance;
      await dio.post('/proximity/location', data: {
        'latitude': position.latitude,
        'longitude': position.longitude,
        'accuracy_meters': position.accuracy,
        'altitude': position.altitude,
        'speed_mps': position.speed,
        'heading_degrees': position.heading,
        'source': 'background',
      });
    } catch (e) {
      print('Failed to send location update: $e');
    }
  }

  Future<Position?> getCurrentPosition() async {
    final hasPermission = await checkPermissions();
    if (!hasPermission) {
      return null;
    }

    try {
      return await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
    } catch (e) {
      print('Failed to get current position: $e');
      return null;
    }
  }
}
