import {apiClient} from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {User, LoginCredentials, RegisterData, ApiResponse} from '@/types';

export const authService = {
  async register(data: RegisterData): Promise<ApiResponse<{user: User; token: string}>> {
    const response = await apiClient.post('/auth/register', data);
    if (response.success && response.data) {
      await AsyncStorage.setItem('auth_token', response.data.token);
      await AsyncStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response;
  },

  async login(credentials: LoginCredentials): Promise<ApiResponse<{user: User; token: string}>> {
    const response = await apiClient.post('/auth/login', credentials);
    if (response.success && response.data) {
      await AsyncStorage.setItem('auth_token', response.data.token);
      await AsyncStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response;
  },

  async verifyOTP(phone: string, otp: string): Promise<ApiResponse<{user: User; token: string}>> {
    const response = await apiClient.post('/auth/verify-otp', {phone_number: phone, otp});
    if (response.success && response.data) {
      await AsyncStorage.setItem('auth_token', response.data.token);
      await AsyncStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response;
  },

  async requestOTP(phone: string): Promise<ApiResponse<any>> {
    return apiClient.post('/auth/request-otp', {phone_number: phone});
  },

  async logout(): Promise<void> {
    await apiClient.post('/auth/logout');
    await AsyncStorage.removeItem('auth_token');
    await AsyncStorage.removeItem('user');
  },

  async getProfile(): Promise<ApiResponse<User>> {
    return apiClient.get('/users/profile');
  },

  async updateProfile(data: Partial<User>): Promise<ApiResponse<User>> {
    return apiClient.put('/users/profile', data);
  },

  async updateFCMToken(token: string): Promise<ApiResponse<any>> {
    return apiClient.post('/users/fcm-token', {fcm_token: token});
  },
};
