/**
 * Application-wide constants
 */

export const ADVANTAGE_TYPES = {
  PERCENTAGE: 'percentage',
  FIXED_AMOUNT: 'fixed_amount',
  TWO_FOR_ONE: '2for1',
  FREE_ITEM: 'free_item',
} as const

export const ADVANTAGE_TYPE_LABELS = {
  [ADVANTAGE_TYPES.PERCENTAGE]: 'Pourcentage',
  [ADVANTAGE_TYPES.FIXED_AMOUNT]: 'Montant fixe',
  [ADVANTAGE_TYPES.TWO_FOR_ONE]: '2 pour 1',
  [ADVANTAGE_TYPES.FREE_ITEM]: 'Article gratuit',
} as const

export const QR_CODE_STATUS = {
  ACTIVE: 'active',
  USED: 'used',
  EXPIRED: 'expired',
  CANCELLED: 'cancelled',
} as const

export const QR_CODE_STATUS_LABELS = {
  [QR_CODE_STATUS.ACTIVE]: 'Actif',
  [QR_CODE_STATUS.USED]: 'Utilisé',
  [QR_CODE_STATUS.EXPIRED]: 'Expiré',
  [QR_CODE_STATUS.CANCELLED]: 'Annulé',
} as const

export const TRANSACTION_STATUS = {
  COMPLETED: 'completed',
  PENDING: 'pending',
  CANCELLED: 'cancelled',
} as const

export const TRANSACTION_STATUS_LABELS = {
  [TRANSACTION_STATUS.COMPLETED]: 'Complétée',
  [TRANSACTION_STATUS.PENDING]: 'En attente',
  [TRANSACTION_STATUS.CANCELLED]: 'Annulée',
} as const

export const SUBSCRIPTION_PLANS = {
  BASIC: 'basic',
  PREMIUM: 'premium',
  ENTERPRISE: 'enterprise',
} as const

export const SUBSCRIPTION_PLAN_LABELS = {
  [SUBSCRIPTION_PLANS.BASIC]: 'Basic',
  [SUBSCRIPTION_PLANS.PREMIUM]: 'Premium',
  [SUBSCRIPTION_PLANS.ENTERPRISE]: 'Enterprise',
} as const

export const DAYS_OF_WEEK = [
  { value: 0, label: 'Dimanche', short: 'Dim' },
  { value: 1, label: 'Lundi', short: 'Lun' },
  { value: 2, label: 'Mardi', short: 'Mar' },
  { value: 3, label: 'Mercredi', short: 'Mer' },
  { value: 4, label: 'Jeudi', short: 'Jeu' },
  { value: 5, label: 'Vendredi', short: 'Ven' },
  { value: 6, label: 'Samedi', short: 'Sam' },
] as const

export const PAGINATION = {
  DEFAULT_PAGE: 1,
  DEFAULT_PER_PAGE: 15,
  PER_PAGE_OPTIONS: [10, 15, 25, 50, 100],
} as const

export const PROXIMITY = {
  DEFAULT_RADIUS_METERS: 1000,
  MIN_RADIUS_METERS: 500,
  MAX_RADIUS_METERS: 5000,
  MAX_ALERTS_PER_DAY: 5,
  MIN_INTERVAL_MINUTES: 30,
} as const

export const QR_CODE = {
  VALIDITY_HOURS: 2,
  MAX_PER_USER_PER_DAY: 10,
} as const

export const DATE_FORMATS = {
  DISPLAY: 'DD/MM/YYYY',
  DISPLAY_TIME: 'DD/MM/YYYY HH:mm',
  API: 'YYYY-MM-DD',
  API_TIME: 'YYYY-MM-DD HH:mm:ss',
} as const

export const CURRENCIES = {
  TND: 'TND',
} as const

export const LANGUAGES = {
  FR: 'fr',
  AR: 'ar',
} as const

export const USER_LEVELS = {
  BRONZE: 'bronze',
  SILVER: 'silver',
  GOLD: 'gold',
  PLATINUM: 'platinum',
} as const

export const USER_LEVEL_LABELS = {
  [USER_LEVELS.BRONZE]: 'Bronze',
  [USER_LEVELS.SILVER]: 'Silver',
  [USER_LEVELS.GOLD]: 'Gold',
  [USER_LEVELS.PLATINUM]: 'Platinum',
} as const

export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/merchants/auth/login',
    LOGOUT: '/merchants/auth/logout',
    PROFILE: '/merchants/auth/profile',
    UPDATE_PROFILE: '/merchants/auth/profile',
  },
  ADVANTAGES: {
    LIST: '/merchants/advantages',
    CREATE: '/merchants/advantages',
    UPDATE: (id: string) => `/merchants/advantages/${id}`,
    DELETE: (id: string) => `/merchants/advantages/${id}`,
    STATS: '/merchants/advantages/stats',
  },
  QR_CODES: {
    VALIDATE: '/merchants/qr-codes/validate',
    HISTORY: '/merchants/qr-codes/history',
  },
  TRANSACTIONS: {
    LIST: '/merchants/transactions',
    EXPORT: '/merchants/transactions/export',
  },
  ANALYTICS: {
    DASHBOARD: '/merchants/analytics/dashboard',
    SCANS: '/merchants/analytics/scans',
  },
} as const

export const CHART_COLORS = {
  PRIMARY: '#FF6F00',
  SECONDARY: '#4CAF50',
  TERTIARY: '#2196F3',
  QUATERNARY: '#9C27B0',
  DANGER: '#F44336',
  WARNING: '#FFC107',
  SUCCESS: '#4CAF50',
  INFO: '#2196F3',
} as const
