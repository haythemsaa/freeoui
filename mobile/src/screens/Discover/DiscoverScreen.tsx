import React, {useEffect, useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  TextInput,
  Image,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '../../store';
import {fetchAdvantages} from '../../store/slices/advantageSlice';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

interface Category {
  id: string;
  name: string;
  icon: string;
}

const CATEGORIES: Category[] = [
  {id: 'all', name: 'Tous', icon: 'grid-outline'},
  {id: 'restaurant', name: 'Restaurant', icon: 'restaurant-outline'},
  {id: 'cafe', name: 'Café', icon: 'cafe-outline'},
  {id: 'shopping', name: 'Shopping', icon: 'bag-outline'},
  {id: 'beauty', name: 'Beauté', icon: 'cut-outline'},
  {id: 'sport', name: 'Sport', icon: 'fitness-outline'},
  {id: 'entertainment', name: 'Loisirs', icon: 'game-controller-outline'},
  {id: 'health', name: 'Santé', icon: 'medical-outline'},
];

const SORT_OPTIONS = [
  {id: 'discount', label: 'Réduction', icon: 'trending-down'},
  {id: 'distance', label: 'Distance', icon: 'location'},
  {id: 'trending', label: 'Tendance', icon: 'flame'},
  {id: 'new', label: 'Nouveau', icon: 'star'},
];

const DiscoverScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {advantages, loading} = useAppSelector(state => state.advantages);

  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedSort, setSelectedSort] = useState('discount');
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    dispatch(fetchAdvantages());
  }, [dispatch]);

  const onRefresh = async () => {
    setRefreshing(true);
    await dispatch(fetchAdvantages());
    setRefreshing(false);
  };

  const filteredAdvantages = advantages
    .filter(adv => {
      const matchesSearch =
        adv.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        adv.merchant.name.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesCategory =
        selectedCategory === 'all' ||
        adv.merchant.category?.toLowerCase() === selectedCategory.toLowerCase();
      return matchesSearch && matchesCategory;
    })
    .sort((a, b) => {
      switch (selectedSort) {
        case 'discount':
          return (b.discount_percentage || 0) - (a.discount_percentage || 0);
        case 'distance':
          return (a.distance || 0) - (b.distance || 0);
        case 'trending':
          return (b.usage_count || 0) - (a.usage_count || 0);
        case 'new':
          return (
            new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
          );
        default:
          return 0;
      }
    });

  const renderAdvantageCard = ({item}: {item: any}) => (
    <TouchableOpacity
      style={styles.advantageCard}
      onPress={() =>
        navigation.navigate('AdvantageDetail' as never, {id: item.id} as never)
      }>
      <Image
        source={{uri: item.image_url || 'https://via.placeholder.com/300'}}
        style={styles.advantageImage}
      />
      <View style={styles.discountBadge}>
        <Text style={styles.discountText}>-{item.discount_percentage}%</Text>
      </View>

      {item.is_trending && (
        <View style={styles.trendingBadge}>
          <Icon name="flame" size={14} color="#FFF" />
          <Text style={styles.trendingText}>Tendance</Text>
        </View>
      )}

      <View style={styles.advantageContent}>
        <Text style={styles.advantageTitle} numberOfLines={2}>
          {item.title}
        </Text>
        <View style={styles.merchantRow}>
          <Icon name="storefront-outline" size={14} color={Colors.textLight} />
          <Text style={styles.merchantName} numberOfLines={1}>
            {item.merchant.name}
          </Text>
        </View>

        <View style={styles.infoRow}>
          {item.distance && (
            <View style={styles.infoItem}>
              <Icon name="location-outline" size={14} color={Colors.primary} />
              <Text style={styles.infoText}>{item.distance.toFixed(1)} km</Text>
            </View>
          )}
          {item.points_required && (
            <View style={styles.infoItem}>
              <Icon name="trophy-outline" size={14} color={Colors.secondary} />
              <Text style={styles.infoText}>{item.points_required} pts</Text>
            </View>
          )}
        </View>

        {item.valid_until && (
          <View style={styles.validUntil}>
            <Icon name="time-outline" size={12} color={Colors.warning} />
            <Text style={styles.validUntilText}>
              Valable jusqu'au {new Date(item.valid_until).toLocaleDateString()}
            </Text>
          </View>
        )}
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Search Bar */}
      <View style={styles.searchContainer}>
        <View style={styles.searchBar}>
          <Icon name="search-outline" size={20} color={Colors.textLight} />
          <TextInput
            style={styles.searchInput}
            placeholder="Rechercher un avantage..."
            placeholderTextColor={Colors.textLight}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery.length > 0 && (
            <TouchableOpacity onPress={() => setSearchQuery('')}>
              <Icon name="close-circle" size={20} color={Colors.textLight} />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* Categories */}
      <View style={styles.categoriesContainer}>
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={CATEGORIES}
          keyExtractor={item => item.id}
          renderItem={({item}) => (
            <TouchableOpacity
              style={[
                styles.categoryChip,
                selectedCategory === item.id && styles.categoryChipActive,
              ]}
              onPress={() => setSelectedCategory(item.id)}>
              <Icon
                name={item.icon}
                size={18}
                color={
                  selectedCategory === item.id ? '#FFF' : Colors.textLight
                }
              />
              <Text
                style={[
                  styles.categoryText,
                  selectedCategory === item.id && styles.categoryTextActive,
                ]}>
                {item.name}
              </Text>
            </TouchableOpacity>
          )}
        />
      </View>

      {/* Sort Options */}
      <View style={styles.sortContainer}>
        <Text style={styles.sortLabel}>Trier par:</Text>
        {SORT_OPTIONS.map(option => (
          <TouchableOpacity
            key={option.id}
            style={[
              styles.sortChip,
              selectedSort === option.id && styles.sortChipActive,
            ]}
            onPress={() => setSelectedSort(option.id)}>
            <Icon
              name={option.icon}
              size={14}
              color={selectedSort === option.id ? '#FFF' : Colors.textLight}
            />
            <Text
              style={[
                styles.sortText,
                selectedSort === option.id && styles.sortTextActive,
              ]}>
              {option.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Results Count */}
      <View style={styles.resultsHeader}>
        <Text style={styles.resultsCount}>
          {filteredAdvantages.length} avantage{filteredAdvantages.length !== 1 ? 's' : ''}
        </Text>
      </View>

      {/* Advantages List */}
      {loading && !refreshing ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={Colors.primary} />
        </View>
      ) : (
        <FlatList
          data={filteredAdvantages}
          renderItem={renderAdvantageCard}
          keyExtractor={item => item.id}
          numColumns={2}
          columnWrapperStyle={styles.row}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              colors={[Colors.primary]}
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Icon name="search-outline" size={64} color={Colors.textLight} />
              <Text style={styles.emptyText}>Aucun avantage trouvé</Text>
              <Text style={styles.emptySubtext}>
                Essayez de modifier vos filtres
              </Text>
            </View>
          }
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  searchContainer: {
    padding: Theme.spacing.md,
    backgroundColor: '#FFF',
    ...Theme.shadows.sm,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.background,
    borderRadius: Theme.borderRadius.lg,
    paddingHorizontal: Theme.spacing.md,
    paddingVertical: Theme.spacing.sm,
  },
  searchInput: {
    flex: 1,
    marginLeft: Theme.spacing.sm,
    fontSize: Theme.fontSize.md,
    color: Colors.text,
  },
  categoriesContainer: {
    paddingVertical: Theme.spacing.md,
    paddingLeft: Theme.spacing.md,
    backgroundColor: '#FFF',
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  categoryChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Theme.spacing.md,
    paddingVertical: Theme.spacing.sm,
    marginRight: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.full,
    backgroundColor: Colors.background,
    borderWidth: 1,
    borderColor: Colors.border,
  },
  categoryChipActive: {
    backgroundColor: Colors.primary,
    borderColor: Colors.primary,
  },
  categoryText: {
    marginLeft: Theme.spacing.xs,
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    fontWeight: Theme.fontWeight.medium,
  },
  categoryTextActive: {
    color: '#FFF',
  },
  sortContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: Theme.spacing.md,
    backgroundColor: '#FFF',
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  sortLabel: {
    fontSize: Theme.fontSize.sm,
    color: Colors.text,
    fontWeight: Theme.fontWeight.semibold,
    marginRight: Theme.spacing.sm,
  },
  sortChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: Theme.spacing.sm,
    paddingVertical: Theme.spacing.xs,
    marginRight: Theme.spacing.xs,
    borderRadius: Theme.borderRadius.md,
    backgroundColor: Colors.background,
    borderWidth: 1,
    borderColor: Colors.border,
  },
  sortChipActive: {
    backgroundColor: Colors.primary,
    borderColor: Colors.primary,
  },
  sortText: {
    marginLeft: 4,
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
  },
  sortTextActive: {
    color: '#FFF',
  },
  resultsHeader: {
    padding: Theme.spacing.md,
    backgroundColor: '#FFF',
  },
  resultsCount: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    fontWeight: Theme.fontWeight.medium,
  },
  row: {
    justifyContent: 'space-between',
    paddingHorizontal: Theme.spacing.md,
  },
  listContent: {
    paddingTop: Theme.spacing.sm,
    paddingBottom: Theme.spacing.xl,
  },
  advantageCard: {
    width: '48%',
    backgroundColor: '#FFF',
    borderRadius: Theme.borderRadius.lg,
    marginBottom: Theme.spacing.md,
    ...Theme.shadows.sm,
    overflow: 'hidden',
  },
  advantageImage: {
    width: '100%',
    height: 120,
    backgroundColor: Colors.background,
  },
  discountBadge: {
    position: 'absolute',
    top: Theme.spacing.sm,
    right: Theme.spacing.sm,
    backgroundColor: Colors.error,
    paddingHorizontal: Theme.spacing.sm,
    paddingVertical: 4,
    borderRadius: Theme.borderRadius.md,
  },
  discountText: {
    color: '#FFF',
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.bold,
  },
  trendingBadge: {
    position: 'absolute',
    top: Theme.spacing.sm,
    left: Theme.spacing.sm,
    backgroundColor: Colors.warning,
    paddingHorizontal: Theme.spacing.xs,
    paddingVertical: 4,
    borderRadius: Theme.borderRadius.md,
    flexDirection: 'row',
    alignItems: 'center',
  },
  trendingText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: Theme.fontWeight.bold,
    marginLeft: 2,
  },
  advantageContent: {
    padding: Theme.spacing.sm,
  },
  advantageTitle: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginBottom: Theme.spacing.xs,
  },
  merchantRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: Theme.spacing.xs,
  },
  merchantName: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    marginLeft: 4,
    flex: 1,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: Theme.spacing.xs,
  },
  infoItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: Theme.spacing.sm,
  },
  infoText: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    marginLeft: 2,
  },
  validUntil: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.warningLight,
    paddingHorizontal: Theme.spacing.xs,
    paddingVertical: 2,
    borderRadius: Theme.borderRadius.sm,
  },
  validUntilText: {
    fontSize: 10,
    color: Colors.warning,
    marginLeft: 2,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: Theme.spacing.xxl,
  },
  emptyText: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginTop: Theme.spacing.md,
  },
  emptySubtext: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginTop: Theme.spacing.xs,
  },
});

export default DiscoverScreen;
