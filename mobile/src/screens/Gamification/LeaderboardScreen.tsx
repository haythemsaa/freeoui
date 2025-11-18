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
import {gamificationService} from '../../services';
import {useAppSelector} from '../../store';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const LeaderboardScreen = () => {
  const {user} = useAppSelector(state => state.auth);
  const [leaderboard, setLeaderboard] = useState<any[]>([]);
  const [userRank, setUserRank] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedTab, setSelectedTab] = useState<'global' | 'friends'>('global');

  useEffect(() => {
    fetchLeaderboard();
  }, [selectedTab]);

  const fetchLeaderboard = async () => {
    try {
      setLoading(true);
      const [leaderboardRes, rankRes] = await Promise.all([
        selectedTab === 'global'
          ? gamificationService.getGlobalLeaderboard()
          : gamificationService.getFriendsLeaderboard(),
        gamificationService.getUserRank(),
      ]);
      setLeaderboard(leaderboardRes.data);
      setUserRank(rankRes.data);
    } catch (error) {
      console.error('Error fetching leaderboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchLeaderboard();
    setRefreshing(false);
  };

  const getMedalColor = (rank: number) => {
    switch (rank) {
      case 1:
        return '#FFD700'; // Gold
      case 2:
        return '#C0C0C0'; // Silver
      case 3:
        return '#CD7F32'; // Bronze
      default:
        return Colors.textLight;
    }
  };

  const getMedalIcon = (rank: number) => {
    if (rank <= 3) return 'trophy';
    return 'person-circle-outline';
  };

  const renderLeaderItem = (item: any, index: number) => {
    const rank = index + 1;
    const isCurrentUser = item.user_id === user?.id;

    return (
      <View
        key={item.user_id}
        style={[styles.leaderItem, isCurrentUser && styles.currentUserItem]}>
        {/* Rank */}
        <View style={styles.rankContainer}>
          {rank <= 3 ? (
            <View style={[styles.medalBadge, {backgroundColor: getMedalColor(rank)}]}>
              <Icon name="trophy" size={20} color="#FFF" />
            </View>
          ) : (
            <Text style={styles.rankText}>#{rank}</Text>
          )}
        </View>

        {/* Avatar & Info */}
        <View style={styles.userInfo}>
          <View style={styles.avatar}>
            {item.avatar_url ? (
              <Image source={{uri: item.avatar_url}} style={styles.avatarImage} />
            ) : (
              <View style={styles.avatarPlaceholder}>
                <Text style={styles.avatarText}>
                  {item.first_name?.[0]}{item.last_name?.[0]}
                </Text>
              </View>
            )}
          </View>
          <View style={styles.userDetails}>
            <Text style={styles.userName}>
              {item.first_name} {item.last_name}
              {isCurrentUser && <Text style={styles.youBadge}> (Vous)</Text>}
            </Text>
            <View style={styles.statsRow}>
              <View style={styles.statBadge}>
                <Icon name="trophy" size={12} color={Colors.warning} />
                <Text style={styles.statBadgeText}>{item.points_balance} pts</Text>
              </View>
              <View style={styles.statBadge}>
                <Icon name="flame" size={12} color={Colors.error} />
                <Text style={styles.statBadgeText}>Série {item.checkin_streak}</Text>
              </View>
            </View>
          </View>
        </View>

        {/* Level */}
        <View style={styles.levelBadge}>
          <Icon name="star" size={14} color={Colors.primary} />
          <Text style={styles.levelText}>{item.level}</Text>
        </View>
      </View>
    );
  };

  if (loading && leaderboard.length === 0) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>🏆 Classement</Text>
        <View style={styles.tabsContainer}>
          <TouchableOpacity
            style={[styles.tab, selectedTab === 'global' && styles.tabActive]}
            onPress={() => setSelectedTab('global')}>
            <Text
              style={[
                styles.tabText,
                selectedTab === 'global' && styles.tabTextActive,
              ]}>
              Global
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.tab, selectedTab === 'friends' && styles.tabActive]}
            onPress={() => setSelectedTab('friends')}>
            <Text
              style={[
                styles.tabText,
                selectedTab === 'friends' && styles.tabTextActive,
              ]}>
              Amis
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* User Rank Card */}
      {userRank && (
        <View style={styles.userRankCard}>
          <View style={styles.userRankContent}>
            <View style={styles.userRankInfo}>
              <Text style={styles.userRankLabel}>Votre classement</Text>
              <Text style={styles.userRankValue}>#{userRank.rank}</Text>
            </View>
            <View style={styles.userRankStats}>
              <View style={styles.userRankStat}>
                <Icon name="trophy" size={18} color={Colors.warning} />
                <Text style={styles.userRankStatText}>{user?.points_balance}</Text>
              </View>
              <View style={styles.userRankStat}>
                <Icon name="star" size={18} color={Colors.primary} />
                <Text style={styles.userRankStatText}>Niveau {user?.level}</Text>
              </View>
            </View>
          </View>
        </View>
      )}

      {/* Leaderboard */}
      <ScrollView
        style={styles.leaderboardContainer}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={[Colors.primary]}
          />
        }>
        {leaderboard.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Icon name="people-outline" size={64} color={Colors.textLight} />
            <Text style={styles.emptyText}>
              Aucun utilisateur dans ce classement
            </Text>
          </View>
        ) : (
          leaderboard.map(renderLeaderItem)
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
  header: {
    backgroundColor: '#FFF',
    padding: Theme.spacing.lg,
    ...Theme.shadows.sm,
  },
  headerTitle: {
    fontSize: Theme.fontSize.xxl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginBottom: Theme.spacing.md,
  },
  tabsContainer: {
    flexDirection: 'row',
    backgroundColor: Colors.background,
    borderRadius: Theme.borderRadius.lg,
    padding: 4,
  },
  tab: {
    flex: 1,
    paddingVertical: Theme.spacing.sm,
    alignItems: 'center',
    borderRadius: Theme.borderRadius.md,
  },
  tabActive: {
    backgroundColor: Colors.primary,
  },
  tabText: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.textLight,
  },
  tabTextActive: {
    color: '#FFF',
  },
  userRankCard: {
    margin: Theme.spacing.md,
    padding: Theme.spacing.lg,
    backgroundColor: Colors.primaryLight,
    borderRadius: Theme.borderRadius.xl,
    borderWidth: 2,
    borderColor: Colors.primary,
  },
  userRankContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  userRankInfo: {
    flex: 1,
  },
  userRankLabel: {
    fontSize: Theme.fontSize.sm,
    color: Colors.primary,
    marginBottom: 4,
  },
  userRankValue: {
    fontSize: Theme.fontSize.xxl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.primary,
  },
  userRankStats: {
    flexDirection: 'row',
  },
  userRankStat: {
    flexDirection: 'row',
    alignItems: 'center',
    marginLeft: Theme.spacing.lg,
  },
  userRankStatText: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginLeft: Theme.spacing.xs,
  },
  leaderboardContainer: {
    flex: 1,
    padding: Theme.spacing.md,
  },
  leaderItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    padding: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
    marginBottom: Theme.spacing.sm,
    ...Theme.shadows.sm,
  },
  currentUserItem: {
    backgroundColor: Colors.primaryLight,
    borderWidth: 2,
    borderColor: Colors.primary,
  },
  rankContainer: {
    width: 50,
    alignItems: 'center',
  },
  medalBadge: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  rankText: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textLight,
  },
  userInfo: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatar: {
    width: 50,
    height: 50,
    borderRadius: 25,
    marginRight: Theme.spacing.md,
  },
  avatarImage: {
    width: '100%',
    height: '100%',
    borderRadius: 25,
  },
  avatarPlaceholder: {
    width: '100%',
    height: '100%',
    borderRadius: 25,
    backgroundColor: Colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
  },
  userDetails: {
    flex: 1,
  },
  userName: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginBottom: 4,
  },
  youBadge: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.primary,
  },
  statsRow: {
    flexDirection: 'row',
  },
  statBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.background,
    paddingHorizontal: Theme.spacing.xs,
    paddingVertical: 2,
    borderRadius: Theme.borderRadius.sm,
    marginRight: Theme.spacing.xs,
  },
  statBadgeText: {
    fontSize: 11,
    color: Colors.text,
    marginLeft: 2,
    fontWeight: Theme.fontWeight.medium,
  },
  levelBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.primaryLight,
    paddingHorizontal: Theme.spacing.sm,
    paddingVertical: Theme.spacing.xs,
    borderRadius: Theme.borderRadius.md,
  },
  levelText: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.primary,
    marginLeft: 4,
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
    textAlign: 'center',
  },
});

export default LeaderboardScreen;
