import React, {useEffect, useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Image,
} from 'react-native';
import {useAppDispatch, useAppSelector} from '../../store';
import {fetchStickers} from '../../store/slices/gamificationSlice';
import {gamificationService} from '../../services';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const STICKER_CATEGORIES = [
  {id: 'all', name: 'Tous', icon: 'apps'},
  {id: 'bronze', name: 'Bronze', icon: 'medal', color: '#CD7F32'},
  {id: 'silver', name: 'Argent', icon: 'medal', color: '#C0C0C0'},
  {id: 'gold', name: 'Or', icon: 'medal', color: '#FFD700'},
  {id: 'diamond', name: 'Diamant', icon: 'diamond', color: '#B9F2FF'},
];

const StickersScreen = () => {
  const dispatch = useAppDispatch();
  const {stickers, loading} = useAppSelector(state => state.gamification);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [stats, setStats] = useState<any>(null);

  useEffect(() => {
    dispatch(fetchStickers());
    fetchStats();
  }, [dispatch]);

  const fetchStats = async () => {
    try {
      const response = await gamificationService.getStickerStatistics();
      setStats(response.data);
    } catch (error) {
      console.error('Error fetching sticker stats:', error);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await Promise.all([dispatch(fetchStickers()), fetchStats()]);
    setRefreshing(false);
  };

  const filteredStickers = stickers.filter((sticker: any) =>
    selectedCategory === 'all' ? true : sticker.rarity === selectedCategory,
  );

  const collectedStickers = stickers.filter((s: any) => s.is_collected);
  const collectionRate = stickers.length > 0
    ? ((collectedStickers.length / stickers.length) * 100).toFixed(0)
    : 0;

  const getRarityColor = (rarity: string) => {
    const category = STICKER_CATEGORIES.find(c => c.id === rarity);
    return category?.color || Colors.textLight;
  };

  const getRarityIcon = (rarity: string) => {
    switch (rarity) {
      case 'bronze':
        return 'medal-outline';
      case 'silver':
        return 'medal-outline';
      case 'gold':
        return 'medal';
      case 'diamond':
        return 'diamond';
      default:
        return 'star';
    }
  };

  if (loading && stickers.length === 0) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScrollView
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={[Colors.primary]}
          />
        }>
        {/* Collection Progress */}
        <View style={styles.progressCard}>
          <View style={styles.progressHeader}>
            <View>
              <Text style={styles.progressTitle}>Ma Collection</Text>
              <Text style={styles.progressSubtitle}>
                {collectedStickers.length} / {stickers.length} stickers
              </Text>
            </View>
            <View style={styles.progressCircle}>
              <Text style={styles.progressPercentage}>{collectionRate}%</Text>
            </View>
          </View>

          <View style={styles.progressBar}>
            <View
              style={[
                styles.progressFill,
                {width: `${collectionRate}%`},
              ]}
            />
          </View>

          {/* Rarity Stats */}
          {stats && (
            <View style={styles.rarityStats}>
              {Object.entries(stats.by_rarity || {}).map(([rarity, count]: any) => (
                <View key={rarity} style={styles.rarityItem}>
                  <Icon
                    name={getRarityIcon(rarity)}
                    size={16}
                    color={getRarityColor(rarity)}
                  />
                  <Text style={styles.rarityCount}>{count}</Text>
                </View>
              ))}
            </View>
          )}
        </View>

        {/* Categories */}
        <View style={styles.categoriesContainer}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {STICKER_CATEGORIES.map(category => (
              <TouchableOpacity
                key={category.id}
                style={[
                  styles.categoryChip,
                  selectedCategory === category.id && styles.categoryChipActive,
                ]}
                onPress={() => setSelectedCategory(category.id)}>
                <Icon
                  name={category.icon}
                  size={18}
                  color={
                    selectedCategory === category.id
                      ? '#FFF'
                      : category.color || Colors.textLight
                  }
                />
                <Text
                  style={[
                    styles.categoryText,
                    selectedCategory === category.id && styles.categoryTextActive,
                  ]}>
                  {category.name}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>

        {/* Stickers Grid */}
        <View style={styles.stickersGrid}>
          {filteredStickers.map((sticker: any, index: number) => (
            <TouchableOpacity
              key={sticker.id}
              style={[
                styles.stickerCard,
                !sticker.is_collected && styles.stickerCardLocked,
              ]}>
              <View
                style={[
                  styles.stickerIconContainer,
                  {borderColor: getRarityColor(sticker.rarity)},
                ]}>
                {sticker.is_collected ? (
                  <Image
                    source={{
                      uri: sticker.image_url || 'https://via.placeholder.com/100',
                    }}
                    style={styles.stickerImage}
                  />
                ) : (
                  <View style={styles.lockedSticker}>
                    <Icon name="lock-closed" size={32} color={Colors.textLight} />
                  </View>
                )}

                {/* Rarity Badge */}
                <View
                  style={[
                    styles.rarityBadge,
                    {backgroundColor: getRarityColor(sticker.rarity)},
                  ]}>
                  <Icon
                    name={getRarityIcon(sticker.rarity)}
                    size={12}
                    color="#FFF"
                  />
                </View>
              </View>

              <Text
                style={[
                  styles.stickerName,
                  !sticker.is_collected && styles.stickerNameLocked,
                ]}
                numberOfLines={2}>
                {sticker.is_collected ? sticker.name : '???'}
              </Text>

              {sticker.is_collected && (
                <Text style={styles.stickerDate}>
                  Obtenu le{' '}
                  {new Date(sticker.collected_at || sticker.created_at).toLocaleDateString(
                    'fr-FR',
                    {day: 'numeric', month: 'short'},
                  )}
                </Text>
              )}

              {!sticker.is_collected && sticker.condition && (
                <Text style={styles.stickerCondition} numberOfLines={2}>
                  {sticker.condition}
                </Text>
              )}
            </TouchableOpacity>
          ))}
        </View>

        {filteredStickers.length === 0 && (
          <View style={styles.emptyContainer}>
            <Icon name="images-outline" size={64} color={Colors.textLight} />
            <Text style={styles.emptyText}>Aucun sticker dans cette catégorie</Text>
          </View>
        )}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: Colors.background,
  },
  progressCard: {
    margin: Theme.spacing.md,
    padding: Theme.spacing.lg,
    backgroundColor: Colors.primary,
    borderRadius: Theme.borderRadius.xl,
    ...Theme.shadows.lg,
  },
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: Theme.spacing.md,
  },
  progressTitle: {
    fontSize: Theme.fontSize.xl,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
  },
  progressSubtitle: {
    fontSize: Theme.fontSize.sm,
    color: 'rgba(255, 255, 255, 0.8)',
    marginTop: 4,
  },
  progressCircle: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  progressPercentage: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
  },
  progressBar: {
    height: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.3)',
    borderRadius: Theme.borderRadius.full,
    overflow: 'hidden',
    marginBottom: Theme.spacing.md,
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#FFF',
    borderRadius: Theme.borderRadius.full,
  },
  rarityStats: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  rarityItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  rarityCount: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
    marginLeft: Theme.spacing.xs,
  },
  categoriesContainer: {
    paddingVertical: Theme.spacing.md,
    paddingLeft: Theme.spacing.md,
  },
  categoryChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Theme.spacing.md,
    paddingVertical: Theme.spacing.sm,
    marginRight: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.full,
    backgroundColor: '#FFF',
    borderWidth: 2,
    borderColor: Colors.border,
  },
  categoryChipActive: {
    backgroundColor: Colors.primary,
    borderColor: Colors.primary,
  },
  categoryText: {
    marginLeft: Theme.spacing.xs,
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.medium,
    color: Colors.text,
  },
  categoryTextActive: {
    color: '#FFF',
  },
  stickersGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    padding: Theme.spacing.md,
  },
  stickerCard: {
    width: '31%',
    marginRight: '2.33%',
    marginBottom: Theme.spacing.md,
    alignItems: 'center',
  },
  stickerCardLocked: {
    opacity: 0.6,
  },
  stickerIconContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    borderWidth: 3,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#FFF',
    marginBottom: Theme.spacing.sm,
    position: 'relative',
    ...Theme.shadows.md,
  },
  stickerImage: {
    width: 80,
    height: 80,
    borderRadius: 40,
  },
  lockedSticker: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: Colors.background,
    justifyContent: 'center',
    alignItems: 'center',
  },
  rarityBadge: {
    position: 'absolute',
    bottom: -4,
    right: -4,
    width: 28,
    height: 28,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#FFF',
  },
  stickerName: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    textAlign: 'center',
    marginBottom: 4,
  },
  stickerNameLocked: {
    color: Colors.textLight,
  },
  stickerDate: {
    fontSize: 10,
    color: Colors.textLight,
    textAlign: 'center',
  },
  stickerCondition: {
    fontSize: 10,
    color: Colors.textLight,
    textAlign: 'center',
    fontStyle: 'italic',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: Theme.spacing.xxl * 2,
  },
  emptyText: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginTop: Theme.spacing.md,
  },
});

export default StickersScreen;
