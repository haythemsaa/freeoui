import {apiClient} from './api';
import {Transaction, Cashback, GiftCard, Subscription, ApiResponse} from '@/types';

export const walletService = {
  // Wallet
  async getBalance(): Promise<ApiResponse<{balance: number; transactions: Transaction[]}>> {
    return apiClient.get('/wallet');
  },

  async topUp(amount: number, paymentMethod: string): Promise<ApiResponse<any>> {
    return apiClient.post('/wallet/top-up', {amount, payment_method: paymentMethod});
  },

  async withdraw(amount: number): Promise<ApiResponse<any>> {
    return apiClient.post('/wallet/withdraw', {amount});
  },

  async getTransactions(): Promise<ApiResponse<Transaction[]>> {
    return apiClient.get('/transactions');
  },

  // Cashback
  async getMyCashbacks(): Promise<ApiResponse<Cashback[]>> {
    return apiClient.get('/cashbacks');
  },

  async getCashbackStats(): Promise<ApiResponse<any>> {
    return apiClient.get('/cashbacks/stats');
  },

  // Gift Cards
  async purchaseGiftCard(data: {amount: number; recipient_email?: string; message?: string}): Promise<ApiResponse<GiftCard>> {
    return apiClient.post('/gift-cards/purchase', data);
  },

  async redeemGiftCard(code: string): Promise<ApiResponse<any>> {
    return apiClient.post('/gift-cards/redeem', {code});
  },

  async checkGiftCardBalance(code: string): Promise<ApiResponse<any>> {
    return apiClient.get(`/gift-cards/${code}/balance`);
  },

  // Subscription
  async getSubscriptionPlans(): Promise<ApiResponse<any>> {
    return apiClient.get('/subscriptions/plans');
  },

  async getCurrentSubscription(): Promise<ApiResponse<Subscription>> {
    return apiClient.get('/subscriptions/current');
  },

  async subscribe(planType: string, paymentMethod: string): Promise<ApiResponse<any>> {
    return apiClient.post('/subscriptions/subscribe', {plan_type: planType, payment_method: paymentMethod});
  },

  async cancelSubscription(reason?: string): Promise<ApiResponse<any>> {
    return apiClient.post('/subscriptions/cancel', {reason});
  },
};
