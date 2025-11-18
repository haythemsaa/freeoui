# 📱 FreeOui Mobile - React Native Application

Application mobile iOS & Android pour FreeOui - Découvrez les avantages locaux en Tunisie.

## 🚀 Fonctionnalités

### ✅ Implémenté
- ✅ **Authentication** (Login, Register, OTP)
- ✅ **Home Dashboard** avec recommandations AI
- ✅ **Profile** avec gamification stats
- ✅ **Redux State Management**
- ✅ **API Integration** complète
- ✅ **Navigation** (Stack + Bottom Tabs)
- ✅ **Thème** personnalisé avec Design System

### 🔄 À compléter
- ⏳ **Discover Screen** (Liste avantages + filtres)
- ⏳ **Map Screen** (React Native Maps + géolocalisation)
- ⏳ **QR Code Scanner** (Camera + validation)
- ⏳ **Stories** (Instagram-style)
- ⏳ **Chat** (Real-time messaging)
- ⏳ **Wallet & Payments**
- ⏳ **Gamification** (Challenges, Stickers, Leaderboards)
- ⏳ **Push Notifications** (FCM)

## 📋 Prérequis

- Node.js >= 18
- npm >= 9
- React Native CLI
- Xcode (pour iOS)
- Android Studio (pour Android)
- CocoaPods (pour iOS)

## 🛠️ Installation

### 1. Installer les dépendances

```bash
cd mobile
npm install
```

### 2. Configuration iOS

```bash
cd ios
pod install
cd ..
```

### 3. Configuration Android

Assurez-vous qu'Android Studio est installé et configuré.

### 4. Variables d'environnement

Créez un fichier `.env` à la racine du projet mobile :

```env
API_URL=http://10.0.2.2:8000/api/v1  # Android emulator
# API_URL=http://localhost:8000/api/v1  # iOS simulator
GOOGLE_MAPS_API_KEY=YOUR_KEY
FIREBASE_API_KEY=YOUR_KEY
```

## 🏃 Démarrage

### iOS

```bash
npm run ios
# ou pour un device spécifique
npx react-native run-ios --device "iPhone 14 Pro"
```

### Android

```bash
npm run android
# ou
npx react-native run-android
```

### Metro Bundler

```bash
npm start
```

## 📁 Structure du Projet

```
mobile/
├── src/
│   ├── components/        # Composants réutilisables
│   │   ├── common/       # Buttons, Inputs, Cards, etc.
│   │   └── cards/        # AdvantageCard, MerchantCard, etc.
│   ├── constants/        # Colors, Theme, Config
│   ├── hooks/           # Custom hooks
│   ├── navigation/      # React Navigation setup
│   ├── screens/         # Écrans de l'app
│   │   ├── Auth/       # Login, Register, OTP
│   │   ├── Home/       # Dashboard principal
│   │   ├── Discover/   # Liste avantages
│   │   ├── Map/        # Carte interactive
│   │   ├── Profile/    # Profil utilisateur
│   │   ├── QR/         # Scanner QR
│   │   ├── Gamification/ # Challenges, Stickers, Leaderboards
│   │   └── Wallet/     # Portefeuille
│   ├── services/       # API Services
│   │   ├── api.ts     # Axios client
│   │   ├── authService.ts
│   │   ├── advantageService.ts
│   │   ├── gamificationService.ts
│   │   └── walletService.ts
│   ├── store/         # Redux Store
│   │   ├── index.ts
│   │   └── slices/
│   │       ├── authSlice.ts
│   │       ├── advantageSlice.ts
│   │       ├── gamificationSlice.ts
│   │       └── walletSlice.ts
│   ├── types/        # TypeScript types
│   └── utils/        # Utilitaires
├── App.tsx          # Point d'entrée
├── package.json
└── tsconfig.json
```

## 🎨 Design System

### Colors

```typescript
import {Colors} from '@/constants/colors';

// Primary
Colors.primary      // #4F46E5 (Indigo)
Colors.secondary    // #10B981 (Green)
Colors.accent       // #F59E0B (Amber)

// Gamification
Colors.bronze       // #CD7F32
Colors.silver       // #C0C0C0
Colors.gold         // #FFD700
Colors.diamond      // #B9F2FF
```

### Theme

```typescript
import {Theme} from '@/constants/theme';

// Spacing
Theme.spacing.sm    // 8
Theme.spacing.md    // 16
Theme.spacing.lg    // 24

// Border Radius
Theme.borderRadius.md   // 8
Theme.borderRadius.lg   // 12

// Font Size
Theme.fontSize.sm   // 14
Theme.fontSize.md   // 16
Theme.fontSize.lg   // 18
```

## 🔌 API Integration

### Utilisation des services

```typescript
import {authService, advantageService} from '@/services';

// Login
const response = await authService.login({
  phone_number: '+21612345678',
  password: 'password',
});

// Get advantages
const advantages = await advantageService.getAdvantages({
  latitude: 36.8065,
  longitude: 10.1815,
  radius: 5000,
});

// Add to favorites
await advantageService.addToFavorites(advantageId);
```

### Redux Store

```typescript
import {useAppDispatch, useAppSelector} from '@/store';
import {login, logout} from '@/store/slices/authSlice';

const MyComponent = () => {
  const dispatch = useAppDispatch();
  const {user, isAuthenticated} = useAppSelector(state => state.auth);

  const handleLogin = async () => {
    await dispatch(login(credentials));
  };

  return ...
};
```

## 📱 Écrans Principaux

### 1. Splash Screen
- Auto-check authentication
- Navigate to Login ou Main

### 2. Login Screen
- Phone number + Password
- OTP verification
- Link to Register

### 3. Home Screen
- User greeting + stats (Points, Level, Savings)
- AI Recommendations
- Active Challenges
- Trending advantages

### 4. Profile Screen
- User info + avatar
- Premium badge (if subscribed)
- Stats (Points, Level, Wallet)
- Menu (Wallet, Challenges, Stickers, Settings)
- Logout

### 5. Discover Screen (À implémenter)
- Liste avantages avec filtres
- Catégories
- Search bar
- Sort (Distance, Discount, Trending)

### 6. Map Screen (À implémenter)
- React Native Maps
- Markers pour merchants
- Current location
- Proximity alerts

## 🎮 Gamification UI

### Challenges
```tsx
<ChallengeCard
  challenge={challenge}
  onPress={() => navigate('ChallengeDetails')}
/>
```

### Stickers
```tsx
<StickerGrid
  stickers={stickers}
  onStickerPress={(sticker) => showStickerDetails(sticker)}
/>
```

### Leaderboard
```tsx
<LeaderboardList
  entries={leaderboard}
  currentUser={user}
/>
```

## 🔔 Push Notifications

### Setup Firebase

1. Add `google-services.json` (Android) et `GoogleService-Info.plist` (iOS)
2. Configure FCM dans `@react-native-firebase/messaging`

```typescript
import messaging from '@react-native-firebase/messaging';

// Request permission
await messaging().requestPermission();

// Get FCM token
const token = await messaging().getToken();
await authService.updateFCMToken(token);

// Listen for messages
messaging().onMessage(async remoteMessage => {
  console.log('Notification received!', remoteMessage);
});
```

## 📸 QR Code Scanner

```typescript
import {RNCamera} from 'react-native-camera';

<RNCamera
  type={RNCamera.Constants.Type.back}
  onBarCodeRead={({data}) => {
    qrCodeService.validateQR(data);
  }}
/>
```

## 🗺️ Maps & Geolocation

```typescript
import MapView, {Marker} from 'react-native-maps';
import Geolocation from 'react-native-geolocation-service';

// Get current location
Geolocation.getCurrentPosition(
  position => {
    setLocation(position.coords);
  },
  error => console.log(error),
  {enableHighAccuracy: true},
);

// Display map
<MapView
  region={{
    latitude: location.latitude,
    longitude: location.longitude,
    latitudeDelta: 0.05,
    longitudeDelta: 0.05,
  }}>
  {merchants.map(merchant => (
    <Marker
      key={merchant.id}
      coordinate={{
        latitude: merchant.latitude,
        longitude: merchant.longitude,
      }}
      title={merchant.name}
    />
  ))}
</MapView>
```

## 🧪 Testing

```bash
npm test
```

## 📦 Build Production

### iOS

```bash
cd ios
pod install
cd ..
npx react-native run-ios --configuration Release
```

### Android

```bash
cd android
./gradlew assembleRelease
# APK: android/app/build/outputs/apk/release/app-release.apk
```

## 🔧 Troubleshooting

### Metro Bundler Cache

```bash
npx react-native start --reset-cache
```

### iOS Build Errors

```bash
cd ios
pod deintegrate
pod install
cd ..
```

### Android Gradle Issues

```bash
cd android
./gradlew clean
cd ..
```

## 📝 TODO List

### Priorité Haute
- [ ] Implémenter Discover Screen avec filtres
- [ ] Ajouter Map Screen avec React Native Maps
- [ ] Implémenter QR Code Scanner
- [ ] Ajouter Push Notifications (FCM)
- [ ] Implémenter Wallet & Payment screens

### Priorité Moyenne
- [ ] Stories système (Instagram-style)
- [ ] Chat en temps réel
- [ ] Challenges UI complète
- [ ] Stickers collection UI
- [ ] Leaderboards

### Priorité Basse
- [ ] Dark mode
- [ ] Animations Lottie
- [ ] Onboarding screens
- [ ] Deep linking
- [ ] Share functionality

## 🤝 Contribution

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit (`git commit -m 'Add AmazingFeature'`)
4. Push (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📄 License

Proprietary - © 2024-2025 FreeOui

---

**Version**: 1.0.0  
**Status**: 🟡 En Développement  
**Platform**: iOS & Android  
**Framework**: React Native 0.73
