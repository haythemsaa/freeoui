export interface User {
  id: string;
  phone_number: string;
  email?: string;
  first_name: string;
  last_name: string;
  avatar_url?: string;
  points_balance: number;
  level: number;
  coins_balance: number;
  wallet_balance: number;
  is_premium: boolean;
  checkin_streak: number;
  total_savings_tnd: number;
  referral_code: string;
}

export interface Merchant {
  id: number;
  name: string;
  description?: string;
  logo_url?: string;
  cover_url?: string;
  latitude: number;
  longitude: number;
  address: string;
  phone: string;
  category: Category;
  rating_average: number;
  rating_count: number;
  distance?: number;
  is_open: boolean;
}

export interface Advantage {
  id: number;
  title: string;
  description: string;
  discount_percentage: number;
  original_price?: number;
  discounted_price?: number;
  image_url?: string;
  merchant: Merchant;
  category: Category;
  starts_at: string;
  ends_at: string;
  is_active: boolean;
  redemption_count: number;
  terms_conditions?: string;
}

export interface Category {
  id: number;
  name: string;
  icon: string;
  color: string;
  parent_id?: number;
  children?: Category[];
}

export interface QRCode {
  id: string;
  code: string;
  advantage_id: number;
  user_id: string;
  status: 'active' | 'used' | 'expired';
  expires_at: string;
  created_at: string;
}

export interface Transaction {
  id: number;
  user_id: string;
  merchant_id: number;
  advantage_id?: number;
  amount_paid: number;
  discount_amount: number;
  points_earned: number;
  status: string;
  created_at: string;
  merchant: Merchant;
  advantage?: Advantage;
}

export interface Challenge {
  id: number;
  name: string;
  description: string;
  challenge_type: string;
  criteria: any;
  reward_points: number;
  badge_icon: string;
  starts_at: string;
  ends_at: string;
  is_active: boolean;
  is_recurring: boolean;
  participation?: ChallengeParticipation;
}

export interface ChallengeParticipation {
  id: number;
  challenge_id: number;
  user_id: string;
  progress: any;
  progress_percentage: number;
  status: 'active' | 'completed' | 'expired';
  completed_at?: string;
}

export interface Sticker {
  id: number;
  category_id: number;
  category: Category;
  sticker_tier: 'bronze' | 'silver' | 'gold' | 'diamond';
  visit_count: number;
  coin_multiplier: number;
  unlocked_at: string;
}

export interface Mayorship {
  id: number;
  merchant_id: number;
  merchant: Merchant;
  checkin_count: number;
  is_active: boolean;
  claimed_at: string;
  last_checkin_at: string;
}

export interface Story {
  id: number;
  user?: User;
  merchant?: Merchant;
  type: 'image' | 'video';
  media_url: string;
  thumbnail_url?: string;
  caption?: string;
  duration: number;
  views_count: number;
  expires_at: string;
  created_at: string;
  has_viewed: boolean;
}

export interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  data?: any;
  read_at?: string;
  created_at: string;
}

export interface LeaderboardEntry {
  rank: number;
  user: User;
  points: number;
  level: number;
}

export interface Subscription {
  id: number;
  plan_type: 'monthly' | 'quarterly' | 'yearly';
  status: 'active' | 'cancelled' | 'expired';
  amount: number;
  discount_percentage: number;
  starts_at: string;
  ends_at: string;
  next_billing_date: string;
}

export interface GiftCard {
  id: number;
  code: string;
  amount: number;
  balance: number;
  status: 'active' | 'redeemed' | 'expired';
  expires_at: string;
  message?: string;
}

export interface Cashback {
  id: number;
  amount: number;
  rate: number;
  status: 'pending' | 'processed' | 'cancelled';
  merchant: Merchant;
  transaction_id: number;
  eligible_at: string;
  processed_at?: string;
}

// Navigation Types
export type RootStackParamList = {
  Splash: undefined;
  Onboarding: undefined;
  Auth: undefined;
  Login: undefined;
  Register: undefined;
  VerifyOTP: {phone: string};
  Main: undefined;
  AdvantageDetails: {advantageId: number};
  MerchantDetails: {merchantId: number};
  QRScanner: undefined;
  QRCode: {qrCodeId: string};
  StoryViewer: {stories: Story[]};
  Map: undefined;
  Chat: {conversationId?: number; merchantId?: number};
  Profile: undefined;
  Settings: undefined;
  Wallet: undefined;
  Subscription: undefined;
  Challenges: undefined;
  Leaderboard: undefined;
  Stickers: undefined;
  Referral: undefined;
};

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: any;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  total_pages: number;
  total_items: number;
}

// Auth Types
export interface AuthState {
  isAuthenticated: boolean;
  user: User | null;
  token: string | null;
  loading: boolean;
  error: string | null;
}

export interface LoginCredentials {
  phone_number: string;
  password?: string;
}

export interface RegisterData {
  phone_number: string;
  first_name: string;
  last_name: string;
  email?: string;
  referral_code?: string;
}
