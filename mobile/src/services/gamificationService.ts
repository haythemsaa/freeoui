import {apiClient} from './api';
import {Challenge, Sticker, Mayorship, LeaderboardEntry, ApiResponse} from '@/types';

export const gamificationService = {
  // Challenges
  async getChallenges(): Promise<ApiResponse<Challenge[]>> {
    return apiClient.get('/challenges');
  },

  async getChallengeById(id: number): Promise<ApiResponse<Challenge>> {
    return apiClient.get(`/challenges/${id}`);
  },

  async participateChallenge(id: number): Promise<ApiResponse<any>> {
    return apiClient.post(`/challenges/${id}/participate`);
  },

  async getChallengeProgress(id: number): Promise<ApiResponse<any>> {
    return apiClient.get(`/challenges/${id}/progress`);
  },

  async getChallengeStatistics(): Promise<ApiResponse<any>> {
    return apiClient.get('/challenges/user/statistics');
  },

  // Stickers
  async getMyStickers(): Promise<ApiResponse<Sticker[]>> {
    return apiClient.get('/stickers/collection');
  },

  async getStickerStatistics(): Promise<ApiResponse<any>> {
    return apiClient.get('/stickers/statistics');
  },

  async getStickerTiers(): Promise<ApiResponse<any>> {
    return apiClient.get('/stickers/tiers');
  },

  async showcaseStickers(stickerIds: number[]): Promise<ApiResponse<any>> {
    return apiClient.post('/stickers/showcase', {sticker_ids: stickerIds});
  },

  // Mayorships
  async checkin(merchantId: number, location?: {latitude: number; longitude: number}): Promise<ApiResponse<any>> {
    return apiClient.post('/mayorships/checkin', {
      merchant_id: merchantId,
      ...location,
    });
  },

  async getMyMayorships(): Promise<ApiResponse<Mayorship[]>> {
    return apiClient.get('/mayorships/my-mayorships');
  },

  async getMayorshipStats(): Promise<ApiResponse<any>> {
    return apiClient.get('/mayorships/stats');
  },

  async getCurrentMayor(merchantId: number): Promise<ApiResponse<any>> {
    return apiClient.get(`/mayorships/${merchantId}/mayor`);
  },

  // Leaderboards
  async getGlobalLeaderboard(period: string = 'weekly'): Promise<ApiResponse<LeaderboardEntry[]>> {
    return apiClient.get(`/leaderboards/global?period=${period}`);
  },

  async getFriendsLeaderboard(): Promise<ApiResponse<LeaderboardEntry[]>> {
    return apiClient.get('/leaderboards/friends');
  },

  async getMyRank(): Promise<ApiResponse<any>> {
    return apiClient.get('/leaderboards/rank');
  },
};
