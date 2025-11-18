import React, {useState, useEffect} from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  Vibration,
  Animated,
} from 'react-native';
import {RNCamera} from 'react-native-camera';
import {useNavigation} from '@react-navigation/native';
import {qrCodeService} from '../../services';
import {Colors} from '../../constants/colors';
import {Theme} from '../../constants/theme';
import Icon from 'react-native-vector-icons/Ionicons';

const QRScannerScreen = () => {
  const navigation = useNavigation();
  const [scanned, setScanned] = useState(false);
  const [flashOn, setFlashOn] = useState(false);
  const [scanning, setScanning] = useState(false);
  const scanLineAnim = useState(new Animated.Value(0))[0];

  useEffect(() => {
    // Animate scan line
    Animated.loop(
      Animated.sequence([
        Animated.timing(scanLineAnim, {
          toValue: 1,
          duration: 2000,
          useNativeDriver: true,
        }),
        Animated.timing(scanLineAnim, {
          toValue: 0,
          duration: 2000,
          useNativeDriver: true,
        }),
      ]),
    ).start();
  }, []);

  const handleBarCodeScanned = async ({data}: {data: string}) => {
    if (scanned || scanning) return;

    setScanned(true);
    setScanning(true);
    Vibration.vibrate(200);

    try {
      const response = await qrCodeService.validateQR(data);

      if (response.success) {
        Alert.alert(
          '✅ QR Code Validé!',
          `Avantage: ${response.data.advantage?.title}\nPoints gagnés: +${response.data.points_earned || 0}`,
          [
            {
              text: 'OK',
              onPress: () => {
                navigation.goBack();
              },
            },
          ],
        );
      } else {
        Alert.alert(
          '❌ QR Code Invalide',
          response.message || 'Ce QR code n\'est pas valide ou a expiré.',
          [
            {
              text: 'Réessayer',
              onPress: () => {
                setScanned(false);
                setScanning(false);
              },
            },
            {
              text: 'Annuler',
              onPress: () => navigation.goBack(),
              style: 'cancel',
            },
          ],
        );
      }
    } catch (error: any) {
      Alert.alert(
        'Erreur',
        error.response?.data?.message || 'Impossible de valider le QR code',
        [
          {
            text: 'Réessayer',
            onPress: () => {
              setScanned(false);
              setScanning(false);
            },
          },
          {
            text: 'Annuler',
            onPress: () => navigation.goBack(),
            style: 'cancel',
          },
        ],
      );
    }
  };

  const scanLineTranslate = scanLineAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 250],
  });

  return (
    <View style={styles.container}>
      <RNCamera
        style={styles.camera}
        type={RNCamera.Constants.Type.back}
        flashMode={
          flashOn
            ? RNCamera.Constants.FlashMode.torch
            : RNCamera.Constants.FlashMode.off
        }
        onBarCodeRead={handleBarCodeScanned}
        barCodeTypes={[RNCamera.Constants.BarCodeType.qr]}
        captureAudio={false}>
        {/* Header */}
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.headerButton}
            onPress={() => navigation.goBack()}>
            <Icon name="close" size={28} color="#FFF" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Scanner QR Code</Text>
          <TouchableOpacity
            style={styles.headerButton}
            onPress={() => setFlashOn(!flashOn)}>
            <Icon
              name={flashOn ? 'flash' : 'flash-off'}
              size={28}
              color="#FFF"
            />
          </TouchableOpacity>
        </View>

        {/* Scan Area */}
        <View style={styles.scanArea}>
          <View style={styles.overlay}>
            {/* Top Overlay */}
            <View style={styles.overlayTop} />

            {/* Middle Row */}
            <View style={styles.overlayMiddle}>
              <View style={styles.overlaySide} />

              {/* Scan Frame */}
              <View style={styles.scanFrame}>
                {/* Corner decorations */}
                <View style={[styles.corner, styles.cornerTopLeft]} />
                <View style={[styles.corner, styles.cornerTopRight]} />
                <View style={[styles.corner, styles.cornerBottomLeft]} />
                <View style={[styles.corner, styles.cornerBottomRight]} />

                {/* Animated scan line */}
                {!scanned && (
                  <Animated.View
                    style={[
                      styles.scanLine,
                      {
                        transform: [{translateY: scanLineTranslate}],
                      },
                    ]}
                  />
                )}
              </View>

              <View style={styles.overlaySide} />
            </View>

            {/* Bottom Overlay */}
            <View style={styles.overlayBottom} />
          </View>

          {/* Instructions */}
          <View style={styles.instructionsContainer}>
            <Icon name="qr-code" size={48} color="#FFF" />
            <Text style={styles.instructionsTitle}>
              {scanned ? 'Validation en cours...' : 'Scannez un QR Code'}
            </Text>
            <Text style={styles.instructionsText}>
              {scanned
                ? 'Veuillez patienter'
                : 'Placez le QR code dans le cadre'}
            </Text>
          </View>
        </View>

        {/* Bottom Tips */}
        <View style={styles.bottomContainer}>
          <View style={styles.tipCard}>
            <Icon name="information-circle" size={20} color={Colors.primary} />
            <Text style={styles.tipText}>
              Assurez-vous d'avoir une bonne luminosité
            </Text>
          </View>
        </View>
      </RNCamera>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#000',
  },
  camera: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 50,
    paddingHorizontal: Theme.spacing.md,
    paddingBottom: Theme.spacing.md,
  },
  headerButton: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: Theme.fontSize.lg,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
  },
  scanArea: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  overlay: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
  },
  overlayTop: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
  },
  overlayMiddle: {
    flexDirection: 'row',
    height: 250,
  },
  overlaySide: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
  },
  overlayBottom: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.6)',
  },
  scanFrame: {
    width: 250,
    height: 250,
    borderWidth: 2,
    borderColor: '#FFF',
    borderRadius: Theme.borderRadius.lg,
    position: 'relative',
  },
  corner: {
    position: 'absolute',
    width: 30,
    height: 30,
    borderColor: Colors.primary,
  },
  cornerTopLeft: {
    top: -2,
    left: -2,
    borderTopWidth: 4,
    borderLeftWidth: 4,
    borderTopLeftRadius: Theme.borderRadius.lg,
  },
  cornerTopRight: {
    top: -2,
    right: -2,
    borderTopWidth: 4,
    borderRightWidth: 4,
    borderTopRightRadius: Theme.borderRadius.lg,
  },
  cornerBottomLeft: {
    bottom: -2,
    left: -2,
    borderBottomWidth: 4,
    borderLeftWidth: 4,
    borderBottomLeftRadius: Theme.borderRadius.lg,
  },
  cornerBottomRight: {
    bottom: -2,
    right: -2,
    borderBottomWidth: 4,
    borderRightWidth: 4,
    borderBottomRightRadius: Theme.borderRadius.lg,
  },
  scanLine: {
    position: 'absolute',
    left: 0,
    right: 0,
    height: 2,
    backgroundColor: Colors.primary,
    shadowColor: Colors.primary,
    shadowOffset: {width: 0, height: 0},
    shadowOpacity: 0.8,
    shadowRadius: 10,
    elevation: 5,
  },
  instructionsContainer: {
    marginTop: 40,
    alignItems: 'center',
    paddingHorizontal: Theme.spacing.xl,
  },
  instructionsTitle: {
    fontSize: Theme.fontSize.xl,
    fontWeight: Theme.fontWeight.bold,
    color: '#FFF',
    marginTop: Theme.spacing.md,
    textAlign: 'center',
  },
  instructionsText: {
    fontSize: Theme.fontSize.md,
    color: 'rgba(255, 255, 255, 0.8)',
    marginTop: Theme.spacing.xs,
    textAlign: 'center',
  },
  bottomContainer: {
    padding: Theme.spacing.lg,
    paddingBottom: 40,
  },
  tipCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.9)',
    padding: Theme.spacing.md,
    borderRadius: Theme.borderRadius.lg,
  },
  tipText: {
    flex: 1,
    fontSize: Theme.fontSize.sm,
    color: Colors.text,
    marginLeft: Theme.spacing.sm,
  },
});

export default QRScannerScreen;
