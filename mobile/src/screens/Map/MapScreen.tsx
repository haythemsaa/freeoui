import React, {useEffect, useState, useRef} from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  ScrollView,
  Image,
  Dimensions,
  Alert,
} from 'react-native';
import MapView, {Marker, PROVIDER_GOOGLE, Callout, Circle} from 'react-native-maps';
import Geolocation from '@react-native-community/geolocation';
import {useNavigation} from '@react-navigation/native';
import {useAppDispatch, useAppSelector} from '../../store';
import {fetchAdvantages} from '../../store/slices/advantageSlice';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const {width} = Dimensions.get('window');

interface Location {
  latitude: number;
  longitude: number;
}

const MapScreen = () => {
  const navigation = useNavigation();
  const dispatch = useAppDispatch();
  const {advantages} = useAppSelector(state => state.advantages);
  const mapRef = useRef<MapView>(null);

  const [userLocation, setUserLocation] = useState<Location | null>(null);
  const [selectedMerchant, setSelectedMerchant] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [radius, setRadius] = useState(5); // km
  const [showRadius, setShowRadius] = useState(false);

  useEffect(() => {
    requestLocationPermission();
    dispatch(fetchAdvantages());
  }, [dispatch]);

  const requestLocationPermission = () => {
    Geolocation.getCurrentPosition(
      position => {
        const {latitude, longitude} = position.coords;
        setUserLocation({latitude, longitude});
        setLoading(false);
      },
      error => {
        console.error('Geolocation error:', error);
        Alert.alert(
          'Erreur de localisation',
          'Impossible d\'obtenir votre position. Veuillez activer la géolocalisation.',
        );
        // Default to Tunis center
        setUserLocation({latitude: 36.8065, longitude: 10.1815});
        setLoading(false);
      },
      {enableHighAccuracy: true, timeout: 15000, maximumAge: 10000},
    );
  };

  const centerOnUser = () => {
    if (userLocation && mapRef.current) {
      mapRef.current.animateToRegion({
        latitude: userLocation.latitude,
        longitude: userLocation.longitude,
        latitudeDelta: 0.05,
        longitudeDelta: 0.05,
      });
    }
  };

  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number) => {
    const R = 6371; // Earth radius in km
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  };

  const nearbyAdvantages = advantages.filter(adv => {
    if (!userLocation || !adv.merchant.latitude || !adv.merchant.longitude) {
      return false;
    }
    const distance = calculateDistance(
      userLocation.latitude,
      userLocation.longitude,
      adv.merchant.latitude,
      adv.merchant.longitude,
    );
    return distance <= radius;
  });

  const getMerchantColor = (category: string) => {
    const colors: {[key: string]: string} = {
      restaurant: '#EF4444',
      cafe: '#F59E0B',
      shopping: '#8B5CF6',
      beauty: '#EC4899',
      sport: '#10B981',
      entertainment: '#3B82F6',
      health: '#06B6D4',
    };
    return colors[category.toLowerCase()] || Colors.primary;
  };

  if (loading || !userLocation) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={Colors.primary} />
        <Text style={styles.loadingText}>Chargement de la carte...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <MapView
        ref={mapRef}
        provider={PROVIDER_GOOGLE}
        style={styles.map}
        initialRegion={{
          latitude: userLocation.latitude,
          longitude: userLocation.longitude,
          latitudeDelta: 0.05,
          longitudeDelta: 0.05,
        }}
        showsUserLocation
        showsMyLocationButton={false}
        showsCompass
        onMarkerPress={e => {
          const merchant = e.nativeEvent;
          setSelectedMerchant(merchant);
        }}>
        {/* Radius Circle */}
        {showRadius && (
          <Circle
            center={userLocation}
            radius={radius * 1000}
            strokeColor="rgba(79, 70, 229, 0.5)"
            fillColor="rgba(79, 70, 229, 0.1)"
          />
        )}

        {/* Merchant Markers */}
        {nearbyAdvantages.map((advantage, index) => {
          if (!advantage.merchant.latitude || !advantage.merchant.longitude) {
            return null;
          }
          return (
            <Marker
              key={`${advantage.merchant.id}-${index}`}
              coordinate={{
                latitude: advantage.merchant.latitude,
                longitude: advantage.merchant.longitude,
              }}
              pinColor={getMerchantColor(advantage.merchant.category || '')}
              onPress={() => setSelectedMerchant(advantage)}>
              <View
                style={[
                  styles.markerContainer,
                  {backgroundColor: getMerchantColor(advantage.merchant.category || '')},
                ]}>
                <Icon name="storefront" size={16} color="#FFF" />
                <View style={styles.discountBubble}>
                  <Text style={styles.discountBubbleText}>
                    -{advantage.discount_percentage}%
                  </Text>
                </View>
              </View>
              <Callout>
                <View style={styles.calloutContainer}>
                  <Text style={styles.calloutTitle}>{advantage.merchant.name}</Text>
                  <Text style={styles.calloutSubtitle}>{advantage.title}</Text>
                </View>
              </Callout>
            </Marker>
          );
        })}
      </MapView>

      {/* Top Controls */}
      <View style={styles.topControls}>
        <View style={styles.controlCard}>
          <Text style={styles.controlLabel}>Rayon de recherche</Text>
          <View style={styles.radiusControls}>
            <TouchableOpacity
              style={styles.radiusButton}
              onPress={() => setRadius(Math.max(1, radius - 1))}>
              <Icon name="remove" size={20} color={Colors.primary} />
            </TouchableOpacity>
            <Text style={styles.radiusText}>{radius} km</Text>
            <TouchableOpacity
              style={styles.radiusButton}
              onPress={() => setRadius(Math.min(20, radius + 1))}>
              <Icon name="add" size={20} color={Colors.primary} />
            </TouchableOpacity>
          </View>
          <TouchableOpacity
            style={styles.toggleRadiusButton}
            onPress={() => setShowRadius(!showRadius)}>
            <Icon
              name={showRadius ? 'eye' : 'eye-off'}
              size={16}
              color={Colors.primary}
            />
            <Text style={styles.toggleRadiusText}>
              {showRadius ? 'Masquer' : 'Afficher'} le rayon
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* My Location Button */}
      <TouchableOpacity style={styles.myLocationButton} onPress={centerOnUser}>
        <Icon name="locate" size={24} color={Colors.primary} />
      </TouchableOpacity>

      {/* Bottom Sheet */}
      {selectedMerchant && (
        <View style={styles.bottomSheet}>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={styles.bottomScroll}>
            <TouchableOpacity
              style={styles.advantageCardHorizontal}
              onPress={() => {
                navigation.navigate(
                  'AdvantageDetail' as never,
                  {id: selectedMerchant.id} as never,
                );
              }}>
              <Image
                source={{
                  uri:
                    selectedMerchant.image_url ||
                    'https://via.placeholder.com/300',
                }}
                style={styles.advantageImageHorizontal}
              />
              <View style={styles.advantageContentHorizontal}>
                <View style={styles.discountBadgeHorizontal}>
                  <Text style={styles.discountTextHorizontal}>
                    -{selectedMerchant.discount_percentage}%
                  </Text>
                </View>
                <Text style={styles.advantageTitleHorizontal}>
                  {selectedMerchant.title}
                </Text>
                <Text style={styles.merchantNameHorizontal}>
                  {selectedMerchant.merchant.name}
                </Text>
                <View style={styles.infoRowHorizontal}>
                  <Icon name="location" size={14} color={Colors.primary} />
                  <Text style={styles.infoTextHorizontal}>
                    {userLocation &&
                    selectedMerchant.merchant.latitude &&
                    selectedMerchant.merchant.longitude
                      ? calculateDistance(
                          userLocation.latitude,
                          userLocation.longitude,
                          selectedMerchant.merchant.latitude,
                          selectedMerchant.merchant.longitude,
                        ).toFixed(1)
                      : '0.0'}{' '}
                    km
                  </Text>
                </View>
                <TouchableOpacity
                  style={styles.directionButton}
                  onPress={() => {
                    // Open directions in external map app
                    Alert.alert('Directions', 'Ouvrir dans Google Maps');
                  }}>
                  <Icon name="navigate" size={16} color="#FFF" />
                  <Text style={styles.directionButtonText}>Itinéraire</Text>
                </TouchableOpacity>
              </View>
            </TouchableOpacity>
          </ScrollView>

          <TouchableOpacity
            style={styles.closeBottomSheet}
            onPress={() => setSelectedMerchant(null)}>
            <Icon name="close" size={20} color={Colors.text} />
          </TouchableOpacity>
        </View>
      )}

      {/* Stats Badge */}
      <View style={styles.statsBadge}>
        <Icon name="storefront" size={16} color={Colors.primary} />
        <Text style={styles.statsBadgeText}>
          {nearbyAdvantages.length} avantages à proximité
        </Text>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  map: {
    flex: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: Colors.background,
  },
  loadingText: {
    marginTop: Theme.spacing.md,
    fontSize: Theme.fontSize.md,
    color: Colors.textLight,
  },
  markerContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: '#FFF',
    ...Theme.shadows.md,
  },
  discountBubble: {
    position: 'absolute',
    top: -8,
    right: -8,
    backgroundColor: Colors.error,
    borderRadius: 10,
    paddingHorizontal: 4,
    paddingVertical: 2,
    minWidth: 24,
    alignItems: 'center',
  },
  discountBubbleText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: Theme.fontWeight.bold,
  },
  calloutContainer: {
    width: 200,
    padding: Theme.spacing.sm,
  },
  calloutTitle: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginBottom: 4,
  },
  calloutSubtitle: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
  },
  topControls: {
    position: 'absolute',
    top: Theme.spacing.md,
    left: Theme.spacing.md,
    right: Theme.spacing.md,
  },
  controlCard: {
    backgroundColor: '#FFF',
    borderRadius: Theme.borderRadius.lg,
    padding: Theme.spacing.md,
    ...Theme.shadows.md,
  },
  controlLabel: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginBottom: Theme.spacing.sm,
  },
  radiusControls: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  radiusButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: Colors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  radiusText: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: Colors.text,
    marginHorizontal: Theme.spacing.lg,
    minWidth: 60,
    textAlign: 'center',
  },
  toggleRadiusButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: Theme.spacing.sm,
    paddingVertical: Theme.spacing.xs,
  },
  toggleRadiusText: {
    fontSize: Theme.fontSize.sm,
    color: Colors.primary,
    marginLeft: Theme.spacing.xs,
  },
  myLocationButton: {
    position: 'absolute',
    bottom: 200,
    right: Theme.spacing.md,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#FFF',
    justifyContent: 'center',
    alignItems: 'center',
    ...Theme.shadows.lg,
  },
  statsBadge: {
    position: 'absolute',
    bottom: 180,
    left: Theme.spacing.md,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFF',
    paddingHorizontal: Theme.spacing.md,
    paddingVertical: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.full,
    ...Theme.shadows.md,
  },
  statsBadgeText: {
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginLeft: Theme.spacing.xs,
  },
  bottomSheet: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#FFF',
    borderTopLeftRadius: Theme.borderRadius.xl,
    borderTopRightRadius: Theme.borderRadius.xl,
    paddingTop: Theme.spacing.md,
    paddingBottom: Theme.spacing.lg,
    ...Theme.shadows.xl,
  },
  bottomScroll: {
    paddingHorizontal: Theme.spacing.md,
  },
  advantageCardHorizontal: {
    width: width - 32,
    flexDirection: 'row',
    backgroundColor: '#FFF',
    borderRadius: Theme.borderRadius.lg,
    overflow: 'hidden',
  },
  advantageImageHorizontal: {
    width: 120,
    height: 140,
    backgroundColor: Colors.background,
  },
  advantageContentHorizontal: {
    flex: 1,
    padding: Theme.spacing.md,
  },
  discountBadgeHorizontal: {
    alignSelf: 'flex-start',
    backgroundColor: Colors.error,
    paddingHorizontal: Theme.spacing.sm,
    paddingVertical: 4,
    borderRadius: Theme.borderRadius.md,
    marginBottom: Theme.spacing.xs,
  },
  discountTextHorizontal: {
    color: '#FFF',
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.bold,
  },
  advantageTitleHorizontal: {
    fontSize: Theme.fontSize.md,
    fontWeight: Theme.fontWeight.semibold,
    color: Colors.text,
    marginBottom: 4,
  },
  merchantNameHorizontal: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginBottom: Theme.spacing.sm,
  },
  infoRowHorizontal: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: Theme.spacing.sm,
  },
  infoTextHorizontal: {
    fontSize: Theme.fontSize.sm,
    color: Colors.textLight,
    marginLeft: 4,
  },
  directionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.primary,
    paddingVertical: Theme.spacing.sm,
    borderRadius: Theme.borderRadius.md,
  },
  directionButtonText: {
    color: '#FFF',
    fontSize: Theme.fontSize.sm,
    fontWeight: Theme.fontWeight.semibold,
    marginLeft: Theme.spacing.xs,
  },
  closeBottomSheet: {
    position: 'absolute',
    top: Theme.spacing.md,
    right: Theme.spacing.md,
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: Colors.background,
    justifyContent: 'center',
    alignItems: 'center',
  },
});

export default MapScreen;
