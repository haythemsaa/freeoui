import React, {useEffect, useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Alert,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '../../store';
import {fetchChallenges} from '../../store/slices/gamificationSlice';
import {gamificationService} from '../../services';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const ChallengesScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {challenges, loading} = useAppSelector(state => state.gamification);
  const [refreshing, setRefreshing] = useState(false);
  const [participating, setParticipating] = useState<{[key: string]: boolean}>({});

  useEffect(() => {
    dispatch(fetchChallenges());
  }, [dispatch]);

  const onRefresh = async () => {
    setRefreshing(true);
    await dispatch(fetchChallenges());
    setRefreshing(false);
  };

  const handleParticipate = async (challengeId: string) => {
    try {
      setParticipating(prev => ({...prev, [challengeId]: true}));
      const response = await gamificationService.participateInChallenge(challengeId);

      if (response.success) {
        Alert.alert(
          '🎯 Challenge accepté!',
          'Vous participez maintenant à ce challenge. Bonne chance!',
        );
        dispatch(fetchChallenges());
      }
    } catch (error: any) {
      Alert.alert(
        'Erreur',
        error.response?.data?.message || 'Impossible de participer au challenge',
      );
    } finally {
      setParticipating(prev => ({...prev, [challengeId]: false}));
    }
  };

  const getChallengeIcon = (type: string) => {
    switch (type) {
      case 'visits':
        return 'location';
      case 'referrals':
        return 'people';
      case 'spending':
        return 'cash';
      case 'checkins':
        return 'checkmark-circle';
      default:
        return 'trophy';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active':
        return Colors.success;
      case 'completed':
        return Colors.primary;
      case 'upcoming':
        return Colors.warning;
      case 'expired':
        return Colors.textLight;
      default:
        return Colors.text;
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'active':
        return 'En cours';
      case 'completed':
        return 'Terminé';
      case 'upcoming':
        return 'À venir';
      case 'expired':
        return 'Expiré';
      default:
        return status;
    }
  };

  const activeChallenges = challenges.filter((c: any) => c.status === 'active');
  const upcomingChallenges = challenges.filter((c: any) => c.status === 'upcoming');
  const completedChallenges = challenges.filter((c: any) => c.status === 'completed');

  const renderChallenge = (challenge: any) => {
    const progress = challenge.participation?.progress_percentage || 0;
    const isParticipating = challenge.participation !== null;
    const isCompleted = challenge.status === 'completed';

    return (
      <View key={challenge.id} style={styles.challengeCard}>
        <View style={styles.challengeHeader}>
          <View
            style={[
              styles.challengeIcon,
              {backgroundColor: getStatusColor(challenge.status) + '20'},
            ]}>
            <Icon
              name={getChallengeIcon(challenge.type)}
              size={28}
              color={getStatusColor(challenge.status)}
            />
          </View>
          <View
            style={[
              styles.statusBadge,
              {backgroundColor: getStatusColor(challenge.status)},
            ]}>
            <Text style={styles.statusText}>{getStatusLabel(challenge.status)}</Text>
          </View>
        </View>

        <Text style={styles.challengeTitle}>{challenge.title}</Text>
        <Text style={styles.challengeDescription}>{challenge.description}</Text>

        {/* Rewards */}
        <View style={styles.rewardsRow}>
          <View style={styles.rewardItem}>
            <Icon name="trophy" size={16} color={Colors.warning} />
            <Text style={styles.rewardText}>
              {challenge.points_reward} points
            </Text>
          </View>
          {challenge.coins_reward && (
            <View style={styles.rewardItem}>
              <Icon name="diamond" size={16} color={Colors.primary} />
              <Text style={styles.rewardText}>
                {challenge.coins_reward} coins
              </Text>
            </View>
          )}
        </View>

        {/* Progress */}
        {isParticipating && !isCompleted && (
          <View style={styles.progressContainer}>
            <View style={styles.progressHeader}>
              <Text style={styles.progressLabel}>Progression</Text>
              <Text style={styles.progressPercentage}>{progress.toFixed(0)}%</Text>
            </View>
            <View style={styles.progressBar}>
              <View
                style={[
                  styles.progressFill,
                  {
                    width: `${Math.min(progress, 100)}%`,
                    backgroundColor: getStatusColor(challenge.status),
                  },
                ]}
              />
            </View>
            <Text style={styles.progressText}>
              {challenge.participation?.current_progress || 0} / {challenge.target_value}
            </Text>
          </View>
        )}

        {/* Validity */}
        <View style={styles.validityRow}>
          <Icon name="calendar-outline" size={14} color={Colors.textLight} />
          <Text style={styles.validityText}>
            Jusqu'au {new Date(challenge.end_date).toLocaleDateString('fr-FR')}
          </Text>
        </View>

        {/* Action Button */}
        {!isParticipating && challenge.status === 'active' && (
          <TouchableOpacity
            style={styles.participateButton}
            onPress={() => handleParticipate(challenge.id)}
            disabled={participating[challenge.id]}>
            {participating[challenge.id] ? (
              <ActivityIndicator size="small" color="#FFF" />
            ) : (
              <>
                <Icon name="rocket" size={18} color="#FFF" />
                <Text style={styles.participateButtonText}>Participer</Text>
              </>
            )}
          </TouchableOpacity>
        )}

        {isCompleted && (
          <View style={styles.completedBadge}>
            <Icon name="checkmark-circle" size={18} color={Colors.success} />
            <Text style={styles.completedText}>Challenge terminé!</Text>
          </View>
        )}
      </View>
    );
  };

  if (loading && challenges.length === 0) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={onRefresh}
          colors={[Colors.primary]}
        />
      }>
      {/* Header Stats */}
      <View style={styles.headerStats}>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{activeChallenges.length}</Text>
          <Text style={styles.statLabel}>En cours</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{completedChallenges.length}</Text>
          <Text style={styles.statLabel}>Terminés</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{upcomingChallenges.length}</Text>
          <Text style={styles.statLabel}>À venir</Text>
        </View>
      </View>

      {/* Active Challenges */}
      {activeChallenges.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>🔥 Challenges actifs</Text>
          {activeChallenges.map(renderChallenge)}
        </View>
      )}

      {/* Upcoming Challenges */}
      {upcomingChallenges.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>⏰ Prochainement</Text>
          {upcomingChallenges.map(renderChallenge)}
        </View>
      )}

      {/* Completed Challenges */}
      {completedChallenges.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>✅ Terminés</Text>
          {completedChallenges.map(renderChallenge)}
        </View>
      )}

      {challenges.length === 0 && (
        <View style={styles.emptyContainer}>
          <Icon name="trophy-outline" size={64} color={Colors.textLight} />
          <Text style={styles.emptyText}>Aucun challenge disponible</Text>
          <Text style={styles.emptySubtext}>
            Les challenges apparaîtront bientôt!
          </Text>
        </View>
      )}
    </ScrollView>
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
  headerStats: {
    flexDirection: 'row',
    padding: Theme.spacing.md,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#FFF',
    padding: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
    marginHorizontal: Theme.spacing.xs,
    alignItems: 'center',
    ...Theme.shadows.sm,
  },
  statValue: {
    fontSize: Theme.fontSize.xxl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.primary,
  },
  statLabel: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    marginTop: 4,
  },
  section: {
    padding: Theme.spacing.md,
  },
  sectionTitle: {
    fontSize: Theme.fontSize.xl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginBottom: Theme.spacing.md,
  },
  challengeCard: {
    backgroundColor: '#FFF',
    borderRadius: Theme.borderRadius.xl,
    padding: Theme.spacing.lg,
    marginBottom: Theme.spacing.md,
    ...Theme.shadows.md,
  },
  challengeHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: Theme.spacing.md,
  },
  challengeIcon: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
  },
  statusBadge: {
    paddingHorizontal: Theme.spacing.sm,
    paddingVertical: 4,
    borderRadius: Theme.borderRadius.full,
  },
  statusText: {
    fontSize: Theme.fontSize.xs,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
  },
  challengeTitle: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginBottom: Theme.spacing.xs,
  },
  challengeDescription: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    lineHeight: 20,
    marginBottom: Theme.spacing.md,
  },
  rewardsRow: {
    flexDirection: 'row',
    marginBottom: Theme.spacing.md,
  },
  rewardItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: Theme.spacing.lg,
  },
  rewardText: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginLeft: Theme.spacing.xs,
  },
  progressContainer: {
    marginBottom: Theme.spacing.md,
  },
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: Theme.spacing.xs,
  },
  progressLabel: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    fontWeight: Theme.fontWeight.medium,
  },
  progressPercentage: {
    fontSize: Theme.fontSize.sm,
    color: Colors.text,
    fontWeight: Theme.fontWeight.bold,
  },
  progressBar: {
    height: 8,
    backgroundColor: Colors.border,
    borderRadius: Theme.borderRadius.full,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: Theme.borderRadius.full,
  },
  progressText: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    marginTop: 4,
  },
  validityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: Theme.spacing.md,
  },
  validityText: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    marginLeft: 4,
  },
  participateButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.primary,
    paddingVertical: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
  },
  participateButtonText: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
    marginLeft: Theme.spacing.xs,
  },
  completedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.successLight,
    paddingVertical: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
  },
  completedText: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.success,
    marginLeft: Theme.spacing.xs,
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
  emptySubtext: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginTop: Theme.spacing.xs,
  },
});

export default ChallengesScreen;
