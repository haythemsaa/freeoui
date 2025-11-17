import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/advantage.dart';
import '../services/advantage_service.dart';
import '../widgets/advantage_card.dart';
import '../widgets/common/loading_indicator.dart';
import '../widgets/common/empty_state.dart';
import '../widgets/common/error_view.dart';

class FavoritesScreen extends ConsumerStatefulWidget {
  const FavoritesScreen({super.key});

  @override
  ConsumerState<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends ConsumerState<FavoritesScreen> {
  final AdvantageService _advantageService = AdvantageService();
  List<Advantage> _favorites = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadFavorites();
  }

  Future<void> _loadFavorites() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final favorites = await _advantageService.getFavorites();
      setState(() {
        _favorites = favorites;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _removeFavorite(int advantageId) async {
    try {
      await _advantageService.removeFavorite(advantageId);
      setState(() {
        _favorites.removeWhere((a) => a.id == advantageId);
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Retiré des favoris'),
            backgroundColor: Colors.orange,
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
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Favoris'),
        backgroundColor: Colors.orange,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: LoadingIndicator());
    }

    if (_error != null) {
      return ErrorView(
        message: _error!,
        onRetry: _loadFavorites,
      );
    }

    if (_favorites.isEmpty) {
      return EmptyState(
        icon: Icons.favorite_border,
        title: 'Aucun favori',
        message: 'Vous n\'avez pas encore ajouté de favoris',
        actionLabel: 'Découvrir des avantages',
        onAction: () {
          Navigator.of(context).pop();
        },
      );
    }

    return RefreshIndicator(
      onRefresh: _loadFavorites,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _favorites.length,
        itemBuilder: (context, index) {
          final advantage = _favorites[index];
          return Dismissible(
            key: Key('favorite_${advantage.id}'),
            direction: DismissDirection.endToStart,
            background: Container(
              alignment: Alignment.centerRight,
              padding: const EdgeInsets.only(right: 20),
              decoration: BoxDecoration(
                color: Colors.red,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.delete,
                color: Colors.white,
                size: 32,
              ),
            ),
            confirmDismiss: (direction) async {
              return await showDialog(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Confirmer'),
                  content: const Text(
                    'Voulez-vous retirer cet avantage de vos favoris ?',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.of(context).pop(false),
                      child: const Text('Annuler'),
                    ),
                    TextButton(
                      onPressed: () => Navigator.of(context).pop(true),
                      child: const Text(
                        'Retirer',
                        style: TextStyle(color: Colors.red),
                      ),
                    ),
                  ],
                ),
              );
            },
            onDismissed: (direction) {
              _removeFavorite(advantage.id);
            },
            child: Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: AdvantageCard(advantage: advantage),
            ),
          );
        },
      ),
    );
  }
}
