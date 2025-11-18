import React from 'react';
import {NavigationContainer} from '@react-navigation/native';
import {createStackNavigator} from '@react-navigation/stack';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/Ionicons';
import {Colors} from '@/constants/colors';
import {RootStackParamList} from '@/types';

// Auth Screens
import SplashScreen from '@/screens/SplashScreen';
import LoginScreen from '@/screens/Auth/LoginScreen';
import RegisterScreen from '@/screens/Auth/RegisterScreen';
import OTPVerificationScreen from '@/screens/Auth/OTPVerificationScreen';

// Main Tab Screens
import HomeScreen from '@/screens/Home/HomeScreen';
import DiscoverScreen from '@/screens/Discover/DiscoverScreen';
import MapScreen from '@/screens/Map/MapScreen';
import ProfileScreen from '@/screens/Profile/ProfileScreen';

// Stack Screens
import AdvantageDetailScreen from '@/screens/Advantage/AdvantageDetailScreen';
import QRScannerScreen from '@/screens/QR/QRScannerScreen';
import WalletScreen from '@/screens/Wallet/WalletScreen';
import ChallengesScreen from '@/screens/Gamification/ChallengesScreen';
import StickersScreen from '@/screens/Gamification/StickersScreen';
import LeaderboardScreen from '@/screens/Gamification/LeaderboardScreen';
import ChatbotScreen from '@/screens/Chat/ChatbotScreen';
import SettingsScreen from '@/screens/Settings/SettingsScreen';

const Stack = createStackNavigator<RootStackParamList>();
const Tab = createBottomTabNavigator();

const TabNavigator = () => {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: Colors.primary,
        tabBarInactiveTintColor: Colors.textLight,
        tabBarStyle: {
          backgroundColor: '#FFF',
          borderTopWidth: 1,
          borderTopColor: Colors.border,
          height: 60,
          paddingBottom: 8,
        },
      }}>
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarLabel: 'Accueil',
          tabBarIcon: ({color, size}) => (
            <Icon name="home" color={color} size={size} />
          ),
        }}
      />
      <Tab.Screen
        name="Discover"
        component={DiscoverScreen}
        options={{
          tabBarLabel: 'Découvrir',
          tabBarIcon: ({color, size}) => (
            <Icon name="compass" color={color} size={size} />
          ),
        }}
      />
      <Tab.Screen
        name="Map"
        component={MapScreen}
        options={{
          tabBarLabel: 'Carte',
          tabBarIcon: ({color, size}) => (
            <Icon name="location" color={color} size={size} />
          ),
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          tabBarLabel: 'Profil',
          tabBarIcon: ({color, size}) => (
            <Icon name="person" color={color} size={size} />
          ),
        }}
      />
    </Tab.Navigator>
  );
};

export const AppNavigator = () => {
  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{headerShown: false}}>
        {/* Auth Stack */}
        <Stack.Screen name="Splash" component={SplashScreen} />
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="Register" component={RegisterScreen} />
        <Stack.Screen name="OTPVerification" component={OTPVerificationScreen} />

        {/* Main Tab Navigator */}
        <Stack.Screen name="Main" component={TabNavigator} />

        {/* Modal Screens */}
        <Stack.Screen
          name="AdvantageDetail"
          component={AdvantageDetailScreen}
          options={{
            headerShown: false,
            presentation: 'card',
          }}
        />
        <Stack.Screen
          name="QRScanner"
          component={QRScannerScreen}
          options={{
            headerShown: false,
            presentation: 'fullScreenModal',
          }}
        />
        <Stack.Screen
          name="Wallet"
          component={WalletScreen}
          options={{
            headerShown: true,
            headerTitle: 'Portefeuille',
            headerStyle: {backgroundColor: Colors.primary},
            headerTintColor: '#FFF',
          }}
        />
        <Stack.Screen
          name="Challenges"
          component={ChallengesScreen}
          options={{
            headerShown: true,
            headerTitle: 'Challenges',
            headerStyle: {backgroundColor: Colors.primary},
            headerTintColor: '#FFF',
          }}
        />
        <Stack.Screen
          name="Stickers"
          component={StickersScreen}
          options={{
            headerShown: true,
            headerTitle: 'Ma Collection',
            headerStyle: {backgroundColor: Colors.primary},
            headerTintColor: '#FFF',
          }}
        />
        <Stack.Screen
          name="Leaderboard"
          component={LeaderboardScreen}
          options={{
            headerShown: false,
          }}
        />
        <Stack.Screen
          name="Chatbot"
          component={ChatbotScreen}
          options={{
            headerShown: true,
            headerTitle: 'Assistant FreeOui',
            headerStyle: {backgroundColor: Colors.primary},
            headerTintColor: '#FFF',
          }}
        />
        <Stack.Screen
          name="Settings"
          component={SettingsScreen}
          options={{
            headerShown: true,
            headerTitle: 'Paramètres',
            headerStyle: {backgroundColor: Colors.primary},
            headerTintColor: '#FFF',
          }}
        />
      </Stack.Navigator>
    </NavigationContainer>
  );
};
