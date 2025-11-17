import apiClient from '../lib/axios'
import type { QRCodeData, Transaction } from '../types'

export interface ValidateQRRequest {
  qr_code: string
  original_amount?: number
  latitude?: number
  longitude?: number
  notes?: string
}

export interface ValidateQRResponse {
  transaction: Transaction
  advantage: {
    id: string
    title: string
    discount_type: string
    discount_value: number
  }
  user: {
    id: string
    name: string
    phone: string
  }
}

export const qrApi = {
  validate: async (data: ValidateQRRequest): Promise<ValidateQRResponse> => {
    const response = await apiClient.post('/merchants/qr-codes/validate', data)
    return response.data.data
  },

  getScans: async (params?: {
    from_date?: string
    to_date?: string
    page?: number
  }): Promise<{ scans: QRCodeData[]; total: number; per_page: number }> => {
    const response = await apiClient.get('/merchants/qr-codes/scans', { params })
    return response.data.data
  },
}
