export interface Merchant {
  id: string
  name: string
  email: string
  phone_number: string
  address: string
  city: string
  latitude: number
  longitude: number
  subscription_plan: 'basic' | 'premium' | 'enterprise'
  is_active: boolean
  created_at: string
}

export interface Advantage {
  id: string
  title: string
  description: string
  type: 'percentage' | 'fixed_amount' | '2for1' | 'free_item'
  discount_percentage?: number
  discount_amount?: number
  valid_from: string
  valid_until: string
  days_available: number[]
  time_from?: string
  time_until?: string
  category: Category
  terms_conditions?: string
  usage_limit?: number
  usage_count: number
  is_active: boolean
  created_at: string
}

export interface Category {
  id: number
  name: string
  icon: string
  color: string
}

export interface QRCodeData {
  id: string
  code: string
  advantage_id: string
  advantage_title: string
  user_id: string
  user_name: string
  valid_from: string
  valid_until: string
  status: 'active' | 'used' | 'cancelled' | 'expired'
  created_at: string
}

export interface Transaction {
  id: string
  qr_code_id: string
  advantage_id: string
  advantage_title: string
  user_id: string
  user_name: string
  original_amount?: number
  discount_amount: number
  final_amount?: number
  latitude?: number
  longitude?: number
  notes?: string
  status: 'pending' | 'completed' | 'cancelled'
  created_at: string
}

export interface DashboardStats {
  total_advantages: number
  active_advantages: number
  total_scans_today: number
  total_scans_week: number
  total_scans_month: number
  total_revenue_saved: number
  average_rating: number
  total_reviews: number
}

export interface AnalyticsData {
  daily_scans: Array<{
    date: string
    count: number
  }>
  top_advantages: Array<{
    id: string
    title: string
    scans: number
    conversion_rate: number
  }>
  hourly_distribution: Array<{
    hour: number
    count: number
  }>
  category_distribution: Array<{
    category: string
    count: number
  }>
}
