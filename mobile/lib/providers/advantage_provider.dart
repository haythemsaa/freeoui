import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/advantage.dart';
import '../services/advantage_service.dart';

final advantageServiceProvider =
    Provider<AdvantageService>((ref) => AdvantageService());

class AdvantageState {
  final List<Advantage> advantages;
  final List<Advantage> favorites;
  final List<Category> categories;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final bool hasMore;

  const AdvantageState({
    this.advantages = const [],
    this.favorites = const [],
    this.categories = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.hasMore = true,
  });

  AdvantageState copyWith({
    List<Advantage>? advantages,
    List<Advantage>? favorites,
    List<Category>? categories,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    bool? hasMore,
  }) {
    return AdvantageState(
      advantages: advantages ?? this.advantages,
      favorites: favorites ?? this.favorites,
      categories: categories ?? this.categories,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      hasMore: hasMore ?? this.hasMore,
    );
  }
}

class AdvantageNotifier extends StateNotifier<AdvantageState> {
  final AdvantageService _advantageService;

  AdvantageNotifier(this._advantageService) : super(const AdvantageState());

  Future<void> loadAdvantages({
    double? latitude,
    double? longitude,
    int? radius,
    List<int>? categoryIds,
    int? minDiscount,
    String? sort,
    bool loadMore = false,
  }) async {
    if (loadMore) {
      state = state.copyWith(isLoadingMore: true);
    } else {
      state = state.copyWith(isLoading: true, error: null);
    }

    try {
      final page = loadMore ? state.currentPage + 1 : 1;

      final advantages = await _advantageService.getAdvantages(
        latitude: latitude,
        longitude: longitude,
        radius: radius,
        categoryIds: categoryIds,
        minDiscount: minDiscount,
        sort: sort,
        page: page,
      );

      if (loadMore) {
        state = state.copyWith(
          advantages: [...state.advantages, ...advantages],
          isLoadingMore: false,
          currentPage: page,
          hasMore: advantages.isNotEmpty,
        );
      } else {
        state = state.copyWith(
          advantages: advantages,
          isLoading: false,
          currentPage: page,
          hasMore: advantages.isNotEmpty,
        );
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        isLoadingMore: false,
        error: e.toString(),
      );
      rethrow;
    }
  }

  Future<Advantage> getAdvantageById(
    String id, {
    double? latitude,
    double? longitude,
  }) async {
    try {
      return await _advantageService.getAdvantageById(
        id,
        latitude: latitude,
        longitude: longitude,
      );
    } catch (e) {
      rethrow;
    }
  }

  Future<void> toggleFavorite(String advantageId, bool isFavorite) async {
    try {
      if (isFavorite) {
        await _advantageService.removeFromFavorites(advantageId);

        // Update local state
        state = state.copyWith(
          advantages: state.advantages.map((adv) {
            if (adv.id == advantageId) {
              return Advantage(
                id: adv.id,
                title: adv.title,
                description: adv.description,
                type: adv.type,
                discountPercentage: adv.discountPercentage,
                discountAmount: adv.discountAmount,
                validFrom: adv.validFrom,
                validUntil: adv.validUntil,
                daysAvailable: adv.daysAvailable,
                timeFrom: adv.timeFrom,
                timeUntil: adv.timeUntil,
                merchant: adv.merchant,
                category: adv.category,
                termsConditions: adv.termsConditions,
                usageLimit: adv.usageLimit,
                usageCount: adv.usageCount,
                rating: adv.rating,
                reviewsCount: adv.reviewsCount,
                distanceMeters: adv.distanceMeters,
                isFavorite: false,
              );
            }
            return adv;
          }).toList(),
          favorites: state.favorites
              .where((fav) => fav.id != advantageId)
              .toList(),
        );
      } else {
        await _advantageService.addToFavorites(advantageId);

        // Update local state
        state = state.copyWith(
          advantages: state.advantages.map((adv) {
            if (adv.id == advantageId) {
              return Advantage(
                id: adv.id,
                title: adv.title,
                description: adv.description,
                type: adv.type,
                discountPercentage: adv.discountPercentage,
                discountAmount: adv.discountAmount,
                validFrom: adv.validFrom,
                validUntil: adv.validUntil,
                daysAvailable: adv.daysAvailable,
                timeFrom: adv.timeFrom,
                timeUntil: adv.timeUntil,
                merchant: adv.merchant,
                category: adv.category,
                termsConditions: adv.termsConditions,
                usageLimit: adv.usageLimit,
                usageCount: adv.usageCount,
                rating: adv.rating,
                reviewsCount: adv.reviewsCount,
                distanceMeters: adv.distanceMeters,
                isFavorite: true,
              );
            }
            return adv;
          }).toList(),
        );
      }
    } catch (e) {
      rethrow;
    }
  }

  Future<void> loadFavorites({int page = 1}) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final favorites = await _advantageService.getFavorites(page: page);
      state = state.copyWith(
        favorites: favorites,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      rethrow;
    }
  }

  Future<void> loadCategories() async {
    try {
      final categories = await _advantageService.getCategories();
      state = state.copyWith(categories: categories);
    } catch (e) {
      // Silent fail for categories
    }
  }
}

final advantageProvider =
    StateNotifierProvider<AdvantageNotifier, AdvantageState>((ref) {
  final advantageService = ref.watch(advantageServiceProvider);
  return AdvantageNotifier(advantageService);
});
