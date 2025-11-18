import React from 'react';
import {View, Text, StyleSheet, TouchableOpacity, ScrollView} from 'react-native';
import {useAppSelector, useAppDispatch} from '@/store';
import {logout} from '@/store/slices/authSlice';
import {useNavigation} from '@react-navigation/native';
import {Colors, Theme} from '@/constants';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

const ProfileScreen = () => {
  const {user} = useAppSelector(state => state.auth);
  const dispatch = useAppDispatch();
  const navigation = useNavigation();

  const handleLogout = async () => {
    await dispatch(logout());
    navigation.navigate('Login' as never);
  };

  const menuItems = [
    {icon: 'wallet', label: 'Mon Portefeuille'},
    {icon: 'star', label: 'Mes Points'},
    {icon: 'trophy', label: 'Challenges'},
    {icon: 'sticker-emoji', label: 'Mes Stickers'},
    {icon: 'crown', label: 'FreeOui Plus'},
    {icon: 'share-variant', label: 'Parrainage'},
    {icon: 'cog', label: 'Paramètres'},
  ];

  return (
    <ScrollView style={styles.container}>
      {/* Profile Header */}
      <View style={styles.header}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>
            {user?.first_name?.[0]}{user?.last_name?.[0]}
          </Text>
        </View>
        <Text style={styles.name}>
          {user?.first_name} {user?.last_name}
        </Text>
        <Text style={styles.phone}>{user?.phone_number}</Text>

        {user?.is_premium && (
          <View style={styles.premiumBadge}>
            <Icon name="crown" size={16} color={Colors.gold} />
            <Text style={styles.premiumText}>FreeOui Plus</Text>
          </View>
        )}
      </View>

      {/* Stats */}
      <View style={styles.statsContainer}>
        <View style={styles.statBox}>
          <Text style={styles.statValue}>{user?.points_balance || 0}</Text>
          <Text style={styles.statLabel}>Points</Text>
        </View>
        <View style={styles.statBox}>
          <Text style={styles.statValue}>Niveau {user?.level || 1}</Text>
          <Text style={styles.statLabel}>Rang</Text>
        </View>
        <View style={styles.statBox}>
          <Text style={styles.statValue}>{user?.wallet_balance || 0} TND</Text>
          <Text style={styles.statLabel}>Solde</Text>
        </View>
      </View>

      {/* Menu */}
      <View style={styles.menu}>
        {menuItems.map((item, index) => (
          <TouchableOpacity key={index} style={styles.menuItem}>
            <View style={styles.menuLeft}>
              <Icon name={item.icon} size={24} color={Colors.primary} />
              <Text style={styles.menuLabel}>{item.label}</Text>
            </View>
            <Icon name="chevron-right" size={24} color={Colors.gray400} />
          </TouchableOpacity>
        ))}
      </View>

      {/* Logout */}
      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Icon name="logout" size={24} color={Colors.error} />
        <Text style={styles.logoutText}>Déconnexion</Text>
      </TouchableOpacity>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  header: {
    backgroundColor: Colors.white,
    alignItems: 'center',
    paddingVertical: Theme.spacing.xl,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: Colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: Theme.spacing.md,
  },
  avatarText: {
    fontSize: 32,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.white,
  },
  name: {
    fontSize: Theme.fontSize.xl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textPrimary,
  },
  phone: {
    fontSize: Theme.fontSize.md,
    color: Colors.textSecondary,
    marginTop: 4,
  },
  premiumBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.gold + '20',
    paddingHorizontal: Theme.spacing.md,
    paddingVertical: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.full,
    marginTop: Theme.spacing.md,
  },
  premiumText: {
    color: Colors.gold,
    fontWeight: Theme.fontWeight.semibold,
    marginLeft: 4,
  },
  statsContainer: {
    flexDirection: 'row',
    backgroundColor: Colors.white,
    marginTop: Theme.spacing.md,
    paddingVertical: Theme.spacing.lg,
  },
  statBox: {
    flex: 1,
    alignItems: 'center',
  },
  statValue: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textPrimary,
  },
  statLabel: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textSecondary,
    marginTop: 4,
  },
  menu: {
    backgroundColor: Colors.white,
    marginTop: Theme.spacing.md,
  },
  menuItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: Theme.spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: Colors.gray100,
  },
  menuLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  menuLabel: {
    fontSize: Theme.fontSize.md,
    color: Colors.textPrimary,
    marginLeft: Theme.spacing.md,
  },
  logoutButton: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: Colors.white,
    marginTop: Theme.spacing.md,
    marginBottom: Theme.spacing.xl,
    padding: Theme.spacing.lg,
  },
  logoutText: {
    fontSize: Theme.fontSize.md,
    color: Colors.error,
    marginLeft: Theme.spacing.sm,
    fontWeight: Theme.fontWeight.semibold,
  },
});

export default ProfileScreen;
