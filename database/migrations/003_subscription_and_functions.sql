-- ============================================
-- FREEOUI - SUBSCRIPTION PLANS AND UTILITY FUNCTIONS
-- ============================================

-- ============================================
-- TABLE : subscription_plans
-- ============================================

CREATE TABLE subscription_plans (
    id SERIAL PRIMARY KEY,

    -- Information
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,

    -- Pricing
    price_monthly DECIMAL(10, 3) NOT NULL,
    price_quarterly DECIMAL(10, 3),
    price_yearly DECIMAL(10, 3),
    currency VARCHAR(3) DEFAULT 'TND',

    -- Limits
    max_active_advantages INTEGER,
    max_photos_per_advantage INTEGER DEFAULT 10,
    monthly_alerts_quota INTEGER,

    -- Features (JSON)
    features JSONB,

    -- Visibility
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,

    -- Display order
    sort_order INTEGER DEFAULT 0,

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Insert default plans
INSERT INTO subscription_plans (name_fr, name_ar, slug, price_monthly, max_active_advantages, monthly_alerts_quota, features, sort_order) VALUES
('Starter', 'المبتدئ', 'starter', 49.000, 3, 1000,
'{"analytics": false, "priority_support": false, "featured_listing": false, "heat_map": false}', 1),
('Business', 'الأعمال', 'business', 99.000, 10, 5000,
'{"analytics": true, "priority_support": false, "featured_listing": false, "heat_map": true}', 2),
('Premium', 'المميز', 'premium', 199.000, NULL, 20000,
'{"analytics": true, "priority_support": true, "featured_listing": true, "heat_map": true, "ai_recommendations": true}', 3);

-- ============================================
-- TABLE : merchant_subscriptions
-- ============================================

CREATE TABLE merchant_subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL REFERENCES merchants(id) ON DELETE CASCADE,
    plan_id INTEGER NOT NULL REFERENCES subscription_plans(id),

    -- Period
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    billing_cycle VARCHAR(20) NOT NULL,

    -- Payment
    amount DECIMAL(10, 3) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TND',
    payment_status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    payment_date TIMESTAMP,

    -- Renewal
    auto_renew BOOLEAN DEFAULT TRUE,
    renewal_reminder_sent BOOLEAN DEFAULT FALSE,

    -- Status
    status VARCHAR(50) DEFAULT 'active',
    cancelled_at TIMESTAMP,
    cancellation_reason TEXT,

    -- Meta
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_merchant_subscriptions_merchant ON merchant_subscriptions(merchant_id);
CREATE INDEX idx_merchant_subscriptions_status ON merchant_subscriptions(status, end_date);

-- ============================================
-- MATERIALIZED VIEWS
-- ============================================

-- Merchant statistics view
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

-- ============================================
-- UTILITY FUNCTIONS
-- ============================================

-- Function: Calculate distance between two GPS points
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

-- Function: Find nearby merchants
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

-- Function: Find nearby advantages with filters
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

-- Function: Calculate proximity alert relevance score
CREATE OR REPLACE FUNCTION calculate_alert_relevance_score(
    distance_m DECIMAL,
    discount_pct DECIMAL,
    uses_count INTEGER,
    avg_rating DECIMAL,
    expires_soon BOOLEAN
)
RETURNS DECIMAL AS $$
DECLARE
    score DECIMAL := 0;
BEGIN
    -- Proximity score (max 30 points)
    score := score + ((1000 - LEAST(distance_m, 1000)) / 1000 * 30);

    -- Discount score (max 50 points)
    score := score + (COALESCE(discount_pct, 0) * 0.5);

    -- Popularity score (max 20 points)
    score := score + LEAST(uses_count / 100.0, 20);

    -- Quality score (max 25 points)
    score := score + (COALESCE(avg_rating, 0) * 5);

    -- Urgency bonus (15 points)
    IF expires_soon THEN
        score := score + 15;
    END IF;

    RETURN ROUND(score, 2);
END;
$$ LANGUAGE plpgsql;

-- Function: Check if user should receive proximity alert (anti-spam rules)
CREATE OR REPLACE FUNCTION should_send_proximity_alert(
    p_user_id UUID,
    p_merchant_id UUID,
    p_advantage_id UUID
)
RETURNS BOOLEAN AS $$
DECLARE
    alerts_today INTEGER;
    last_alert_time TIMESTAMP;
    already_used BOOLEAN;
BEGIN
    -- Rule 1: Max 5 alerts per day
    SELECT COUNT(*) INTO alerts_today
    FROM proximity_alerts_log
    WHERE user_id = p_user_id
    AND created_at >= CURRENT_DATE;

    IF alerts_today >= 5 THEN
        RETURN FALSE;
    END IF;

    -- Rule 2: Same merchant max once per day
    SELECT MAX(created_at) INTO last_alert_time
    FROM proximity_alerts_log
    WHERE user_id = p_user_id
    AND merchant_id = p_merchant_id
    AND created_at >= CURRENT_DATE;

    IF last_alert_time IS NOT NULL THEN
        RETURN FALSE;
    END IF;

    -- Rule 3: Min 30 minutes between any alerts
    SELECT MAX(created_at) INTO last_alert_time
    FROM proximity_alerts_log
    WHERE user_id = p_user_id
    AND created_at >= NOW() - INTERVAL '30 minutes';

    IF last_alert_time IS NOT NULL THEN
        RETURN FALSE;
    END IF;

    -- Rule 4: Don't alert if already used this advantage
    SELECT EXISTS(
        SELECT 1 FROM transactions
        WHERE user_id = p_user_id
        AND advantage_id = p_advantage_id
    ) INTO already_used;

    IF already_used THEN
        RETURN FALSE;
    END IF;

    RETURN TRUE;
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Update merchant stats after transaction
CREATE OR REPLACE FUNCTION update_merchant_stats_after_transaction()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE merchants
    SET total_transactions = total_transactions + 1
    WHERE id = NEW.merchant_id;

    UPDATE advantages
    SET uses_count = uses_count + 1,
        conversion_rate = (uses_count::DECIMAL / NULLIF(proximity_alerts_sent, 0)) * 100
    WHERE id = NEW.advantage_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_merchant_stats_after_transaction
AFTER INSERT ON transactions
FOR EACH ROW
EXECUTE FUNCTION update_merchant_stats_after_transaction();

-- Trigger: Update user points after transaction
CREATE OR REPLACE FUNCTION update_user_points_after_transaction()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE users
    SET points_balance = points_balance + NEW.points_earned,
        total_savings_tnd = total_savings_tnd + NEW.discount_amount
    WHERE id = NEW.user_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_user_points_after_transaction
AFTER INSERT ON transactions
FOR EACH ROW
EXECUTE FUNCTION update_user_points_after_transaction();

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional performance indexes
CREATE INDEX idx_advantages_active_dates
ON advantages(start_date, end_date)
WHERE status = 'active';

CREATE INDEX idx_user_locations_recent
ON user_locations(user_id, recorded_at DESC)
WHERE recorded_at >= NOW() - INTERVAL '24 hours';

CREATE INDEX idx_proximity_alerts_recent
ON proximity_alerts_log(user_id, merchant_id, created_at DESC)
WHERE created_at >= NOW() - INTERVAL '7 days';

-- ============================================
-- INITIAL ADMIN USER
-- ============================================

-- Insert default admin user (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO users (
    phone_number,
    country_code,
    first_name,
    last_name,
    email,
    email_verified,
    password,
    is_verified,
    preferred_language
) VALUES (
    '+21612345678',
    '+216',
    'Admin',
    'FreeOui',
    'admin@freeoui.tn',
    TRUE,
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NANvJ5vU0q6q', -- bcrypt hash of 'admin123'
    TRUE,
    'fr'
);

-- ============================================
-- COMMENTS FOR DOCUMENTATION
-- ============================================

COMMENT ON TABLE users IS 'Application users (consumers)';
COMMENT ON TABLE merchants IS 'Business merchants offering advantages';
COMMENT ON TABLE advantages IS 'Promotional offers created by merchants';
COMMENT ON TABLE proximity_alerts_log IS 'Log of all proximity-based alert notifications sent';
COMMENT ON TABLE qr_codes IS 'QR codes generated by users to redeem advantages';
COMMENT ON TABLE transactions IS 'Completed transactions when QR codes are validated';
COMMENT ON TABLE user_locations IS 'GPS tracking data for proximity detection';
COMMENT ON FUNCTION find_nearby_advantages IS 'Find active advantages near user location with filters';
COMMENT ON FUNCTION should_send_proximity_alert IS 'Anti-spam rules for proximity alerts';
