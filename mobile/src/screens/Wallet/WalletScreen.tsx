import React, {useEffect, useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '../../store';
import {fetchWalletBalance, fetchTransactions} from '../../store/slices/walletSlice';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const WalletScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {balance, transactions, loading} = useAppSelector(state => state.wallet);
  const {user} = useAppSelector(state => state.auth);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    dispatch(fetchWalletBalance());
    dispatch(fetchTransactions());
  }, [dispatch]);

  const onRefresh = async () => {
    setRefreshing(true);
    await Promise.all([
      dispatch(fetchWalletBalance()),
      dispatch(fetchTransactions()),
    ]);
    setRefreshing(false);
  };

  const getTransactionIcon = (type: string) => {
    switch (type) {
      case 'credit':
      case 'cashback':
      case 'referral':
        return 'arrow-down-circle';
      case 'debit':
      case 'purchase':
      case 'withdrawal':
        return 'arrow-up-circle';
      default:
        return 'swap-horizontal';
    }
  };

  const getTransactionColor = (type: string) => {
    switch (type) {
      case 'credit':
      case 'cashback':
      case 'referral':
        return Colors.success;
      case 'debit':
      case 'purchase':
      case 'withdrawal':
        return Colors.error;
      default:
        return Colors.textLight;
    }
  };

  const formatAmount = (amount: number, type: string) => {
    const prefix = type === 'credit' || type === 'cashback' || type === 'referral' ? '+' : '-';
    return `${prefix}${amount.toFixed(3)} TND`;
  };

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
        {/* Balance Card */}
        <View style={styles.balanceCard}>
          <View style={styles.balanceHeader}>
            <View>
              <Text style={styles.balanceLabel}>Solde disponible</Text>
              <Text style={styles.balanceAmount}>
                {(balance || user?.wallet_balance || 0).toFixed(3)} TND
              </Text>
            </View>
            <View style={styles.walletIcon}>
              <Icon name="wallet" size={32} color="#FFF" />
            </View>
          </View>

          {/* Quick Actions */}
          <View style={styles.quickActions}>
            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => navigation.navigate('TopUp' as never)}>
              <View style={styles.actionIconContainer}>
                <Icon name="add-circle" size={24} color={Colors.success} />
              </View>
              <Text style={styles.actionText}>Recharger</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => navigation.navigate('Withdraw' as never)}>
              <View style={styles.actionIconContainer}>
                <Icon name="remove-circle" size={24} color={Colors.error} />
              </View>
              <Text style={styles.actionText}>Retirer</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => navigation.navigate('Transfer' as never)}>
              <View style={styles.actionIconContainer}>
                <Icon name="swap-horizontal" size={24} color={Colors.primary} />
              </View>
              <Text style={styles.actionText}>Transférer</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => navigation.navigate('Cashback' as never)}>
              <View style={styles.actionIconContainer}>
                <Icon name="cash" size={24} color={Colors.warning} />
              </View>
              <Text style={styles.actionText}>Cashback</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Stats Cards */}
        <View style={styles.statsContainer}>
          <View style={styles.statCard}>
            <Icon name="trending-up" size={20} color={Colors.success} />
            <Text style={styles.statValue}>
              {user?.total_savings_tnd?.toFixed(3) || '0.000'} TND
            </Text>
            <Text style={styles.statLabel}>Économies totales</Text>
          </View>

          <View style={styles.statCard}>
            <Icon name="card" size={20} color={Colors.primary} />
            <Text style={styles.statValue}>{transactions.length}</Text>
            <Text style={styles.statLabel}>Transactions</Text>
          </View>
        </View>

        {/* Transactions */}
        <View style={styles.transactionsSection}>
          <View style={styles.transactionsHeader}>
            <Text style={styles.transactionsTitle}>Historique</Text>
            <TouchableOpacity onPress={() => navigation.navigate('TransactionHistory' as never)}>
              <Text style={styles.seeAllText}>Voir tout</Text>
            </TouchableOpacity>
          </View>

          {loading && transactions.length === 0 ? (
            <View style={styles.loadingContainer}>
              <ActivityIndicator size="large" color={Colors.primary} />
            </View>
          ) : transactions.length === 0 ? (
            <View style={styles.emptyContainer}>
              <Icon name="receipt-outline" size={64} color={Colors.textLight} />
              <Text style={styles.emptyText}>Aucune transaction</Text>
              <Text style={styles.emptySubtext}>
                Vos transactions apparaîtront ici
              </Text>
            </View>
          ) : (
            transactions.slice(0, 10).map((transaction: any) => (
              <View key={transaction.id} style={styles.transactionItem}>
                <View
                  style={[
                    styles.transactionIcon,
                    {backgroundColor: getTransactionColor(transaction.type) + '20'},
                  ]}>
                  <Icon
                    name={getTransactionIcon(transaction.type)}
                    size={24}
                    color={getTransactionColor(transaction.type)}
                  />
                </View>
                <View style={styles.transactionInfo}>
                  <Text style={styles.transactionTitle}>
                    {transaction.description || transaction.type}
                  </Text>
                  <Text style={styles.transactionDate}>
                    {new Date(transaction.created_at).toLocaleDateString('fr-FR', {
                      day: 'numeric',
                      month: 'short',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </Text>
                </View>
                <Text
                  style={[
                    styles.transactionAmount,
                    {color: getTransactionColor(transaction.type)},
                  ]}>
                  {formatAmount(transaction.amount, transaction.type)}
                </Text>
              </View>
            ))
          )}
        </View>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  balanceCard: {
    margin: Theme.spacing.md,
    padding: Theme.spacing.xl,
    borderRadius: Theme.borderRadius.xl,
    backgroundColor: Colors.primary,
    ...Theme.shadows.lg,
  },
  balanceHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: Theme.spacing.xl,
  },
  balanceLabel: {
    fontSize: Theme.fontSize.md,
    color: 'rgba(255, 255, 255, 0.8)',
    marginBottom: Theme.spacing.xs,
  },
  balanceAmount: {
    fontSize: 36,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
  },
  walletIcon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  quickActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  actionButton: {
    alignItems: 'center',
    flex: 1,
  },
  actionIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FFF',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: Theme.spacing.xs,
  },
  actionText: {
    fontSize: Theme.fontSize.xs,
    color: '#FFF',
    fontWeight: Theme.fontWeight.medium,
  },
  statsContainer: {
    flexDirection: 'row',
    paddingHorizontal: Theme.spacing.md,
    marginBottom: Theme.spacing.md,
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
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginVertical: Theme.spacing.xs,
  },
  statLabel: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    textAlign: 'center',
  },
  transactionsSection: {
    backgroundColor: '#FFF',
    marginHorizontal: Theme.spacing.md,
    marginBottom: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
    padding: Theme.spacing.md,
    ...Theme.shadows.sm,
  },
  transactionsHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: Theme.spacing.md,
  },
  transactionsTitle: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
  },
  seeAllText: {
    fontSize: Theme.fontSize.sm,
    color: Colors.primary,
    fontWeight: Theme.fontWeight.semibold,
  },
  transactionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: Theme.spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  transactionIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: Theme.spacing.md,
  },
  transactionInfo: {
    flex: 1,
  },
  transactionTitle: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginBottom: 4,
  },
  transactionDate: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
  },
  transactionAmount: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
  },
  loadingContainer: {
    paddingVertical: Theme.spacing.xxl,
    alignItems: 'center',
  },
  emptyContainer: {
    paddingVertical: Theme.spacing.xxl,
    alignItems: 'center',
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

export default WalletScreen;
