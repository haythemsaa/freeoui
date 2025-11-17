import apiClient from '../lib/axios'
import type { Advantage } from '../types'

export interface AdvantageFormData {
  title: string
  description: string
  type: 'percentage' | 'fixed_amount' | '2for1' | 'free_item'
  discount_percentage?: number
  discount_amount?: number
  valid_from: string
  valid_until: string
  days_available: number[]
  time_from?: string
  time_until?: string
  category_id: number
  terms_conditions?: string
  usage_limit?: number
}

export const advantagesApi = {
  getAll: async (): Promise<Advantage[]> => {
    const response = await apiClient.get('/merchants/advantages')
    return response.data.data.advantages
  },

  getById: async (id: string): Promise<Advantage> => {
    const response = await apiClient.get(`/merchants/advantages/${id}`)
    return response.data.data.advantage
  },

  create: async (data: AdvantageFormData): Promise<Advantage> => {
    const response = await apiClient.post('/merchants/advantages', data)
    return response.data.data.advantage
  },

  update: async (id: string, data: Partial<AdvantageFormData>): Promise<Advantage> => {
    const response = await apiClient.put(`/merchants/advantages/${id}`, data)
    return response.data.data.advantage
  },

  delete: async (id: string): Promise<void> => {
    await apiClient.delete(`/merchants/advantages/${id}`)
  },

  toggleActive: async (id: string): Promise<Advantage> => {
    const response = await apiClient.post(`/merchants/advantages/${id}/toggle`)
    return response.data.data.advantage
  },
}
