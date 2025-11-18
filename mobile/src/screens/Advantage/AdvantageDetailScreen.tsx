import React, {useEffect, useState} from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Share,
  Linking,
} from 'react-native';
import {useRoute, useNavigation} from '@react-navigation/native';
import {advantageService} from '../../services';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const AdvantageDetailScreen = () => {
  const route = useRoute();
  const navigation = useNavigation();
  const {id} = route.params as {id: string};

  const [advantage, setAdvantage] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [isFavorite, setIsFavorite] = useState(false);
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    fetchAdvantageDetail();
  }, [id]);

  const fetchAdvantageDetail = async () => {
    try {
      setLoading(true);
      const response = await advantageService.getAdvantageById(id);
      setAdvantage(response.data);
      setIsFavorite(response.data.is_favorite || false);
    } catch (error) {
      console.error('Error fetching advantage:', error);
      Alert.alert('Erreur', 'Impossible de charger l\'avantage');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  };

  const toggleFavorite = async () => {
    try {
      setProcessing(true);
      if (isFavorite) {
        await advantageService.removeFromFavorites(id);
        setIsFavorite(false);
      } else {
        await advantageService.addToFavorites(id);
        setIsFavorite(true);
      }
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de modifier les favoris');
    } finally {
      setProcessing(false);
    }
  };

  const handleShare = async () => {
    try {
      await Share.share({
        message: `Découvre cet avantage sur FreeOui: ${advantage.title} - ${advantage.discount_percentage}% de réduction chez ${advantage.merchant.name}!`,
      });
    } catch (error) {
      console.error('Error sharing:', error);
    }
  };

  const handleGenerateQR = () => {
    navigation.navigate('QRGenerator' as never, {advantageId: id} as never);
  };

  const handleOpenMap = () => {
    if (advantage.merchant.latitude && advantage.merchant.longitude) {
      const url = `https://www.google.com/maps/dir/?api=1&destination=${advantage.merchant.latitude},${advantage.merchant.longitude}`;
      Linking.openURL(url);
    }
  };

  const handleCallMerchant = () => {
    if (advantage.merchant.phone) {
      Linking.openURL(`tel:${advantage.merchant.phone}`);
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={Colors.primary} />
      </View>
    );
  }

  if (!advantage) {
    return null;
  }

  return (
    <View style={styles.container}>
      <ScrollView>
        {/* Image Header */}
        <View style={styles.imageContainer}>
          <Image
            source={{uri: advantage.image_url || 'https://via.placeholder.com/600'}}
            style={styles.image}
          />
          <TouchableOpacity
            style={styles.backButton}
            onPress={() => navigation.goBack()}>
            <Icon name="arrow-back" size={24} color="#FFF" />
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.shareButton}
            onPress={handleShare}>
            <Icon name="share-social" size={24} color="#FFF" />
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.favoriteButton}
            onPress={toggleFavorite}
            disabled={processing}>
            <Icon
              name={isFavorite ? 'heart' : 'heart-outline'}
              size={24}
              color={isFavorite ? Colors.error : '#FFF'}
            />
          </TouchableOpacity>
          <View style={styles.discountBadgeLarge}>
            <Text style={styles.discountTextLarge}>
              -{advantage.discount_percentage}%
            </Text>
            <Text style={styles.discountSubtext}>DE RÉDUCTION</Text>
          </View>
        </View>

        {/* Content */}
        <View style={styles.content}>
          {/* Title & Merchant */}
          <View style={styles.titleSection}>
            <Text style={styles.title}>{advantage.title}</Text>
            <View style={styles.merchantRow}>
              <Icon name="storefront" size={20} color={Colors.primary} />
              <Text style={styles.merchantName}>{advantage.merchant.name}</Text>
            </View>
          </View>

          {/* Stats */}
          <View style={styles.statsRow}>
            {advantage.points_required && (
              <View style={styles.statItem}>
                <Icon name="trophy" size={18} color={Colors.secondary} />
                <Text style={styles.statText}>{advantage.points_required} pts</Text>
              </View>
            )}
            {advantage.distance && (
              <View style={styles.statItem}>
                <Icon name="location" size={18} color={Colors.primary} />
                <Text style={styles.statText}>{advantage.distance.toFixed(1)} km</Text>
              </View>
            )}
            {advantage.usage_count && (
              <View style={styles.statItem}>
                <Icon name="people" size={18} color={Colors.warning} />
                <Text style={styles.statText}>{advantage.usage_count} utilisations</Text>
              </View>
            )}
          </View>

          {/* Validity */}
          {advantage.valid_until && (
            <View style={styles.validityCard}>
              <Icon name="time" size={20} color={Colors.warning} />
              <View style={styles.validityContent}>
                <Text style={styles.validityLabel}>Valable jusqu'au</Text>
                <Text style={styles.validityDate}>
                  {new Date(advantage.valid_until).toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                  })}
                </Text>
              </View>
            </View>
          )}

          {/* Description */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Description</Text>
            <Text style={styles.description}>{advantage.description}</Text>
          </View>

          {/* Conditions */}
          {advantage.conditions && (
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Conditions d'utilisation</Text>
              <Text style={styles.conditionsText}>{advantage.conditions}</Text>
            </View>
          )}

          {/* Merchant Info */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>À propos du commerçant</Text>
            <View style={styles.merchantCard}>
              <View style={styles.merchantHeader}>
                <View style={styles.merchantAvatar}>
                  <Icon name="business" size={24} color={Colors.primary} />
                </View>
                <View style={styles.merchantInfo}>
                  <Text style={styles.merchantCardName}>
                    {advantage.merchant.name}
                  </Text>
                  <Text style={styles.merchantCategory}>
                    {advantage.merchant.category}
                  </Text>
                </View>
              </View>

              {advantage.merchant.address && (
                <View style={styles.infoRow}>
                  <Icon name="location-outline" size={16} color={Colors.textLight} />
                  <Text style={styles.infoText}>{advantage.merchant.address}</Text>
                </View>
              )}

              {advantage.merchant.phone && (
                <TouchableOpacity
                  style={styles.infoRow}
                  onPress={handleCallMerchant}>
                  <Icon name="call-outline" size={16} color={Colors.textLight} />
                  <Text style={[styles.infoText, styles.linkText]}>
                    {advantage.merchant.phone}
                  </Text>
                </TouchableOpacity>
              )}

              {advantage.merchant.opening_hours && (
                <View style={styles.infoRow}>
                  <Icon name="time-outline" size={16} color={Colors.textLight} />
                  <Text style={styles.infoText}>
                    {advantage.merchant.opening_hours}
                  </Text>
                </View>
              )}
            </View>
          </View>
        </View>
      </ScrollView>

      {/* Bottom Actions */}
      <View style={styles.bottomActions}>
        <TouchableOpacity
          style={styles.secondaryButton}
          onPress={handleOpenMap}>
          <Icon name="navigate" size={20} color={Colors.primary} />
          <Text style={styles.secondaryButtonText}>Itinéraire</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.primaryButton}
          onPress={handleGenerateQR}>
          <Icon name="qr-code" size={20} color="#FFF" />
          <Text style={styles.primaryButtonText}>Utiliser</Text>
        </TouchableOpacity>
      </View>
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
  imageContainer: {
    position: 'relative',
    height: 300,
  },
  image: {
    width: '100%',
    height: '100%',
    backgroundColor: Colors.border,
  },
  backButton: {
    position: 'absolute',
    top: 50,
    left: Theme.spacing.md,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  shareButton: {
    position: 'absolute',
    top: 50,
    right: Theme.spacing.md,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  favoriteButton: {
    position: 'absolute',
    top: 50,
    right: 64,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  discountBadgeLarge: {
    position: 'absolute',
    bottom: Theme.spacing.md,
    left: Theme.spacing.md,
    backgroundColor: Colors.error,
    paddingHorizontal: Theme.spacing.lg,
    paddingVertical: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
    alignItems: 'center',
  },
  discountTextLarge: {
    color: '#FFF',
    fontSize: 32,
    fontWeight: Theme.fontWeight.bold,
  },
  discountSubtext: {
    color: '#FFF',
    fontSize: Theme.fontSize.xs,
    fontWeight: Theme.fontWeight.semibold,
    marginTop: 4,
  },
  content: {
    padding: Theme.spacing.lg,
  },
  titleSection: {
    marginBottom: Theme.spacing.lg,
  },
  title: {
    fontSize: Theme.fontSize.xxl,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginBottom: Theme.spacing.sm,
  },
  merchantRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  merchantName: {
    fontSize: Theme.fontSize.lg,
    color: Colors.primary,
    fontWeight: Theme.fontWeight.semibold,
    marginLeft: Theme.spacing.xs,
  },
  statsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: Theme.spacing.lg,
  },
  statItem: {
    flexDirection: 'row',
    alignItems: 'center',
    marginRight: Theme.spacing.lg,
    marginBottom: Theme.spacing.sm,
  },
  statText: {
    fontSize: Theme.fontSize.sm,
    color: Colors.text,
    marginLeft: Theme.spacing.xs,
    fontWeight: Theme.fontWeight.medium,
  },
  validityCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.warningLight,
    padding: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
    marginBottom: Theme.spacing.lg,
  },
  validityContent: {
    marginLeft: Theme.spacing.sm,
    flex: 1,
  },
  validityLabel: {
    fontSize: Theme.fontSize.sm,
    color: Colors.warning,
    fontWeight: Theme.fontWeight.medium,
  },
  validityDate: {
    fontSize: Theme.fontSize.md,
    color: Colors.warning,
    fontWeight: Theme.fontWeight.bold,
    marginTop: 2,
  },
  section: {
    marginBottom: Theme.spacing.xl,
  },
  sectionTitle: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginBottom: Theme.spacing.md,
  },
  description: {
    fontSize: Theme.fontSize.md,
    color: Colors.textLight,
    lineHeight: 24,
  },
  conditionsText: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    lineHeight: 20,
  },
  merchantCard: {
    backgroundColor: '#FFF',
    borderRadius: Theme.borderRadius.lg,
    padding: Theme.spacing.md,
    ...Theme.shadows.sm,
  },
  merchantHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: Theme.spacing.md,
  },
  merchantAvatar: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: Colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  merchantInfo: {
    marginLeft: Theme.spacing.md,
    flex: 1,
  },
  merchantCardName: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
  },
  merchantCategory: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginTop: 2,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: Theme.spacing.sm,
  },
  infoText: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginLeft: Theme.spacing.sm,
    flex: 1,
  },
  linkText: {
    color: Colors.primary,
    textDecorationLine: 'underline',
  },
  bottomActions: {
    flexDirection: 'row',
    padding: Theme.spacing.md,
    backgroundColor: '#FFF',
    borderTopWidth: 1,
    borderTopColor: Colors.border,
    ...Theme.shadows.lg,
  },
  secondaryButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: Theme.spacing.md,
    marginRight: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.lg,
    borderWidth: 2,
    borderColor: Colors.primary,
    backgroundColor: '#FFF',
  },
  secondaryButtonText: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.primary,
    marginLeft: Theme.spacing.xs,
  },
  primaryButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: Theme.spacing.md,
    marginLeft: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.lg,
    backgroundColor: Colors.primary,
  },
  primaryButtonText: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
    marginLeft: Theme.spacing.xs,
  },
});

export default AdvantageDetailScreen;
