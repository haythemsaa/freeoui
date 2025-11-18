import {apiClient} from './api';
import {QRCode, ApiResponse} from '@/types';

export const qrCodeService = {
  async generateQR(advantageId: number): Promise<ApiResponse<QRCode>> {
    return apiClient.post('/qr-codes/generate', {advantage_id: advantageId});
  },

  async validateQR(code: string): Promise<ApiResponse<any>> {
    return apiClient.post('/qr-codes/validate', {code});
  },

  async getMyQRCodes(): Promise<ApiResponse<QRCode[]>> {
    return apiClient.get('/qr-codes');
  },

  async cancelQR(id: string): Promise<ApiResponse<any>> {
    return apiClient.delete(`/qr-codes/${id}`);
  },
};
