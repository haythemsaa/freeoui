import React, {useEffect} from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  Image,
} from 'react-native';
import {useAppDispatch, useAppSelector} from '@/store';
import {fetchAdvantages, fetchRecommendations} from '@/store/slices/advantageSlice';
import {fetchChallenges} from '@/store/slices/gamificationSlice';
import {Colors, Theme} from '@/constants';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

const HomeScreen = () => {
  const dispatch = useAppDispatch();
  const {user} = useAppSelector(state => state.auth);
  const {recommendations} = useAppSelector(state => state.advantages);
  const {challenges} = useAppSelector(state => state.gamification);

  useEffect(() => {
    dispatch(fetchAdvantages());
    dispatch(fetchRecommendations());
    dispatch(fetchChallenges());
  }, []);

  return (
    <ScrollView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Bonjour,</Text>
          <Text style={styles.userName}>{user?.first_name} ! 👋</Text>
        </View>
        <TouchableOpacity style={styles.notificationButton}>
          <Icon name="bell-outline" size={24} color={Colors.textPrimary} />
        </TouchableOpacity>
      </View>

      {/* Stats Card */}
      <View style={styles.statsCard}>
        <View style={styles.statItem}>
          <Icon name="star" size={24} color={Colors.gold} />
          <Text style={styles.statValue}>{user?.points_balance || 0}</Text>
          <Text style={styles.statLabel}>Points</Text>
        </View>
        <View style={styles.statDivider} />
        <View style={styles.statItem}>
          <Icon name="trophy" size={24} color={Colors.primary} />
          <Text style={styles.statValue}>Niveau {user?.level || 1}</Text>
          <Text style={styles.statLabel}>Rang</Text>
        </View>
        <View style={styles.statDivider} />
        <View style={styles.statItem}>
          <Icon name="piggy-bank" size={24} color={Colors.secondary} />
          <Text style={styles.statValue}>{user?.total_savings_tnd || 0} TND</Text>
          <Text style={styles.statLabel}>Économisé</Text>
        </View>
      </View>

      {/* Recommendations */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Pour vous</Text>
          <TouchableOpacity>
            <Text style={styles.seeAll}>Tout voir</Text>
          </TouchableOpacity>
        </View>
        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
          {recommendations.map((advantage, index) => (
            <TouchableOpacity key={index} style={styles.advantageCard}>
              <Image
                source={{uri: advantage.image_url || 'https://via.placeholder.com/150'}}
                style={styles.advantageImage}
              />
              <View style={styles.discountBadge}>
                <Text style={styles.discountText}>-{advantage.discount_percentage}%</Text>
              </View>
              <Text style={styles.advantageTitle} numberOfLines={2}>
                {advantage.title}
              </Text>
              <Text style={styles.merchantName}>{advantage.merchant.name}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: Theme.spacing.lg,
    backgroundColor: Colors.white,
  },
  greeting: {
    fontSize: Theme.fontSize.md,
    color: Colors.textSecondary,
  },
  userName: {
    fontSize: Theme.fontSize.xl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textPrimary,
  },
  notificationButton: {
    position: 'relative',
  },
  statsCard: {
    flexDirection: 'row',
    backgroundColor: Colors.white,
    margin: Theme.spacing.lg,
    padding: Theme.spacing.lg,
    borderRadius: Theme.borderRadius.xl,
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textPrimary,
    marginTop: Theme.spacing.sm,
  },
  statLabel: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textSecondary,
    marginTop: 2,
  },
  statDivider: {
    width: 1,
    backgroundColor: Colors.gray200,
  },
  section: {
    marginBottom: Theme.spacing.lg,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: Theme.spacing.lg,
    marginBottom: Theme.spacing.md,
  },
  sectionTitle: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textPrimary,
  },
  seeAll: {
    fontSize: Theme.fontSize.sm,
    color: Colors.primary,
  },
  advantageCard: {
    width: 200,
    backgroundColor: Colors.white,
    borderRadius: Theme.borderRadius.lg,
    marginLeft: Theme.spacing.lg,
  },
  advantageImage: {
    width: '100%',
    height: 120,
    borderTopLeftRadius: Theme.borderRadius.lg,
    borderTopRightRadius: Theme.borderRadius.lg,
  },
  discountBadge: {
    position: 'absolute',
    top: 8,
    right: 8,
    backgroundColor: Colors.error,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: Theme.borderRadius.md,
  },
  discountText: {
    color: Colors.white,
    fontWeight: Theme.fontWeight.bold,
    fontSize: Theme.fontSize.sm,
  },
  advantageTitle: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.textPrimary,
    padding: Theme.spacing.md,
  },
  merchantName: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textSecondary,
    paddingHorizontal: Theme.spacing.md,
    paddingBottom: Theme.spacing.md,
  },
});

export default HomeScreen;
