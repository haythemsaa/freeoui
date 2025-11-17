import apiClient from '../lib/axios'
import type { Merchant } from '../types'

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  merchant: Merchant
  access_token: string
  refresh_token: string
}

export const authApi = {
  login: async (credentials: LoginCredentials): Promise<LoginResponse> => {
    const response = await apiClient.post('/merchants/auth/login', credentials)
    return response.data.data
  },

  logout: async (): Promise<void> => {
    await apiClient.post('/merchants/auth/logout')
  },

  getProfile: async (): Promise<Merchant> => {
    const response = await apiClient.get('/merchants/profile')
    return response.data.data.merchant
  },

  updateProfile: async (data: Partial<Merchant>): Promise<Merchant> => {
    const response = await apiClient.put('/merchants/profile', data)
    return response.data.data.merchant
  },
}
