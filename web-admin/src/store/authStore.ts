import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { Merchant } from '../types'

interface AuthState {
  merchant: Merchant | null
  isAuthenticated: boolean
  accessToken: string | null
  refreshToken: string | null
  setAuth: (merchant: Merchant, accessToken: string, refreshToken: string) => void
  clearAuth: () => void
  updateMerchant: (merchant: Merchant) => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      merchant: null,
      isAuthenticated: false,
      accessToken: null,
      refreshToken: null,

      setAuth: (merchant, accessToken, refreshToken) => {
        localStorage.setItem('access_token', accessToken)
        localStorage.setItem('refresh_token', refreshToken)
        set({
          merchant,
          isAuthenticated: true,
          accessToken,
          refreshToken,
        })
      },

      clearAuth: () => {
        localStorage.removeItem('access_token')
        localStorage.removeItem('refresh_token')
        set({
          merchant: null,
          isAuthenticated: false,
          accessToken: null,
          refreshToken: null,
        })
      },

      updateMerchant: (merchant) => {
        set({ merchant })
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        merchant: state.merchant,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)
