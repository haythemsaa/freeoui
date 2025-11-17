import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../models/proximity_preference.dart';
import '../../../providers/proximity_provider.dart';

class ProximitySettingsScreen extends ConsumerStatefulWidget {
  const ProximitySettingsScreen({super.key});

  @override
  ConsumerState<ProximitySettingsScreen> createState() =>
      _ProximitySettingsScreenState();
}

class _ProximitySettingsScreenState
    extends ConsumerState<ProximitySettingsScreen> {
  bool _isLoading = false;
  bool _alertsEnabled = true;
  int _radiusMeters = 1000;
  int _maxAlertsPerDay = 5;
  int _minIntervalMinutes = 30;
  List<int> _categoryIds = [];

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    try {
      await ref.read(proximityProvider.notifier).loadPreferences();
      final preferences = ref.read(proximityProvider).preferences;

      if (preferences != null) {
        setState(() {
          _alertsEnabled = preferences.alertsEnabled;
          _radiusMeters = preferences.proximityRadiusMeters;
          _maxAlertsPerDay = preferences.maxAlertsPerDay;
          _minIntervalMinutes = preferences.minIntervalMinutes;
          _categoryIds = preferences.categoryIds;
        });
      }
    } catch (e) {
      // Handle error silently
    }
  }

  Future<void> _savePreferences() async {
    setState(() => _isLoading = true);

    try {
      final preferences = ProximityPreference(
        alertsEnabled: _alertsEnabled,
        proximityRadiusMeters: _radiusMeters,
        maxAlertsPerDay: _maxAlertsPerDay,
        minIntervalMinutes: _minIntervalMinutes,
        categoryIds: _categoryIds,
      );

      await ref.read(proximityProvider.notifier).updatePreferences(preferences);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Paramètres enregistrés'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Paramètres de proximité'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SwitchListTile(
            title: const Text('Activer les alertes de proximité'),
            subtitle: const Text(
              'Recevoir des notifications pour les offres à proximité',
            ),
            value: _alertsEnabled,
            onChanged: (value) {
              setState(() => _alertsEnabled = value);
            },
          ),
          const SizedBox(height: 24),
          Text(
            'Rayon de proximité',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Text(
            '${(_radiusMeters / 1000).toStringAsFixed(1)} km',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          Slider(
            value: _radiusMeters.toDouble(),
            min: 500,
            max: 5000,
            divisions: 9,
            label: '${(_radiusMeters / 1000).toStringAsFixed(1)} km',
            onChanged: (value) {
              setState(() => _radiusMeters = value.toInt());
            },
          ),
          const SizedBox(height: 24),
          Text(
            'Nombre maximum d\'alertes par jour',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Text(
            '$_maxAlertsPerDay alertes',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          Slider(
            value: _maxAlertsPerDay.toDouble(),
            min: 1,
            max: 20,
            divisions: 19,
            label: '$_maxAlertsPerDay',
            onChanged: (value) {
              setState(() => _maxAlertsPerDay = value.toInt());
            },
          ),
          const SizedBox(height: 24),
          Text(
            'Intervalle minimum entre les alertes',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Text(
            '$_minIntervalMinutes minutes',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          Slider(
            value: _minIntervalMinutes.toDouble(),
            min: 10,
            max: 120,
            divisions: 11,
            label: '$_minIntervalMinutes min',
            onChanged: (value) {
              setState(() => _minIntervalMinutes = value.toInt());
            },
          ),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: _isLoading ? null : _savePreferences,
            child: _isLoading
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Enregistrer les paramètres'),
          ),
        ],
      ),
    );
  }
}
