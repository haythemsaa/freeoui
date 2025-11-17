import apiClient from '../lib/axios'
import type { DashboardStats, AnalyticsData } from '../types'

export const analyticsApi = {
  getDashboardStats: async (): Promise<DashboardStats> => {
    const response = await apiClient.get('/merchants/analytics/dashboard')
    return response.data.data.stats
  },

  getAnalytics: async (params: {
    from_date: string
    to_date: string
  }): Promise<AnalyticsData> => {
    const response = await apiClient.get('/merchants/analytics', { params })
    return response.data.data
  },

  getTopAdvantages: async (limit: number = 10) => {
    const response = await apiClient.get('/merchants/analytics/top-advantages', {
      params: { limit },
    })
    return response.data.data.advantages
  },

  getHeatmapData: async () => {
    const response = await apiClient.get('/merchants/analytics/heatmap')
    return response.data.data
  },
}
