import { useEffect, useState } from 'react'
import { X, CheckCircle, XCircle, AlertCircle, Info } from 'lucide-react'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: string
  type: ToastType
  message: string
  title?: string
  duration?: number
}

interface ToastItemProps {
  toast: Toast
  onClose: (id: string) => void
}

const ToastItem = ({ toast, onClose }: ToastItemProps) => {
  const [isExiting, setIsExiting] = useState(false)

  useEffect(() => {
    if (toast.duration) {
      const timer = setTimeout(() => {
        handleClose()
      }, toast.duration)

      return () => clearTimeout(timer)
    }
  }, [toast.duration])

  const handleClose = () => {
    setIsExiting(true)
    setTimeout(() => {
      onClose(toast.id)
    }, 300)
  }

  const getIcon = () => {
    switch (toast.type) {
      case 'success':
        return <CheckCircle className="w-5 h-5 text-green-500" />
      case 'error':
        return <XCircle className="w-5 h-5 text-red-500" />
      case 'warning':
        return <AlertCircle className="w-5 h-5 text-yellow-500" />
      case 'info':
        return <Info className="w-5 h-5 text-blue-500" />
    }
  }

  const getStyles = () => {
    switch (toast.type) {
      case 'success':
        return 'border-green-500 bg-green-50'
      case 'error':
        return 'border-red-500 bg-red-50'
      case 'warning':
        return 'border-yellow-500 bg-yellow-50'
      case 'info':
        return 'border-blue-500 bg-blue-50'
    }
  }

  return (
    <div
      className={`
        flex items-start gap-3 p-4 rounded-lg border-l-4 shadow-lg
        transition-all duration-300 min-w-[320px] max-w-md
        ${getStyles()}
        ${isExiting ? 'opacity-0 translate-x-full' : 'opacity-100 translate-x-0'}
      `}
    >
      <div className="flex-shrink-0 mt-0.5">{getIcon()}</div>

      <div className="flex-1 min-w-0">
        {toast.title && (
          <p className="font-semibold text-gray-900 mb-1">{toast.title}</p>
        )}
        <p className="text-sm text-gray-700">{toast.message}</p>
      </div>

      <button
        onClick={handleClose}
        className="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
      >
        <X className="w-5 h-5" />
      </button>
    </div>
  )
}

interface ToastContainerProps {
  toasts: Toast[]
  onClose: (id: string) => void
}

export const ToastContainer = ({ toasts, onClose }: ToastContainerProps) => {
  return (
    <div className="fixed top-4 right-4 z-50 flex flex-col gap-2">
      {toasts.map((toast) => (
        <ToastItem key={toast.id} toast={toast} onClose={onClose} />
      ))}
    </div>
  )
}
