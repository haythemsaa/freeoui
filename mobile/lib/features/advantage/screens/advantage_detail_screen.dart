import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../models/advantage.dart';
import '../../../providers/advantage_provider.dart';
import '../../../providers/location_provider.dart';
import '../../qr_code/screens/qr_code_generate_screen.dart';

class AdvantageDetailScreen extends ConsumerStatefulWidget {
  final String advantageId;

  const AdvantageDetailScreen({
    super.key,
    required this.advantageId,
  });

  @override
  ConsumerState<AdvantageDetailScreen> createState() =>
      _AdvantageDetailScreenState();
}

class _AdvantageDetailScreenState
    extends ConsumerState<AdvantageDetailScreen> {
  Advantage? _advantage;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadAdvantage();
  }

  Future<void> _loadAdvantage() async {
    try {
      final location = ref.read(locationProvider).currentPosition;
      final advantage = await ref.read(advantageProvider.notifier).getAdvantageById(
            widget.advantageId,
            latitude: location?.latitude,
            longitude: location?.longitude,
          );

      setState(() {
        _advantage = advantage;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_advantage == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: Text('Offre non trouvée')),
      );
    }

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                color: Colors.grey[300],
                child: Icon(
                  Icons.image,
                  size: 80,
                  color: Colors.grey[400],
                ),
              ),
            ),
            actions: [
              IconButton(
                icon: Icon(
                  _advantage!.isFavorite
                      ? Icons.favorite
                      : Icons.favorite_border,
                ),
                onPressed: () {
                  ref
                      .read(advantageProvider.notifier)
                      .toggleFavorite(_advantage!.id, _advantage!.isFavorite);
                  setState(() {
                    _advantage = Advantage(
                      id: _advantage!.id,
                      title: _advantage!.title,
                      description: _advantage!.description,
                      type: _advantage!.type,
                      discountPercentage: _advantage!.discountPercentage,
                      discountAmount: _advantage!.discountAmount,
                      validFrom: _advantage!.validFrom,
                      validUntil: _advantage!.validUntil,
                      daysAvailable: _advantage!.daysAvailable,
                      timeFrom: _advantage!.timeFrom,
                      timeUntil: _advantage!.timeUntil,
                      merchant: _advantage!.merchant,
                      category: _advantage!.category,
                      termsConditions: _advantage!.termsConditions,
                      usageLimit: _advantage!.usageLimit,
                      usageCount: _advantage!.usageCount,
                      rating: _advantage!.rating,
                      reviewsCount: _advantage!.reviewsCount,
                      distanceMeters: _advantage!.distanceMeters,
                      isFavorite: !_advantage!.isFavorite,
                    );
                  });
                },
              ),
            ],
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Discount badge
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.primary,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      _advantage!.type == 'percentage'
                          ? '${_advantage!.discountPercentage}% de réduction'
                          : '${_advantage!.discountAmount} TND de réduction',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  // Title
                  Text(
                    _advantage!.title,
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const SizedBox(height: 8),
                  // Merchant info
                  Row(
                    children: [
                      Icon(Icons.store, color: Colors.grey[600]),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _advantage!.merchant.name,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  // Location
                  Row(
                    children: [
                      Icon(Icons.location_on, color: Colors.grey[600]),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _advantage!.merchant.address,
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ),
                      if (_advantage!.distanceMeters != null)
                        Text(
                          _advantage!.distanceDisplay,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.primary,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Stats
                  Row(
                    children: [
                      Icon(Icons.star, color: Colors.amber),
                      const SizedBox(width: 4),
                      Text(
                        '${_advantage!.rating.toStringAsFixed(1)}',
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Text(' (${_advantage!.reviewsCount} avis)'),
                      const SizedBox(width: 16),
                      Icon(Icons.people, color: Colors.grey[600]),
                      const SizedBox(width: 4),
                      Text('${_advantage!.usageCount} utilisations'),
                    ],
                  ),
                  const SizedBox(height: 24),
                  // Description
                  Text(
                    'Description',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _advantage!.description,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 24),
                  // Terms & Conditions
                  if (_advantage!.termsConditions != null) ...[
                    Text(
                      'Conditions d\'utilisation',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _advantage!.termsConditions!,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    const SizedBox(height: 24),
                  ],
                  // Validity info
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.calendar_today,
                                size: 16, color: Colors.grey[600]),
                            const SizedBox(width: 8),
                            Text(
                              'Valable jusqu\'au ${_advantage!.validUntil.day}/${_advantage!.validUntil.month}/${_advantage!.validUntil.year}',
                            ),
                          ],
                        ),
                        if (_advantage!.timeFrom != null &&
                            _advantage!.timeUntil != null) ...[
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Icon(Icons.access_time,
                                  size: 16, color: Colors.grey[600]),
                              const SizedBox(width: 8),
                              Text(
                                'De ${_advantage!.timeFrom} à ${_advantage!.timeUntil}',
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 80),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomSheet: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 10,
            ),
          ],
        ),
        child: SafeArea(
          child: ElevatedButton(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => QRCodeGenerateScreen(
                    advantageId: _advantage!.id,
                  ),
                ),
              );
            },
            child: const Text('Générer un QR Code'),
          ),
        ),
      ),
    );
  }
}
