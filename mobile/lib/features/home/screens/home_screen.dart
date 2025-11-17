import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../providers/advantage_provider.dart';
import '../../../providers/location_provider.dart';
import '../widgets/advantage_card.dart';
import '../../map/screens/map_screen.dart';
import '../../profile/screens/profile_screen.dart';
import '../../qr_code/screens/qr_code_list_screen.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  int _currentIndex = 0;
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadInitialData();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    // Start location tracking
    ref.read(locationProvider.notifier).startTracking();

    // Load categories
    ref.read(advantageProvider.notifier).loadCategories();

    // Load advantages
    final location = ref.read(locationProvider).currentPosition;
    await ref.read(advantageProvider.notifier).loadAdvantages(
          latitude: location?.latitude,
          longitude: location?.longitude,
          radius: 5000, // 5km
        );
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent * 0.9) {
      final advantageState = ref.read(advantageProvider);
      if (!advantageState.isLoadingMore && advantageState.hasMore) {
        final location = ref.read(locationProvider).currentPosition;
        ref.read(advantageProvider.notifier).loadAdvantages(
              latitude: location?.latitude,
              longitude: location?.longitude,
              loadMore: true,
            );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final List<Widget> screens = [
      _buildHomeTab(),
      const MapScreen(),
      const QRCodeListScreen(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Accueil',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.map),
            label: 'Carte',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.qr_code),
            label: 'QR Codes',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }

  Widget _buildHomeTab() {
    final advantageState = ref.watch(advantageProvider);
    final locationState = ref.watch(locationProvider);

    return CustomScrollView(
      controller: _scrollController,
      slivers: [
        SliverAppBar(
          floating: true,
          title: const Text('FreeOui'),
          actions: [
            IconButton(
              icon: Icon(
                locationState.isTracking
                    ? Icons.location_on
                    : Icons.location_off,
              ),
              onPressed: () {
                if (locationState.isTracking) {
                  ref.read(locationProvider.notifier).stopTracking();
                } else {
                  ref.read(locationProvider.notifier).startTracking();
                }
              },
            ),
          ],
        ),
        if (advantageState.isLoading && advantageState.advantages.isEmpty)
          const SliverFillRemaining(
            child: Center(child: CircularProgressIndicator()),
          )
        else if (advantageState.error != null)
          SliverFillRemaining(
            child: Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.red),
                  const SizedBox(height: 16),
                  Text(
                    'Erreur de chargement',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    advantageState.error!,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadInitialData,
                    child: const Text('Réessayer'),
                  ),
                ],
              ),
            ),
          )
        else if (advantageState.advantages.isEmpty)
          const SliverFillRemaining(
            child: Center(
              child: Text('Aucune offre disponible'),
            ),
          )
        else
          SliverPadding(
            padding: const EdgeInsets.all(16.0),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  if (index == advantageState.advantages.length) {
                    return advantageState.isLoadingMore
                        ? const Padding(
                            padding: EdgeInsets.all(16.0),
                            child: Center(child: CircularProgressIndicator()),
                          )
                        : const SizedBox.shrink();
                  }

                  final advantage = advantageState.advantages[index];
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 16.0),
                    child: AdvantageCard(advantage: advantage),
                  );
                },
                childCount: advantageState.advantages.length +
                    (advantageState.hasMore ? 1 : 0),
              ),
            ),
          ),
      ],
    );
  }
}
