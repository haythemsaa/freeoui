-- ============================================
-- FREEOUI - ADVANTAGES AND QR CODES SCHEMA
-- ============================================

-- ============================================
-- TABLE : advantages (Offers/Promotions)
-- ============================================

CREATE TABLE advantages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,

    -- Main information
    title VARCHAR(255) NOT NULL,
    short_description VARCHAR(500),
    description TEXT,

    -- Type and value
    type VARCHAR(50) NOT NULL,
    discount_percentage DECIMAL(5, 2),
    discount_amount DECIMAL(10, 3),
    buy_quantity INTEGER,
    get_quantity INTEGER,
    free_item_description VARCHAR(255),

    -- Category
    category_id INTEGER NOT NULL REFERENCES categories(id),
    subcategory_id INTEGER REFERENCES categories(id),

    -- Conditions
    minimum_purchase_amount DECIMAL(10, 3),
    maximum_discount_amount DECIMAL(10, 3),
    terms_and_conditions TEXT,
    exclusions TEXT,

    -- Availability
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    days_available INTEGER[] DEFAULT '{1,2,3,4,5,6,7}',
    time_slots JSONB,

    -- Limitations
    max_uses_per_user INTEGER DEFAULT 1,
    max_uses_total INTEGER,
    current_uses_count INTEGER DEFAULT 0,

    -- Media
    main_image_url VARCHAR(500),
    images TEXT[],

    -- Visibility
    status VARCHAR(50) DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    is_exclusive BOOLEAN DEFAULT FALSE,

    -- Statistics
    views_count INTEGER DEFAULT 0,
    saves_count INTEGER DEFAULT 0,
    shares_count INTEGER DEFAULT 0,
    proximity_alerts_sent INTEGER DEFAULT 0,
    proximity_alerts_opened INTEGER DEFAULT 0,
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
-- TABLE : proximity_alerts_log
-- ============================================

CREATE TABLE proximity_alerts_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    advantage_id UUID NOT NULL REFERENCES advantages(id) ON DELETE CASCADE,
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,

    -- User position at alert time
    user_latitude DECIMAL(10, 8) NOT NULL,
    user_longitude DECIMAL(11, 8) NOT NULL,
    user_geom GEOGRAPHY(POINT, 4326),
    distance_meters DECIMAL(10, 2) NOT NULL,

    -- Notification status
    notification_sent BOOLEAN DEFAULT FALSE,
    notification_sent_at TIMESTAMP,
    notification_opened BOOLEAN DEFAULT FALSE,
    notification_opened_at TIMESTAMP,
    notification_fcm_id VARCHAR(255),

    -- User action
    advantage_viewed BOOLEAN DEFAULT FALSE,
    advantage_viewed_at TIMESTAMP,
    advantage_saved BOOLEAN DEFAULT FALSE,
    qr_code_generated BOOLEAN DEFAULT FALSE,
    advantage_used BOOLEAN DEFAULT FALSE,

    -- Context
    user_speed_mps DECIMAL(10, 2),
    time_of_day VARCHAR(20),
    day_of_week INTEGER,

    -- Relevance score
    relevance_score DECIMAL(5, 2),

    -- Meta
    created_at TIMESTAMP DEFAULT NOW()
);

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

    -- Validity
    valid_from TIMESTAMP NOT NULL DEFAULT NOW(),
    valid_until TIMESTAMP NOT NULL,

    -- Usage
    status VARCHAR(50) DEFAULT 'active',
    used_at TIMESTAMP,
    validated_by UUID REFERENCES users(id),
    validation_location_latitude DECIMAL(10, 8),
    validation_location_longitude DECIMAL(11, 8),

    -- Amounts
    original_amount DECIMAL(10, 3),
    discount_amount DECIMAL(10, 3),
    final_amount DECIMAL(10, 3),

    -- Security
    signature VARCHAR(500),

    -- Meta
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_qr_codes_user ON qr_codes(user_id, created_at DESC);
CREATE INDEX idx_qr_codes_code ON qr_codes(code);
CREATE INDEX idx_qr_codes_status ON qr_codes(status);
CREATE INDEX idx_qr_codes_advantage ON qr_codes(advantage_id);
CREATE UNIQUE INDEX idx_qr_codes_active_user_advantage ON qr_codes(user_id, advantage_id, status)
WHERE status = 'active';

-- ============================================
-- TABLE : transactions
-- ============================================

CREATE TABLE transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- References
    user_id UUID NOT NULL REFERENCES users(id),
    merchant_id UUID NOT NULL REFERENCES merchants(id),
    advantage_id UUID NOT NULL REFERENCES advantages(id),
    qr_code_id UUID REFERENCES qr_codes(id),

    -- Amounts
    original_amount DECIMAL(10, 3) NOT NULL,
    discount_amount DECIMAL(10, 3) NOT NULL,
    final_amount DECIMAL(10, 3) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TND',

    -- Transaction details
    transaction_date TIMESTAMP NOT NULL DEFAULT NOW(),
    validated_by UUID REFERENCES users(id),

    -- Location
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),

    -- Gamification
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

    -- References
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    merchant_id UUID REFERENCES merchants(id) ON DELETE CASCADE,
    advantage_id UUID REFERENCES advantages(id) ON DELETE CASCADE,
    transaction_id UUID REFERENCES transactions(id),

    -- Rating and comment
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,

    -- Media
    photos TEXT[],

    -- Merchant response
    merchant_response TEXT,
    merchant_response_at TIMESTAMP,
    merchant_response_by UUID REFERENCES users(id),

    -- Moderation
    status VARCHAR(50) DEFAULT 'pending',
    moderated_by UUID REFERENCES users(id),
    moderated_at TIMESTAMP,
    rejection_reason TEXT,

    -- Helpfulness
    helpful_count INTEGER DEFAULT 0,
    not_helpful_count INTEGER DEFAULT 0,

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

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

    -- Type and content
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,

    -- Data
    data JSONB,

    -- Navigation
    action_url VARCHAR(500),
    deep_link VARCHAR(500),

    -- Status
    read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,

    -- Delivery
    sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP,
    delivery_status VARCHAR(50),
    fcm_message_id VARCHAR(255),

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    expires_at TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id, read, created_at DESC);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_sent ON notifications(sent_at DESC) WHERE sent = TRUE;
