-- ============================================
-- FREEOUI - INITIAL DATABASE SCHEMA
-- PostgreSQL 16 + PostGIS Extension
-- ============================================

-- Enable PostGIS extension
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- TABLE : users
-- ============================================

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Authentication
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    country_code VARCHAR(5) DEFAULT '+216',
    email VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP,
    password VARCHAR(255),

    -- OAuth
    google_id VARCHAR(255),
    facebook_id VARCHAR(255),

    -- Profile
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(500),
    date_of_birth DATE,
    gender VARCHAR(50),

    -- Location
    governorate_id INTEGER,
    city_id INTEGER,
    district VARCHAR(100),
    postal_code VARCHAR(10),
    address_details TEXT,

    -- Preferences
    preferred_language VARCHAR(5) DEFAULT 'fr',

    -- Notifications
    fcm_token VARCHAR(500),
    push_notifications BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,

    -- Gamification
    points_balance INTEGER DEFAULT 0,
    level INTEGER DEFAULT 1,
    total_savings_tnd DECIMAL(10, 3) DEFAULT 0,

    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    last_login_at TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_users_phone ON users(phone_number);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at DESC);

-- ============================================
-- TABLE : user_proximity_preferences
-- ============================================

CREATE TABLE user_proximity_preferences (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- Proximity alerts
    proximity_alerts_enabled BOOLEAN DEFAULT TRUE,
    proximity_radius_meters INTEGER DEFAULT 1000,

    -- Interests
    interested_category_ids INTEGER[] DEFAULT '{}',

    -- Time preferences
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',

    -- Limits
    max_daily_notifications INTEGER DEFAULT 5,
    min_notification_interval_minutes INTEGER DEFAULT 30,

    -- Advanced
    notify_only_when_moving BOOLEAN DEFAULT FALSE,
    notify_for_featured_only BOOLEAN DEFAULT FALSE,
    minimum_discount_percentage INTEGER DEFAULT 0,

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(user_id)
);

CREATE INDEX idx_proximity_prefs_user ON user_proximity_preferences(user_id);

-- ============================================
-- TABLE : user_locations (GPS Tracking)
-- ============================================

CREATE TABLE user_locations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- GPS Coordinates
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    geom GEOGRAPHY(POINT, 4326),

    -- Accuracy
    accuracy_meters DECIMAL(10, 2),
    altitude DECIMAL(10, 2),
    speed_mps DECIMAL(10, 2),
    heading_degrees DECIMAL(5, 2),

    -- Source
    source VARCHAR(50) DEFAULT 'app',

    -- Meta
    recorded_at TIMESTAMP DEFAULT NOW(),

    CONSTRAINT valid_latitude CHECK (latitude >= -90 AND latitude <= 90),
    CONSTRAINT valid_longitude CHECK (longitude >= -180 AND longitude <= 180)
);

-- Spatial index for ultra-fast proximity queries
CREATE INDEX idx_user_locations_geom ON user_locations USING GIST(geom);
CREATE INDEX idx_user_locations_user_time ON user_locations(user_id, recorded_at DESC);

-- Trigger to auto-generate PostGIS geometry
CREATE OR REPLACE FUNCTION update_user_location_geom()
RETURNS TRIGGER AS $$
BEGIN
    NEW.geom := ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326)::geography;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_user_location_geom
BEFORE INSERT OR UPDATE ON user_locations
FOR EACH ROW
EXECUTE FUNCTION update_user_location_geom();

-- ============================================
-- TABLE : governorates
-- ============================================

CREATE TABLE governorates (
    id SERIAL PRIMARY KEY,
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    code VARCHAR(10) UNIQUE NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    population INTEGER,
    area_km2 DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Insert Tunisian governorates
INSERT INTO governorates (name_fr, name_ar, code, latitude, longitude) VALUES
('Tunis', 'تونس', 'TU', 36.8065, 10.1815),
('Ariana', 'أريانة', 'AR', 36.8625, 10.1956),
('Ben Arous', 'بن عروس', 'BA', 36.7500, 10.2167),
('Manouba', 'منوبة', 'MA', 36.8103, 9.8622),
('Nabeul', 'نابل', 'NA', 36.4511, 10.7353),
('Zaghouan', 'زغوان', 'ZA', 36.4028, 10.1425),
('Bizerte', 'بنزرت', 'BI', 37.2744, 9.8739),
('Béja', 'باجة', 'BE', 36.7256, 9.1817),
('Jendouba', 'جندوبة', 'JE', 36.5011, 8.7806),
('Kef', 'الكاف', 'KE', 36.1742, 8.7150),
('Siliana', 'سليانة', 'SI', 36.0850, 9.3700),
('Kairouan', 'القيروان', 'KA', 35.6781, 10.0963),
('Kasserine', 'القصرين', 'KS', 35.1672, 8.8305),
('Sidi Bouzid', 'سيدي بوزيد', 'SB', 35.0381, 9.4839),
('Sousse', 'سوسة', 'SO', 35.8256, 10.6369),
('Monastir', 'المنستير', 'MO', 35.7772, 10.8264),
('Mahdia', 'المهدية', 'MH', 35.5047, 11.0622),
('Sfax', 'صفاقس', 'SF', 34.7406, 10.7603),
('Gafsa', 'قفصة', 'GA', 34.4250, 8.7842),
('Tozeur', 'توزر', 'TO', 33.9197, 8.1333),
('Kebili', 'قبلي', 'KB', 33.7047, 8.9694),
('Gabès', 'قابس', 'GB', 33.8815, 10.0982),
('Medenine', 'مدنين', 'ME', 33.3545, 10.5055),
('Tataouine', 'تطاوين', 'TA', 32.9294, 10.4517);

-- ============================================
-- TABLE : cities
-- ============================================

CREATE TABLE cities (
    id SERIAL PRIMARY KEY,
    governorate_id INTEGER NOT NULL REFERENCES governorates(id),
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    postal_code VARCHAR(10),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    population INTEGER,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_cities_governorate ON cities(governorate_id);

-- ============================================
-- TABLE : categories
-- ============================================

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    slug VARCHAR(100) UNIQUE NOT NULL,
    icon VARCHAR(100),
    color VARCHAR(20),
    parent_id INTEGER REFERENCES categories(id),
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Insert main categories
INSERT INTO categories (name_fr, name_ar, slug, icon, color) VALUES
('Restaurants & Cafés', 'مطاعم ومقاهي', 'restaurants-cafes', '🍽️', '#FF6F00'),
('Shopping & Mode', 'تسوق وأزياء', 'shopping-mode', '🛍️', '#E91E63'),
('Beauté & Bien-être', 'جمال ورفاهية', 'beaute-bien-etre', '💇', '#9C27B0'),
('Sport & Fitness', 'رياضة ولياقة', 'sport-fitness', '🏋️', '#4CAF50'),
('Loisirs & Culture', 'ترفيه وثقافة', 'loisirs-culture', '🎬', '#2196F3'),
('Services & Réparations', 'خدمات وإصلاحات', 'services-reparations', '🔧', '#FF9800'),
('Santé', 'صحة', 'sante', '🏥', '#F44336'),
('Maison & Décoration', 'منزل وديكور', 'maison-decoration', '🏠', '#795548'),
('Automobile', 'سيارات', 'automobile', '🚗', '#607D8B'),
('Éducation', 'تعليم', 'education', '🎓', '#00BCD4');

-- ============================================
-- TABLE : merchants
-- ============================================

CREATE TABLE merchants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Business information
    business_name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    trade_registry VARCHAR(100),
    tax_id VARCHAR(100),

    -- Contact
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    mobile_number VARCHAR(20),
    website VARCHAR(255),

    -- Address & Geolocation
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    governorate_id INTEGER NOT NULL REFERENCES governorates(id),
    city_id INTEGER NOT NULL REFERENCES cities(id),
    district VARCHAR(100),
    postal_code VARCHAR(10),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    geom GEOGRAPHY(POINT, 4326),
    influence_radius_meters INTEGER DEFAULT 100,

    -- Category
    category_id INTEGER NOT NULL REFERENCES categories(id),
    subcategory_id INTEGER REFERENCES categories(id),

    -- Media
    logo_url VARCHAR(500),
    cover_image_url VARCHAR(500),
    photos TEXT[],
    description TEXT,

    -- Opening hours (JSON)
    opening_hours JSONB,

    -- Subscription
    plan_id INTEGER,
    subscription_status VARCHAR(50) DEFAULT 'pending',
    subscription_start_date DATE,
    subscription_end_date DATE,
    monthly_alerts_quota INTEGER DEFAULT 0,
    monthly_alerts_sent INTEGER DEFAULT 0,

    -- Statistics
    average_rating DECIMAL(3, 2) DEFAULT 0,
    reviews_count INTEGER DEFAULT 0,
    total_advantages INTEGER DEFAULT 0,
    total_transactions INTEGER DEFAULT 0,

    -- Status
    status VARCHAR(50) DEFAULT 'pending',
    verified BOOLEAN DEFAULT FALSE,
    featured BOOLEAN DEFAULT FALSE,

    -- Owner
    owner_user_id UUID REFERENCES users(id),

    -- Documents
    patente_url VARCHAR(500),
    cin_url VARCHAR(500),
    rib_url VARCHAR(500),

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Spatial index for proximity queries
CREATE INDEX idx_merchants_geom ON merchants USING GIST(geom);
CREATE INDEX idx_merchants_category ON merchants(category_id);
CREATE INDEX idx_merchants_status ON merchants(status) WHERE status = 'active';
CREATE INDEX idx_merchants_featured ON merchants(featured) WHERE featured = TRUE;

-- Trigger for PostGIS geometry
CREATE OR REPLACE FUNCTION update_merchant_geom()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
        NEW.geom := ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326)::geography;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_merchant_geom
BEFORE INSERT OR UPDATE ON merchants
FOR EACH ROW
EXECUTE FUNCTION update_merchant_geom();

-- Additional tables will be loaded from separate migration files
