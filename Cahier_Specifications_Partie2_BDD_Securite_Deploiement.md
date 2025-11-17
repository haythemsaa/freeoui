# CAHIER DES SPÉCIFICATIONS - PARTIE 2
## Base de Données, Sécurité & Déploiement

---

## 6. MODÈLE DE DONNÉES COMPLET

### 6.1 Schéma PostgreSQL avec PostGIS

```sql
-- ============================================
-- EXTENSION POSTGIS (Géospatial)
-- ============================================

CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- TABLE : users
-- ============================================

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    
    -- Authentification
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    country_code VARCHAR(5) DEFAULT '+216',
    email VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP,
    password VARCHAR(255), -- Nullable si OAuth only
    
    -- OAuth
    google_id VARCHAR(255),
    facebook_id VARCHAR(255),
    
    -- Profil
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(500),
    date_of_birth DATE,
    gender VARCHAR(50),
    
    -- Localisation
    governorate_id INTEGER,
    city_id INTEGER,
    district VARCHAR(100),
    postal_code VARCHAR(10),
    address_details TEXT,
    
    -- Préférences
    preferred_language VARCHAR(5) DEFAULT 'fr', -- fr, ar
    
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
-- TABLE : user_proximity_preferences ⭐
-- ============================================

CREATE TABLE user_proximity_preferences (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Alertes de proximité ⭐
    proximity_alerts_enabled BOOLEAN DEFAULT TRUE,
    proximity_radius_meters INTEGER DEFAULT 1000, -- 500, 1000, 2000, 5000
    
    -- Catégories d'intérêt
    interested_category_ids INTEGER[] DEFAULT '{}',
    
    -- Plages horaires
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    
    -- Limites
    max_daily_notifications INTEGER DEFAULT 5,
    min_notification_interval_minutes INTEGER DEFAULT 30,
    
    -- Préférences avancées
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
-- TABLE : user_locations ⭐ (Tracking GPS)
-- ============================================

CREATE TABLE user_locations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Coordonnées GPS
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    geom GEOGRAPHY(POINT, 4326), -- PostGIS geometry
    
    -- Précision et contexte
    accuracy_meters DECIMAL(10, 2),
    altitude DECIMAL(10, 2),
    speed_mps DECIMAL(10, 2), -- Vitesse en m/s
    heading_degrees DECIMAL(5, 2), -- Direction (0-360°)
    
    -- Source
    source VARCHAR(50) DEFAULT 'app', -- app, background_tracking
    
    -- Meta
    recorded_at TIMESTAMP DEFAULT NOW(),
    
    -- Contraintes
    CONSTRAINT valid_latitude CHECK (latitude >= -90 AND latitude <= 90),
    CONSTRAINT valid_longitude CHECK (longitude >= -180 AND longitude <= 180)
);

-- Index spatial pour requêtes de proximité ultra-rapides
CREATE INDEX idx_user_locations_geom ON user_locations USING GIST(geom);
CREATE INDEX idx_user_locations_user_time ON user_locations(user_id, recorded_at DESC);

-- Trigger pour générer automatiquement la géométrie PostGIS
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

-- Données Tunisie
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
    icon VARCHAR(100), -- emoji ou icon name
    color VARCHAR(20), -- hex color
    parent_id INTEGER REFERENCES categories(id),
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Catégories principales
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
    
    -- Informations entreprise
    business_name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    trade_registry VARCHAR(100),
    tax_id VARCHAR(100),
    
    -- Contact
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    mobile_number VARCHAR(20),
    website VARCHAR(255),
    
    -- Adresse & Géolocalisation ⭐
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    governorate_id INTEGER NOT NULL REFERENCES governorates(id),
    city_id INTEGER NOT NULL REFERENCES cities(id),
    district VARCHAR(100),
    postal_code VARCHAR(10),
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    geom GEOGRAPHY(POINT, 4326), -- PostGIS
    influence_radius_meters INTEGER DEFAULT 100, -- Rayon d'influence
    
    -- Catégorie
    category_id INTEGER NOT NULL REFERENCES categories(id),
    subcategory_id INTEGER REFERENCES categories(id),
    
    -- Média
    logo_url VARCHAR(500),
    cover_image_url VARCHAR(500),
    photos TEXT[], -- Array d'URLs
    description TEXT,
    
    -- Horaires (JSON)
    opening_hours JSONB,
    /*
    Format:
    {
      "monday": [{"start": "09:00", "end": "18:00"}],
      "tuesday": [{"start": "09:00", "end": "18:00"}],
      ...
      "sunday": null  (fermé)
    }
    */
    
    -- Abonnement
    plan_id INTEGER,
    subscription_status VARCHAR(50) DEFAULT 'pending',
    subscription_start_date DATE,
    subscription_end_date DATE,
    monthly_alerts_quota INTEGER DEFAULT 0,
    monthly_alerts_sent INTEGER DEFAULT 0,
    
    -- Statistiques
    average_rating DECIMAL(3, 2) DEFAULT 0,
    reviews_count INTEGER DEFAULT 0,
    total_advantages INTEGER DEFAULT 0,
    total_transactions INTEGER DEFAULT 0,
    
    -- Status
    status VARCHAR(50) DEFAULT 'pending', -- pending, active, suspended, closed
    verified BOOLEAN DEFAULT FALSE,
    featured BOOLEAN DEFAULT FALSE,
    
    -- Owner
    owner_user_id UUID REFERENCES users(id),
    
    -- Documents (URLs S3)
    patente_url VARCHAR(500),
    cin_url VARCHAR(500),
    rib_url VARCHAR(500),
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Index spatial pour requêtes de proximité
CREATE INDEX idx_merchants_geom ON merchants USING GIST(geom);
CREATE INDEX idx_merchants_category ON merchants(category_id);
CREATE INDEX idx_merchants_status ON merchants(status) WHERE status = 'active';
CREATE INDEX idx_merchants_featured ON merchants(featured) WHERE featured = TRUE;

-- Trigger pour géométrie PostGIS
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

-- ============================================
-- TABLE : advantages (Offres)
-- ============================================

CREATE TABLE advantages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    
    -- Informations principales
    title VARCHAR(255) NOT NULL,
    short_description VARCHAR(500),
    description TEXT,
    
    -- Type et valeur
    type VARCHAR(50) NOT NULL, -- percentage, fixed_amount, buy_x_get_y, free_item
    discount_percentage DECIMAL(5, 2),
    discount_amount DECIMAL(10, 3),
    buy_quantity INTEGER,
    get_quantity INTEGER,
    free_item_description VARCHAR(255),
    
    -- Catégorie
    category_id INTEGER NOT NULL REFERENCES categories(id),
    subcategory_id INTEGER REFERENCES categories(id),
    
    -- Conditions
    minimum_purchase_amount DECIMAL(10, 3),
    maximum_discount_amount DECIMAL(10, 3),
    terms_and_conditions TEXT,
    exclusions TEXT,
    
    -- Disponibilité
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    days_available INTEGER[] DEFAULT '{1,2,3,4,5,6,7}', -- 1=Lundi, 7=Dimanche
    time_slots JSONB, -- [{"start": "09:00", "end": "12:00"}]
    
    -- Limitations
    max_uses_per_user INTEGER DEFAULT 1,
    max_uses_total INTEGER,
    current_uses_count INTEGER DEFAULT 0,
    
    -- Média
    main_image_url VARCHAR(500),
    images TEXT[],
    
    -- Visibilité
    status VARCHAR(50) DEFAULT 'draft', -- draft, active, paused, expired
    is_featured BOOLEAN DEFAULT FALSE,
    is_exclusive BOOLEAN DEFAULT FALSE,
    
    -- Statistiques
    views_count INTEGER DEFAULT 0,
    saves_count INTEGER DEFAULT 0,
    shares_count INTEGER DEFAULT 0,
    proximity_alerts_sent INTEGER DEFAULT 0, -- ⭐
    proximity_alerts_opened INTEGER DEFAULT 0, -- ⭐
    qr_codes_generated INTEGER DEFAULT 0,
    uses_count INTEGER DEFAULT 0,
    conversion_rate DECIMAL(5, 2) DEFAULT 0,
    average_rating DECIMAL(3, 2) DEFAULT 0,
    reviews_count INTEGER DEFAULT 0,
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    created_by UUID REFERENCES users(id),
    deleted_at TIMESTAMP,
    
    -- Contraintes
    CHECK (
        (type = 'percentage' AND discount_percentage IS NOT NULL) OR
        (type = 'fixed_amount' AND discount_amount IS NOT NULL) OR
        (type = 'buy_x_get_y' AND buy_quantity IS NOT NULL AND get_quantity IS NOT NULL) OR
        (type = 'free_item' AND free_item_description IS NOT NULL)
    )
);

CREATE INDEX idx_advantages_merchant ON advantages(merchant_id);
CREATE INDEX idx_advantages_status_dates ON advantages(status, start_date, end_date) WHERE status = 'active';
CREATE INDEX idx_advantages_category ON advantages(category_id);
CREATE INDEX idx_advantages_featured ON advantages(is_featured) WHERE is_featured = TRUE;

-- ============================================
-- TABLE : proximity_alerts_log ⭐
-- ============================================

CREATE TABLE proximity_alerts_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    advantage_id UUID NOT NULL REFERENCES advantages(id) ON DELETE CASCADE,
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    
    -- Position utilisateur lors de l'alerte
    user_latitude DECIMAL(10, 8) NOT NULL,
    user_longitude DECIMAL(11, 8) NOT NULL,
    user_geom GEOGRAPHY(POINT, 4326),
    distance_meters DECIMAL(10, 2) NOT NULL,
    
    -- Statut notification
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at TIMESTAMP,
    notification_opened BOOLEAN DEFAULT FALSE,
    notification_opened_at TIMESTAMP,
    notification_fcm_id VARCHAR(255), -- ID message FCM
    
    -- Action utilisateur
    advantage_viewed BOOLEAN DEFAULT FALSE,
    advantage_viewed_at TIMESTAMP,
    advantage_saved BOOLEAN DEFAULT FALSE,
    qr_code_generated BOOLEAN DEFAULT FALSE,
    advantage_used BOOLEAN DEFAULT FALSE,
    
    -- Contexte
    user_speed_mps DECIMAL(10, 2), -- Vitesse utilisateur
    time_of_day VARCHAR(20), -- morning, afternoon, evening, night
    day_of_week INTEGER, -- 1-7
    
    -- Score de pertinence (calculé par l'algo)
    relevance_score DECIMAL(5, 2),
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW()
);

-- Index pour analytics et requêtes
CREATE INDEX idx_proximity_alerts_user_date ON proximity_alerts_log(user_id, created_at DESC);
CREATE INDEX idx_proximity_alerts_merchant_date ON proximity_alerts_log(merchant_id, created_at DESC);
CREATE INDEX idx_proximity_alerts_advantage_date ON proximity_alerts_log(advantage_id, created_at DESC);
CREATE INDEX idx_proximity_alerts_opened ON proximity_alerts_log(notification_opened, notification_opened_at);
CREATE INDEX idx_proximity_alerts_conversion ON proximity_alerts_log(advantage_used) WHERE advantage_used = TRUE;
CREATE INDEX idx_proximity_alerts_geom ON proximity_alerts_log USING GIST(user_geom);

-- ============================================
-- TABLE : qr_codes
-- ============================================

CREATE TABLE qr_codes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    advantage_id UUID NOT NULL REFERENCES advantages(id) ON DELETE CASCADE,
    
    -- Code
    code VARCHAR(255) UNIQUE NOT NULL,
    qr_image_url VARCHAR(500),
    
    -- Validité
    valid_from TIMESTAMP NOT NULL DEFAULT NOW(),
    valid_until TIMESTAMP NOT NULL,
    
    -- Usage
    status VARCHAR(50) DEFAULT 'active', -- active, used, expired, cancelled
    used_at TIMESTAMP,
    validated_by UUID REFERENCES users(id), -- Employé commerçant
    validation_location_latitude DECIMAL(10, 8),
    validation_location_longitude DECIMAL(11, 8),
    
    -- Montants (si applicable)
    original_amount DECIMAL(10, 3),
    discount_amount DECIMAL(10, 3),
    final_amount DECIMAL(10, 3),
    
    -- Sécurité
    signature VARCHAR(500), -- SHA256 hash pour validation
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    
    -- Un user ne peut générer qu'un QR actif à la fois pour un avantage
    UNIQUE(user_id, advantage_id, status)
);

CREATE INDEX idx_qr_codes_user ON qr_codes(user_id, created_at DESC);
CREATE INDEX idx_qr_codes_code ON qr_codes(code);
CREATE INDEX idx_qr_codes_status ON qr_codes(status);
CREATE INDEX idx_qr_codes_advantage ON qr_codes(advantage_id);

-- ============================================
-- TABLE : transactions
-- ============================================

CREATE TABLE transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    
    -- Références
    user_id UUID NOT NULL REFERENCES users(id),
    merchant_id UUID NOT NULL REFERENCES merchants(id),
    advantage_id UUID NOT NULL REFERENCES advantages(id),
    qr_code_id UUID REFERENCES qr_codes(id),
    
    -- Montants
    original_amount DECIMAL(10, 3) NOT NULL,
    discount_amount DECIMAL(10, 3) NOT NULL,
    final_amount DECIMAL(10, 3) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TND',
    
    -- Détails transaction
    transaction_date TIMESTAMP NOT NULL DEFAULT NOW(),
    validated_by UUID REFERENCES users(id), -- Employé qui a scanné
    
    -- Localisation
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Points & Gamification
    points_earned INTEGER DEFAULT 0,
    
    -- Notes
    notes TEXT,
    merchant_notes TEXT,
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_transactions_user ON transactions(user_id, transaction_date DESC);
CREATE INDEX idx_transactions_merchant ON transactions(merchant_id, transaction_date DESC);
CREATE INDEX idx_transactions_advantage ON transactions(advantage_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date DESC);

-- ============================================
-- TABLE : user_favorites
-- ============================================

CREATE TABLE user_favorites (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    advantage_id UUID REFERENCES advantages(id) ON DELETE CASCADE,
    merchant_id UUID REFERENCES merchants(id) ON DELETE CASCADE,
    
    created_at TIMESTAMP DEFAULT NOW(),
    
    -- Un favori peut être soit un advantage soit un merchant
    CHECK (
        (advantage_id IS NOT NULL AND merchant_id IS NULL) OR
        (advantage_id IS NULL AND merchant_id IS NOT NULL)
    )
);

CREATE UNIQUE INDEX idx_user_favorites_advantage 
ON user_favorites(user_id, advantage_id) 
WHERE advantage_id IS NOT NULL;

CREATE UNIQUE INDEX idx_user_favorites_merchant 
ON user_favorites(user_id, merchant_id) 
WHERE merchant_id IS NOT NULL;

-- ============================================
-- TABLE : reviews
-- ============================================

CREATE TABLE reviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    
    -- Références
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    merchant_id UUID REFERENCES merchants(id) ON DELETE CASCADE,
    advantage_id UUID REFERENCES advantages(id) ON DELETE CASCADE,
    transaction_id UUID REFERENCES transactions(id),
    
    -- Note et commentaire
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    
    -- Média
    photos TEXT[],
    
    -- Réponse commerçant
    merchant_response TEXT,
    merchant_response_at TIMESTAMP,
    merchant_response_by UUID REFERENCES users(id),
    
    -- Modération
    status VARCHAR(50) DEFAULT 'pending', -- pending, approved, rejected
    moderated_by UUID REFERENCES users(id),
    moderated_at TIMESTAMP,
    rejection_reason TEXT,
    
    -- Utilité
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    -- Un review peut être pour un merchant OU un advantage
    CHECK (
        (merchant_id IS NOT NULL AND advantage_id IS NULL) OR
        (merchant_id IS NULL AND advantage_id IS NOT NULL)
    )
);

CREATE INDEX idx_reviews_merchant ON reviews(merchant_id, status, created_at DESC);
CREATE INDEX idx_reviews_advantage ON reviews(advantage_id, status, created_at DESC);
CREATE INDEX idx_reviews_user ON reviews(user_id, created_at DESC);
CREATE INDEX idx_reviews_status ON reviews(status);

-- ============================================
-- TABLE : notifications
-- ============================================

CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Type et contenu
    type VARCHAR(50) NOT NULL, 
    -- Types: proximity_alert, offer_expiring, new_offer, transaction_confirmed, etc.
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    
    -- Données associées (JSON)
    data JSONB,
    /*
    Format selon type:
    proximity_alert: {
      "advantage_id": "uuid",
      "merchant_id": "uuid",
      "distance_meters": 350,
      "discount": "30%"
    }
    */
    
    -- Navigation
    action_url VARCHAR(500),
    deep_link VARCHAR(500), -- Deep link app mobile
    
    -- Statut
    read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    
    -- Envoi
    sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP,
    delivery_status VARCHAR(50), -- sent, delivered, failed
    fcm_message_id VARCHAR(255),
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    expires_at TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id, read, created_at DESC);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_sent ON notifications(sent_at DESC) WHERE sent = TRUE;

-- ============================================
-- TABLE : subscription_plans
-- ============================================

CREATE TABLE subscription_plans (
    id SERIAL PRIMARY KEY,
    
    -- Informations
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    
    -- Tarification
    price_monthly DECIMAL(10, 3) NOT NULL,
    price_quarterly DECIMAL(10, 3),
    price_yearly DECIMAL(10, 3),
    currency VARCHAR(3) DEFAULT 'TND',
    
    -- Limites
    max_active_advantages INTEGER, -- NULL = illimité
    max_photos_per_advantage INTEGER DEFAULT 10,
    monthly_alerts_quota INTEGER, -- ⭐
    
    -- Fonctionnalités (JSON)
    features JSONB,
    /*
    {
      "analytics": true,
      "priority_support": false,
      "featured_listing": false,
      "heat_map": true,
      "ai_recommendations": false
    }
    */
    
    -- Visibilité
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- Ordre affichage
    sort_order INTEGER DEFAULT 0,
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Plans par défaut
INSERT INTO subscription_plans (name_fr, name_ar, slug, price_monthly, max_active_advantages, monthly_alerts_quota, features) VALUES
('Starter', 'المبتدئ', 'starter', 49.000, 3, 1000, '{"analytics": false, "priority_support": false, "featured_listing": false, "heat_map": false}'),
('Business', 'الأعمال', 'business', 99.000, 10, 5000, '{"analytics": true, "priority_support": false, "featured_listing": false, "heat_map": true}'),
('Premium', 'المميز', 'premium', 199.000, NULL, 20000, '{"analytics": true, "priority_support": true, "featured_listing": true, "heat_map": true, "ai_recommendations": true}');

-- ============================================
-- TABLE : merchant_subscriptions
-- ============================================

CREATE TABLE merchant_subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    plan_id INTEGER NOT NULL REFERENCES subscription_plans(id),
    
    -- Période
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    billing_cycle VARCHAR(20) NOT NULL, -- monthly, quarterly, yearly
    
    -- Paiement
    amount DECIMAL(10, 3) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TND',
    payment_status VARCHAR(50) DEFAULT 'pending', -- pending, paid, failed, refunded
    payment_method VARCHAR(50), -- d17, flouci, konnect, bank_transfer
    payment_reference VARCHAR(255),
    payment_date TIMESTAMP,
    
    -- Renouvellement
    auto_renew BOOLEAN DEFAULT TRUE,
    renewal_reminder_sent BOOLEAN DEFAULT FALSE,
    
    -- Statut
    status VARCHAR(50) DEFAULT 'active', -- active, cancelled, expired
    cancelled_at TIMESTAMP,
    cancellation_reason TEXT,
    
    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_merchant_subscriptions_merchant ON merchant_subscriptions(merchant_id);
CREATE INDEX idx_merchant_subscriptions_status ON merchant_subscriptions(status, end_date);

-- ============================================
-- VUES MATÉRIALISÉES (Pour performance)
-- ============================================

-- Vue : Statistiques merchants en temps réel
CREATE MATERIALIZED VIEW mv_merchant_stats AS
SELECT 
    m.id AS merchant_id,
    m.business_name,
    COUNT(DISTINCT a.id) AS total_advantages,
    COUNT(DISTINCT CASE WHEN a.status = 'active' THEN a.id END) AS active_advantages,
    COALESCE(SUM(a.proximity_alerts_sent), 0) AS total_alerts_sent,
    COALESCE(SUM(a.uses_count), 0) AS total_uses,
    COALESCE(AVG(a.conversion_rate), 0) AS avg_conversion_rate,
    COUNT(DISTINCT t.id) AS total_transactions,
    COALESCE(SUM(t.discount_amount), 0) AS total_discounts_given,
    COALESCE(AVG(r.rating), 0) AS average_rating,
    COUNT(DISTINCT r.id) AS total_reviews
FROM merchants m
LEFT JOIN advantages a ON m.id = a.merchant_id
LEFT JOIN transactions t ON m.id = t.merchant_id
LEFT JOIN reviews r ON m.id = r.merchant_id AND r.status = 'approved'
WHERE m.status = 'active'
GROUP BY m.id, m.business_name;

CREATE UNIQUE INDEX ON mv_merchant_stats(merchant_id);

-- Rafraîchissement toutes les heures
-- À configurer via cron job : REFRESH MATERIALIZED VIEW CONCURRENTLY mv_merchant_stats;

-- ============================================
-- FONCTIONS UTILITAIRES
-- ============================================

-- Fonction : Calculer la distance entre 2 points GPS
CREATE OR REPLACE FUNCTION calculate_distance(
    lat1 DECIMAL, lon1 DECIMAL,
    lat2 DECIMAL, lon2 DECIMAL
)
RETURNS DECIMAL AS $$
DECLARE
    distance DECIMAL;
BEGIN
    SELECT ST_Distance(
        ST_MakePoint(lon1, lat1)::geography,
        ST_MakePoint(lon2, lat2)::geography
    ) INTO distance;
    RETURN distance;
END;
$$ LANGUAGE plpgsql;

-- Fonction : Trouver commerces à proximité
CREATE OR REPLACE FUNCTION find_nearby_merchants(
    user_lat DECIMAL,
    user_lon DECIMAL,
    radius_meters INTEGER DEFAULT 1000
)
RETURNS TABLE (
    merchant_id UUID,
    business_name VARCHAR,
    distance_meters DECIMAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        m.id,
        m.business_name,
        ST_Distance(
            ST_MakePoint(user_lon, user_lat)::geography,
            m.geom
        ) AS distance
    FROM merchants m
    WHERE 
        m.status = 'active'
        AND ST_DWithin(
            ST_MakePoint(user_lon, user_lat)::geography,
            m.geom,
            radius_meters
        )
    ORDER BY distance ASC;
END;
$$ LANGUAGE plpgsql;

-- Fonction : Trouver avantages à proximité avec filtres
CREATE OR REPLACE FUNCTION find_nearby_advantages(
    user_lat DECIMAL,
    user_lon DECIMAL,
    radius_meters INTEGER DEFAULT 1000,
    category_ids INTEGER[] DEFAULT NULL,
    min_discount INTEGER DEFAULT 0
)
RETURNS TABLE (
    advantage_id UUID,
    merchant_id UUID,
    title VARCHAR,
    discount_percentage DECIMAL,
    distance_meters DECIMAL
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.id,
        a.merchant_id,
        a.title,
        a.discount_percentage,
        ST_Distance(
            ST_MakePoint(user_lon, user_lat)::geography,
            m.geom
        ) AS distance
    FROM advantages a
    JOIN merchants m ON a.merchant_id = m.id
    WHERE 
        a.status = 'active'
        AND a.start_date <= NOW()
        AND a.end_date >= NOW()
        AND m.status = 'active'
        AND ST_DWithin(
            ST_MakePoint(user_lon, user_lat)::geography,
            m.geom,
            radius_meters
        )
        AND (category_ids IS NULL OR a.category_id = ANY(category_ids))
        AND (a.discount_percentage >= min_discount OR a.discount_percentage IS NULL)
    ORDER BY distance ASC;
END;
$$ LANGUAGE plpgsql;
```

---

## 7. SÉCURITÉ ET CONFORMITÉ

### 7.1 Protection des données personnelles

#### 7.1.1 Conformité RGPD et loi tunisienne

**Mesures implémentées :**

1. **Chiffrement**
   - SSL/TLS (HTTPS) obligatoire sur toutes les communications
   - Chiffrement AES-256 pour données sensibles en BDD
   - Hashage Bcrypt (cost factor 12) pour mots de passe
   - Tokens JWT signés avec RS256

2. **Minimisation des données**
   - Collecte uniquement des données nécessaires
   - Géolocalisation précise jamais partagée avec commerçants
   - Anonymisation des données analytics
   - Agrégation statistiques (pas de données individuelles)

3. **Droits des utilisateurs**
   - Droit d'accès : Export complet données personnelles (JSON)
   - Droit de rectification : Modification profil
   - Droit à l'oubli : Suppression compte + données (soft delete 30j, puis hard delete)
   - Droit à la portabilité : Export données format machine-readable

4. **Consentement**
   - Opt-in explicite pour alertes de proximité
   - Opt-in explicite pour notifications marketing
   - Acceptation CGU + Politique confidentialité obligatoire
   - Possibilité de retirer consentement à tout moment

5. **Rétention des données**
   - Données utilisateur : Conservation tant que compte actif
   - Locations GPS : 30 jours maximum, puis suppression auto
   - Logs système : 90 jours
   - Transactions : 3 ans (obligation légale comptable)
   - Données commerçants : 5 ans après fermeture compte

#### 7.1.2 Sécurité géolocalisation

**Principes :**
- Données GPS chiffrées en transit et au repos
- Jamais de géolocalisation en temps réel visible par commerçants
- Agrégation uniquement (heatmaps anonymisées)
- Utilisateur contrôle total (ON/OFF instantané)

**Ce que voient les commerçants :**
```
✅ AUTORISÉ :
- Nombre total d'alertes envoyées
- Taux de conversion global
- Heatmap zones d'où viennent les clients (agrégé, anonymisé)
- Statistiques par rayon (0-500m, 500m-1km, etc.)

❌ INTERDIT :
- Position GPS précise des utilisateurs
- Trajets individuels
- Identité utilisateurs avant validation QR
- Historique déplacements
```

### 7.2 Sécurité API

#### 7.2.1 Authentification JWT

```php
// Structure JWT Token
{
  "header": {
    "alg": "RS256",
    "typ": "JWT"
  },
  "payload": {
    "sub": "uuid-user-id",
    "role": "user", // user, merchant, admin
    "iat": 1700224800,
    "exp": 1700228400, // 1 heure
    "jti": "uuid-token-id"
  },
  "signature": "..."
}
```

**Gestion tokens :**
- Access Token : Durée 1 heure
- Refresh Token : Durée 30 jours
- Rotation automatique refresh tokens
- Révocation possible (blacklist Redis)
- Rate limiting par utilisateur

#### 7.2.2 Rate Limiting

```
Limites par endpoint (par IP ou user_id) :

Authentification :
- POST /auth/register : 5 req/heure
- POST /auth/login : 10 req/heure
- POST /auth/verify-otp : 3 req/5min

Géolocalisation :
- POST /users/location : 120 req/heure (toutes les 30s)

Avantages :
- GET /advantages : 100 req/heure
- GET /advantages/{id} : 200 req/heure

QR Codes :
- POST /qr-codes/generate : 20 req/heure
- POST /qr-codes/validate : 100 req/heure (commerçants)

Général :
- 1000 req/heure par utilisateur
- 10000 req/heure par commerçant (plan premium)
```

#### 7.2.3 Validation et sanitization

**Toutes les entrées utilisateur sont :**
- Validées côté serveur (jamais confiance client)
- Sanitizées (strip HTML, SQL injection prevention)
- Type-checked (strong typing)
- Limitées en taille (max length)

**Exemple validation :**
```php
// Laravel Request Validation
class UpdateProximityPreferencesRequest extends FormRequest
{
    public function rules()
    {
        return [
            'proximity_alerts_enabled' => 'required|boolean',
            'proximity_radius_meters' => 'required|integer|in:500,1000,2000,5000',
            'interested_category_ids' => 'required|array|min:1',
            'interested_category_ids.*' => 'integer|exists:categories,id',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'max_daily_notifications' => 'required|integer|min:1|max:20',
        ];
    }
}
```

### 7.3 Sécurité QR Codes

#### 7.3.1 Génération sécurisée

```php
// Génération QR Code avec signature
function generateSecureQRCode($userId, $advantageId) {
    $code = Str::random(20); // Code aléatoire
    
    // Signature HMAC
    $dataToSign = $userId . $advantageId . $code . now()->timestamp;
    $signature = hash_hmac('sha256', $dataToSign, config('app.qr_secret'));
    
    // Stockage
    QRCode::create([
        'user_id' => $userId,
        'advantage_id' => $advantageId,
        'code' => $code,
        'signature' => $signature,
        'valid_until' => now()->addHours(24),
        'status' => 'active'
    ]);
    
    return $code;
}
```

#### 7.3.2 Validation sécurisée

```php
// Validation QR Code
function validateQRCode($code, $merchantId) {
    $qr = QRCode::where('code', $code)->first();
    
    if (!$qr) {
        throw new QRCodeNotFoundException();
    }
    
    // Vérifications
    if ($qr->status !== 'active') {
        throw new QRCodeAlreadyUsedException($qr->used_at);
    }
    
    if ($qr->valid_until < now()) {
        throw new QRCodeExpiredException($qr->valid_until);
    }
    
    // Vérifier que le merchant correspond
    if ($qr->advantage->merchant_id !== $merchantId) {
        throw new UnauthorizedMerchantException();
    }
    
    // Vérifier signature
    $dataToSign = $qr->user_id . $qr->advantage_id . $qr->code . $qr->created_at->timestamp;
    $expectedSignature = hash_hmac('sha256', $dataToSign, config('app.qr_secret'));
    
    if (!hash_equals($expectedSignature, $qr->signature)) {
        throw new InvalidQRCodeSignatureException();
    }
    
    return $qr;
}
```

### 7.4 Monitoring et logging

#### 7.4.1 Logs de sécurité

**Événements loggés :**
- Tentatives de connexion (succès/échecs)
- Modifications paramètres sensibles (géolocalisation, etc.)
- Génération/validation QR codes
- Envois d'alertes de proximité
- Erreurs 4xx, 5xx
- Accès non autorisés
- Changements de rôles/permissions

#### 7.4.2 Alertes automatiques

**Déclenchement alerte si :**
- > 5 échecs login en 5 min (même IP)
- > 100 requêtes/min (DDoS potentiel)
- Tentative accès endpoint admin sans permission
- Modifications en masse de données
- Erreurs 500 en pic
- Latence API > 5s

---

## 8. PLAN DE DÉPLOIEMENT

### 8.1 Infrastructure cible

#### 8.1.1 Environnements

```
┌─────────────────────────────────────────────┐
│ DÉVELOPPEMENT (Local)                        │
├─────────────────────────────────────────────┤
│ • Docker Compose                             │
│ • Laravel Sail                               │
│ • PostgreSQL + PostGIS local                 │
│ • Redis local                                │
│ • MinIO (S3 local)                           │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ STAGING (Pré-production)                     │
├─────────────────────────────────────────────┤
│ • VPS 4 vCPU, 8GB RAM                        │
│ • Domaine: staging.app.tn                    │
│ • Base de données 25% production             │
│ • Tests E2E automatisés                      │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ PRODUCTION                                   │
├─────────────────────────────────────────────┤
│ • App Server : 8 vCPU, 16GB RAM (x2)         │
│ • DB Server : 8 vCPU, 32GB RAM, SSD NVMe     │
│ • Redis : 4GB RAM                            │
│ • Load Balancer (HAProxy/Nginx)              │
│ • CDN (Cloudflare)                           │
│ • Backup automatique quotidien               │
│ • Monitoring 24/7                            │
└─────────────────────────────────────────────┘
```

#### 8.1.2 Stack production

```yaml
# docker-compose.prod.yml (simplifié)
version: '3.8'

services:
  app:
    image: registry.app.tn/backend:latest
    deploy:
      replicas: 2
      resources:
        limits:
          cpus: '4'
          memory: 8G
    environment:
      - APP_ENV=production
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgis/postgis:16-3.4
    volumes:
      - pgdata:/var/lib/postgresql/data
    environment:
      - POSTGRES_DB=app_db
      - POSTGRES_USER=app_user
      - POSTGRES_PASSWORD=${DB_PASSWORD}

  redis:
    image: redis:7-alpine
    command: redis-server --maxmemory 4gb --maxmemory-policy allkeys-lru

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - /etc/letsencrypt:/etc/letsencrypt
```

### 8.2 Phases de déploiement

#### 8.2.1 Phase 1 : MVP (Mois 1-3)

**Périmètre :**
- Tunis + Sousse uniquement
- 100 commerçants max
- Alertes de proximité (rayon 1km uniquement)
- Back-office basique
- Paiement manuel (virement bancaire)

**Objectifs :**
- 10 000 utilisateurs
- 50 000 alertes envoyées
- Validation concept
- Feedback utilisateurs

#### 8.2.2 Phase 2 : Scale (Mois 4-8)

**Ajouts :**
- Extension 4 nouveaux gouvernorats (Sfax, Monastir, Nabeul, Bizerte)
- 500 commerçants
- Tous rayons de proximité (500m à 5km)
- Programme de parrainage
- Analytics avancées
- Intégration paiement mobile (D17, Flouci)

**Objectifs :**
- 50 000 utilisateurs actifs
- 500 000 alertes/mois
- 5 000 transactions/mois

#### 8.2.3 Phase 3 : National (Mois 9-18)

**Ajouts :**
- Couverture 24 gouvernorats
- 3 000 commerçants
- Reviews & ratings
- Messagerie intégrée
- IA pour recommandations
- API publique pour partenaires

**Objectifs :**
- 300 000 utilisateurs actifs
- 5 000 000 alertes/mois
- 50 000 transactions/mois
- Rentabilité

### 8.3 CI/CD Pipeline

```yaml
# .github/workflows/deploy-prod.yml
name: Deploy Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: |
          composer install
          php artisan test --parallel

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Build Docker image
        run: |
          docker build -t registry.app.tn/backend:${{ github.sha }} .
          docker push registry.app.tn/backend:${{ github.sha }}

  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PROD_HOST }}
          username: deploy
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/app
            docker pull registry.app.tn/backend:${{ github.sha }}
            docker-compose up -d
            php artisan migrate --force
            php artisan cache:clear
            php artisan config:cache
```

### 8.4 Monitoring et alertes

#### 8.4.1 Métriques à surveiller

**Performance :**
- Latence API (p50, p95, p99)
- Throughput (req/s)
- Taux d'erreur (%)
- Disponibilité (uptime %)

**Business :**
- Alertes proximité envoyées/heure
- Taux conversion alertes → visites
- QR codes générés/validés
- Nouveaux utilisateurs/jour
- Transactions/jour
- Churn rate

**Infrastructure :**
- CPU usage
- RAM usage
- Disk I/O
- Network I/O
- Database connections
- Queue depth

#### 8.4.2 Alertes critiques

```
🚨 CRITIQUE (Intervention immédiate) :
- API down > 1 min
- DB down
- CPU > 90% sustained 5 min
- Disk > 90%
- Erreurs 5xx > 1%

⚠️ WARNING (Surveillance rapprochée) :
- Latence p95 > 2s
- CPU > 80% sustained 10 min
- RAM > 80%
- Queue depth > 1000 jobs
```

---

## 9. ANNEXES

### 9.1 Glossaire

| Terme | Définition |
|-------|------------|
| **Alerte de proximité** | Notification push envoyée automatiquement quand l'utilisateur entre dans un rayon défini autour d'un commerce avec offre active |
| **Rayon de proximité** | Distance configurable (500m à 5km) dans laquelle l'utilisateur souhaite recevoir des alertes |
| **Géofencing** | Technologie permettant de déclencher des actions (notifications) quand un appareil entre/sort d'une zone géographique |
| **PostGIS** | Extension PostgreSQL pour requêtes géospatiales (calcul distances, recherche proximité, etc.) |
| **Conversion** | Parcours complet : Alerte → Vue → Génération QR → Validation |
| **Heatmap** | Carte de chaleur montrant zones géographiques avec forte concentration d'événements (alertes, visites) |

### 9.2 Stack technique complète

```
BACKEND
├── Framework: Laravel 11.x
├── Langage: PHP 8.3+
├── Base de données: PostgreSQL 16 + PostGIS
├── Cache: Redis 7.x
├── Queue: Laravel Queues (Redis)
├── Storage: MinIO (S3-compatible)
├── Search: Meilisearch (optionnel)
└── Real-time: Laravel Reverb

MOBILE
├── Framework: Flutter 3.19+
├── Langage: Dart 3.3+
├── State: Riverpod 2.x
├── Networking: Dio + Retrofit
├── Local DB: Sqflite + Hive
├── Maps: Mapbox SDK / Google Maps
├── Geolocation: flutter_background_geolocation
├── Notifications: Firebase Cloud Messaging
└── Analytics: Firebase Analytics / Mixpanel

WEB (Back-office)
├── Framework: React 18 / Vue.js 3
├── Langage: TypeScript
├── UI: Ant Design / Material-UI
├── Maps: Mapbox GL JS
├── Charts: Recharts
└── Build: Vite

INFRASTRUCTURE
├── Hébergement: VPS Tunisie / Cloud
├── CI/CD: GitHub Actions
├── Monitoring: Sentry + Grafana
├── Logs: ELK Stack
├── CDN: Cloudflare
└── Container: Docker + Docker Compose
```

### 9.3 Estimations budgétaires (Année 1)

```
DÉVELOPPEMENT
├── Backend (Laravel)          : 25 000 € (3 mois, 2 devs)
├── Mobile (Flutter)           : 30 000 € (3 mois, 2 devs)
├── Web Admin (React)          : 15 000 € (2 mois, 1 dev)
├── DevOps & Infrastructure    : 8 000 €
└── Design UI/UX               : 5 000 €
    TOTAL DÉVELOPPEMENT        : 83 000 €

INFRASTRUCTURE (Mensuel)
├── Serveurs (VPS x3)          : 300 €/mois
├── Base de données            : 100 €/mois
├── CDN & Stockage             : 50 €/mois
├── Firebase (FCM)             : 50 €/mois
├── SMS Gateway                : 200 €/mois (OTP)
├── Monitoring & Logs          : 100 €/mois
└── Domaine & SSL              : 10 €/mois
    TOTAL INFRA (AN 1)         : 9 720 €

MARKETING & ACQUISITION
├── Marketing digital          : 20 000 €
├── Partenariats commerçants   : 15 000 €
├── Relations presse           : 5 000 €
└── Événements lancement       : 10 000 €
    TOTAL MARKETING            : 50 000 €

OPÉRATIONS (AN 1)
├── Support client (2 pers)    : 24 000 €
├── Account managers (2 pers)  : 30 000 €
├── Légal & conformité         : 5 000 €
└── Divers & imprévus          : 10 000 €
    TOTAL OPÉRATIONS           : 69 000 €

═══════════════════════════════════════════
TOTAL ANNÉE 1                  : 211 720 €
═══════════════════════════════════════════
```

### 9.4 Business Model

**Revenus commerçants :**
```
Plan Starter  :  49 TND/mois x 200 commerces = 9,800 TND/mois
Plan Business :  99 TND/mois x 200 commerces = 19,800 TND/mois
Plan Premium  : 199 TND/mois x 100 commerces = 19,900 TND/mois
                                               ─────────────────
                                    TOTAL MRR = 49,500 TND/mois
                                    TOTAL ARR = 594,000 TND/an
                                              ≈ 180,000 EUR/an
```

**Objectif rentabilité :** Mois 18-24

---

## CONCLUSION

Ce cahier des spécifications détaille l'intégralité du système d'une plateforme d'avantages commerciaux avec **système d'alertes de proximité intelligent** pour le marché tunisien.

### Points clés de l'innovation :

1. **Alertes géolocalisées en temps réel** avec rayon configurable (500m à 5km)
2. **Système anti-spam intelligent** (limites quotidiennes, filtres contextuels)
3. **Scoring de pertinence** pour sélectionner la meilleure offre
4. **Respect absolu de la vie privée** (géolocalisation jamais partagée)
5. **Analytics géospatiaux** (heatmaps, performance par rayon)

### Prochaines étapes :

1. Validation spécifications avec stakeholders
2. Conception maquettes UI/UX (Figma)
3. Setup environnements dev/staging
4. Sprint 1 : MVP Backend (API + BDD)
5. Sprint 2 : MVP Mobile (Alertes proximité)
6. Sprint 3 : Back-office commerçants
7. Tests alpha/beta
8. Lancement public

**Document vivant** : Ces spécifications seront mises à jour au fil du développement et des retours utilisateurs.

---

**Version** : 2.0 Complète  
**Date** : 17 Novembre 2025  
**Statut** : Prêt pour développement

