import React, {useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Switch,
  Alert,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '../../store';
import {logout} from '../../store/slices/authSlice';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const SettingsScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {user} = useAppSelector(state => state.auth);

  const [notifications, setNotifications] = useState(true);
  const [locationServices, setLocationServices] = useState(true);
  const [proximityAlerts, setProximityAlerts] = useState(true);
  const [emailNotifications, setEmailNotifications] = useState(false);

  const handleLogout = () => {
    Alert.alert(
      'Déconnexion',
      'Êtes-vous sûr de vouloir vous déconnecter ?',
      [
        {text: 'Annuler', style: 'cancel'},
        {
          text: 'Déconnexion',
          style: 'destructive',
          onPress: () => {
            dispatch(logout());
            navigation.reset({
              index: 0,
              routes: [{name: 'Login' as never}],
            });
          },
        },
      ],
    );
  };

  const handleDeleteAccount = () => {
    Alert.alert(
      'Supprimer le compte',
      'Cette action est irréversible. Toutes vos données seront supprimées définitivement.',
      [
        {text: 'Annuler', style: 'cancel'},
        {
          text: 'Supprimer',
          style: 'destructive',
          onPress: () => {
            // Implement account deletion
            Alert.alert('En développement', 'Cette fonctionnalité sera disponible bientôt');
          },
        },
      ],
    );
  };

  const SettingItem = ({
    icon,
    title,
    subtitle,
    onPress,
    showChevron = true,
    danger = false,
  }: any) => (
    <TouchableOpacity style={styles.settingItem} onPress={onPress}>
      <View style={[styles.settingIcon, danger && styles.settingIconDanger]}>
        <Icon name={icon} size={24} color={danger ? Colors.error : Colors.primary} />
      </View>
      <View style={styles.settingContent}>
        <Text style={[styles.settingTitle, danger && styles.settingTitleDanger]}>
          {title}
        </Text>
        {subtitle && <Text style={styles.settingSubtitle}>{subtitle}</Text>}
      </View>
      {showChevron && (
        <Icon name="chevron-forward" size={20} color={Colors.textLight} />
      )}
    </TouchableOpacity>
  );

  const SettingSwitch = ({icon, title, subtitle, value, onValueChange}: any) => (
    <View style={styles.settingItem}>
      <View style={styles.settingIcon}>
        <Icon name={icon} size={24} color={Colors.primary} />
      </View>
      <View style={styles.settingContent}>
        <Text style={styles.settingTitle}>{title}</Text>
        {subtitle && <Text style={styles.settingSubtitle}>{subtitle}</Text>}
      </View>
      <Switch
        value={value}
        onValueChange={onValueChange}
        trackColor={{false: Colors.border, true: Colors.primaryLight}}
        thumbColor={value ? Colors.primary : Colors.textLight}
      />
    </View>
  );

  return (
    <ScrollView style={styles.container}>
      {/* Profile Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Profil</Text>
        <SettingItem
          icon="person-outline"
          title="Informations personnelles"
          subtitle="Nom, prénom, téléphone"
          onPress={() => navigation.navigate('EditProfile' as never)}
        />
        <SettingItem
          icon="card-outline"
          title="Moyens de paiement"
          subtitle="Gérer vos cartes bancaires"
          onPress={() => navigation.navigate('PaymentMethods' as never)}
        />
        <SettingItem
          icon="location-outline"
          title="Adresses"
          subtitle="Gérer vos adresses"
          onPress={() => navigation.navigate('Addresses' as never)}
        />
      </View>

      {/* Notifications Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Notifications</Text>
        <SettingSwitch
          icon="notifications-outline"
          title="Notifications push"
          subtitle="Recevoir des notifications sur votre appareil"
          value={notifications}
          onValueChange={setNotifications}
        />
        <SettingSwitch
          icon="mail-outline"
          title="Notifications email"
          subtitle="Recevoir des emails promotionnels"
          value={emailNotifications}
          onValueChange={setEmailNotifications}
        />
        <SettingSwitch
          icon="navigate-outline"
          title="Alertes de proximité"
          subtitle="Être averti des avantages à proximité"
          value={proximityAlerts}
          onValueChange={setProximityAlerts}
        />
      </View>

      {/* Privacy Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Confidentialité</Text>
        <SettingSwitch
          icon="location-outline"
          title="Services de localisation"
          subtitle="Autoriser l'accès à votre position"
          value={locationServices}
          onValueChange={setLocationServices}
        />
        <SettingItem
          icon="shield-checkmark-outline"
          title="Confidentialité"
          subtitle="Gérer vos préférences de confidentialité"
          onPress={() => navigation.navigate('Privacy' as never)}
        />
        <SettingItem
          icon="lock-closed-outline"
          title="Sécurité"
          subtitle="Mot de passe, authentification"
          onPress={() => navigation.navigate('Security' as never)}
        />
      </View>

      {/* App Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Application</Text>
        <SettingItem
          icon="language-outline"
          title="Langue"
          subtitle="Français"
          onPress={() => Alert.alert('Langue', 'Fonctionnalité à venir')}
        />
        <SettingItem
          icon="moon-outline"
          title="Thème"
          subtitle="Clair"
          onPress={() => Alert.alert('Thème', 'Fonctionnalité à venir')}
        />
        <SettingItem
          icon="download-outline"
          title="Stockage et données"
          subtitle="Gérer le cache et les téléchargements"
          onPress={() => Alert.alert('Stockage', 'Fonctionnalité à venir')}
        />
      </View>

      {/* Support Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Support</Text>
        <SettingItem
          icon="help-circle-outline"
          title="Centre d'aide"
          subtitle="FAQ et guides"
          onPress={() => navigation.navigate('Help' as never)}
        />
        <SettingItem
          icon="chatbubble-outline"
          title="Contactez-nous"
          subtitle="Envoyer un message"
          onPress={() => navigation.navigate('Contact' as never)}
        />
        <SettingItem
          icon="star-outline"
          title="Noter l'application"
          subtitle="Donnez votre avis sur FreeOui"
          onPress={() => Alert.alert('Merci!', 'Fonctionnalité à venir')}
        />
      </View>

      {/* Legal Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Légal</Text>
        <SettingItem
          icon="document-text-outline"
          title="Conditions d'utilisation"
          onPress={() => navigation.navigate('Terms' as never)}
        />
        <SettingItem
          icon="document-text-outline"
          title="Politique de confidentialité"
          onPress={() => navigation.navigate('PrivacyPolicy' as never)}
        />
        <SettingItem
          icon="information-circle-outline"
          title="À propos"
          subtitle="Version 1.0.0"
          onPress={() => navigation.navigate('About' as never)}
        />
      </View>

      {/* Danger Zone */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Zone dangereuse</Text>
        <SettingItem
          icon="log-out-outline"
          title="Déconnexion"
          onPress={handleLogout}
          showChevron={false}
          danger
        />
        <SettingItem
          icon="trash-outline"
          title="Supprimer le compte"
          subtitle="Action irréversible"
          onPress={handleDeleteAccount}
          showChevron={false}
          danger
        />
      </View>

      <View style={styles.footer}>
        <Text style={styles.footerText}>FreeOui v1.0.0</Text>
        <Text style={styles.footerText}>© 2024 FreeOui. Tous droits réservés.</Text>
      </View>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  section: {
    marginTop: Theme.spacing.lg,
  },
  sectionTitle: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.textLight,
    textTransform: 'uppercase',
    letterSpacing: 1,
    paddingHorizontal: Theme.spacing.lg,
    marginBottom: Theme.spacing.sm,
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    paddingVertical: Theme.spacing.md,
    paddingHorizontal: Theme.spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: Colors.border,
  },
  settingIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: Colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: Theme.spacing.md,
  },
  settingIconDanger: {
    backgroundColor: Colors.errorLight,
  },
  settingContent: {
    flex: 1,
  },
  settingTitle: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
  },
  settingTitleDanger: {
    color: Colors.error,
  },
  settingSubtitle: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginTop: 2,
  },
  footer: {
    alignItems: 'center',
    paddingVertical: Theme.spacing.xxl,
  },
  footerText: {
    fontSize: Theme.fontSize.xs,
    color: Colors.textLight,
    marginBottom: 4,
  },
});

export default SettingsScreen;
