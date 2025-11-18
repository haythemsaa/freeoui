import {apiClient} from './api';
import {Advantage, ApiResponse, PaginatedResponse} from '@/types';

export const advantageService = {
  async getAdvantages(params?: {
    category_id?: number;
    merchant_id?: number;
    latitude?: number;
    longitude?: number;
    radius?: number;
    page?: number;
  }): Promise<ApiResponse<PaginatedResponse<Advantage>>> {
    return apiClient.get('/advantages', {params});
  },

  async getAdvantageById(id: number): Promise<ApiResponse<Advantage>> {
    return apiClient.get(`/advantages/${id}`);
  },

  async addToFavorites(id: number): Promise<ApiResponse<any>> {
    return apiClient.post(`/advantages/${id}/favorite`);
  },

  async removeFromFavorites(id: number): Promise<ApiResponse<any>> {
    return apiClient.delete(`/advantages/${id}/favorite`);
  },

  async getFavorites(): Promise<ApiResponse<Advantage[]>> {
    return apiClient.get('/favorites');
  },

  async getRecommendations(): Promise<ApiResponse<Advantage[]>> {
    return apiClient.get('/recommendations');
  },

  async getTrending(): Promise<ApiResponse<Advantage[]>> {
    return apiClient.get('/analytics/trending');
  },
};
