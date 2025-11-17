import { create } from 'zustand'
import type { Toast, ToastType } from '../components/ui/Toast'

interface ToastStore {
  toasts: Toast[]
  addToast: (toast: Omit<Toast, 'id'>) => void
  removeToast: (id: string) => void
  success: (message: string, title?: string) => void
  error: (message: string, title?: string) => void
  warning: (message: string, title?: string) => void
  info: (message: string, title?: string) => void
}

const useToastStore = create<ToastStore>((set) => ({
  toasts: [],

  addToast: (toast) => {
    const id = Math.random().toString(36).substring(7)
    const newToast: Toast = {
      id,
      duration: 5000,
      ...toast,
    }

    set((state) => ({
      toasts: [...state.toasts, newToast],
    }))
  },

  removeToast: (id) => {
    set((state) => ({
      toasts: state.toasts.filter((toast) => toast.id !== id),
    }))
  },

  success: (message, title) => {
    useToastStore.getState().addToast({
      type: 'success',
      message,
      title: title || 'Succès',
    })
  },

  error: (message, title) => {
    useToastStore.getState().addToast({
      type: 'error',
      message,
      title: title || 'Erreur',
      duration: 7000,
    })
  },

  warning: (message, title) => {
    useToastStore.getState().addToast({
      type: 'warning',
      message,
      title: title || 'Attention',
    })
  },

  info: (message, title) => {
    useToastStore.getState().addToast({
      type: 'info',
      message,
      title: title || 'Information',
    })
  },
}))

export const useToast = () => {
  const { success, error, warning, info } = useToastStore()

  return {
    success,
    error,
    warning,
    info,
  }
}

export const useToasts = () => {
  const { toasts, removeToast } = useToastStore()

  return {
    toasts,
    removeToast,
  }
}
