import messaging from '@react-native-firebase/messaging';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {authService} from './authService';

class NotificationService {
  async requestPermission(): Promise<boolean> {
    try {
      const authStatus = await messaging().requestPermission();
      const enabled =
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

      if (enabled) {
        console.log('Notification permission granted');
        await this.getFCMToken();
      }

      return enabled;
    } catch (error) {
      console.error('Permission request error:', error);
      return false;
    }
  }

  async getFCMToken(): Promise<string | null> {
    try {
      const fcmToken = await messaging().getToken();
      if (fcmToken) {
        console.log('FCM Token:', fcmToken);
        await AsyncStorage.setItem('fcm_token', fcmToken);
        // Send token to backend
        await authService.updateFCMToken(fcmToken);
        return fcmToken;
      }
      return null;
    } catch (error) {
      console.error('Get FCM Token error:', error);
      return null;
    }
  }

  async refreshToken(): Promise<void> {
    messaging().onTokenRefresh(async (token) => {
      console.log('FCM Token refreshed:', token);
      await AsyncStorage.setItem('fcm_token', token);
      await authService.updateFCMToken(token);
    });
  }

  setupForegroundHandler(): void {
    messaging().onMessage(async (remoteMessage) => {
      console.log('Foreground notification:', remoteMessage);
      // Handle notification in app
      if (remoteMessage.notification) {
        // You can show a custom in-app notification here
        console.log('Notification:', remoteMessage.notification);
      }
    });
  }

  setupBackgroundHandler(): void {
    messaging().setBackgroundMessageHandler(async (remoteMessage) => {
      console.log('Background notification:', remoteMessage);
      // Handle background notification
    });
  }

  async getInitialNotification(): Promise<any> {
    const notification = await messaging().getInitialNotification();
    if (notification) {
      console.log('Initial notification:', notification);
      return notification;
    }
    return null;
  }

  onNotificationOpenedApp(callback: (remoteMessage: any) => void): void {
    messaging().onNotificationOpenedApp((remoteMessage) => {
      console.log('Notification opened app:', remoteMessage);
      callback(remoteMessage);
    });
  }

  async subscribeTopic(topic: string): Promise<void> {
    try {
      await messaging().subscribeToTopic(topic);
      console.log(`Subscribed to topic: ${topic}`);
    } catch (error) {
      console.error('Subscribe to topic error:', error);
    }
  }

  async unsubscribeFromTopic(topic: string): Promise<void> {
    try {
      await messaging().unsubscribeFromTopic(topic);
      console.log(`Unsubscribed from topic: ${topic}`);
    } catch (error) {
      console.error('Unsubscribe from topic error:', error);
    }
  }

  async initialize(): Promise<void> {
    // Request permission
    await this.requestPermission();

    // Setup handlers
    this.setupForegroundHandler();
    this.setupBackgroundHandler();
    this.refreshToken();

    // Check initial notification
    await this.getInitialNotification();

    // Handle notification opened app
    this.onNotificationOpenedApp((remoteMessage) => {
      // Handle navigation based on notification data
      console.log('App opened from notification:', remoteMessage);
    });
  }
}

export default new NotificationService();
