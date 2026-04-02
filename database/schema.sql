CREATE DATABASE IF NOT EXISTS azure_horizon
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE azure_horizon;

DROP TABLE IF EXISTS loyalty_history;
DROP TABLE IF EXISTS user_loyalty;

CREATE TABLE IF NOT EXISTS loyalty_tiers (
    id INT NOT NULL AUTO_INCREMENT,
    tier_name VARCHAR(50) NOT NULL,
    min_spending DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_rate DECIMAL(5, 4) NOT NULL DEFAULT 0.0000,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY loyalty_tiers_name_uk (tier_name),
    KEY loyalty_tiers_min_idx (min_spending)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO loyalty_tiers (tier_name, min_spending, discount_rate)
SELECT 'Bronze', 0.00, 0.0000
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE tier_name = 'Bronze');

INSERT INTO loyalty_tiers (tier_name, min_spending, discount_rate)
SELECT 'Silver', 500.00, 0.0500
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE tier_name = 'Silver');

INSERT INTO loyalty_tiers (tier_name, min_spending, discount_rate)
SELECT 'Gold', 1500.00, 0.1000
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE tier_name = 'Gold');

INSERT INTO loyalty_tiers (tier_name, min_spending, discount_rate)
SELECT 'Platinum', 3000.00, 0.1500
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE tier_name = 'Platinum');

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  total_spent DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  loyalty_tier_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @users_phone_exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND column_name = 'phone'
);
SET @users_phone_sql := IF(@users_phone_exists = 0, 'ALTER TABLE users ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER password', 'SELECT 1');
PREPARE users_phone_stmt FROM @users_phone_sql;
EXECUTE users_phone_stmt;
DEALLOCATE PREPARE users_phone_stmt;

SET @users_is_admin_exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND column_name = 'is_admin'
);
SET @users_is_admin_sql := IF(@users_is_admin_exists = 0, 'ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER phone', 'SELECT 1');
PREPARE users_is_admin_stmt FROM @users_is_admin_sql;
EXECUTE users_is_admin_stmt;
DEALLOCATE PREPARE users_is_admin_stmt;

SET @users_total_spent_exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND column_name = 'total_spent'
);
SET @users_total_spent_sql := IF(@users_total_spent_exists = 0, 'ALTER TABLE users ADD COLUMN total_spent DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER is_admin', 'SELECT 1');
PREPARE users_total_spent_stmt FROM @users_total_spent_sql;
EXECUTE users_total_spent_stmt;
DEALLOCATE PREPARE users_total_spent_stmt;

SET @users_loyalty_tier_id_exists := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND column_name = 'loyalty_tier_id'
);
SET @users_loyalty_tier_id_sql := IF(@users_loyalty_tier_id_exists = 0, 'ALTER TABLE users ADD COLUMN loyalty_tier_id INT DEFAULT NULL AFTER total_spent', 'SELECT 1');
PREPARE users_loyalty_tier_id_stmt FROM @users_loyalty_tier_id_sql;
EXECUTE users_loyalty_tier_id_stmt;
DEALLOCATE PREPARE users_loyalty_tier_id_stmt;

SET @users_loyalty_fk_exists := (
  SELECT COUNT(*)
  FROM information_schema.table_constraints
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND constraint_name = 'users_loyalty_tier_fk'
    AND constraint_type = 'FOREIGN KEY'
);
SET @users_loyalty_fk_sql := IF(
  @users_loyalty_fk_exists = 0,
  'ALTER TABLE users ADD CONSTRAINT users_loyalty_tier_fk FOREIGN KEY (loyalty_tier_id) REFERENCES loyalty_tiers(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE users_loyalty_fk_stmt FROM @users_loyalty_fk_sql;
EXECUTE users_loyalty_fk_stmt;
DEALLOCATE PREPARE users_loyalty_fk_stmt;

DROP TABLE IF EXISTS room_images;
DROP TABLE IF EXISTS room_features;
DROP TABLE IF EXISTS room_benefits;
DROP TABLE IF EXISTS room_bathroom_features;
DROP TABLE IF EXISTS room_furnishings;
DROP TABLE IF EXISTS amenities;
DROP TABLE IF EXISTS rooms;

CREATE TABLE IF NOT EXISTS bookings (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    room_id INT DEFAULT NULL,
    room_name VARCHAR(150) DEFAULT NULL,
    guest_name VARCHAR(150) DEFAULT NULL,
    guest_email VARCHAR(150) DEFAULT NULL,
    guest_phone VARCHAR(50) DEFAULT NULL,
    billing_address VARCHAR(255) DEFAULT NULL,
    billing_city VARCHAR(100) DEFAULT NULL,
    billing_postal VARCHAR(30) DEFAULT NULL,
    check_in DATE DEFAULT NULL,
    check_out DATE DEFAULT NULL,
    nights INT NOT NULL DEFAULT 1,
    room_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(10, 2) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Confirmed',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY bookings_room_idx (room_id),
    KEY bookings_user_idx (user_id),
    KEY bookings_dates_idx (check_in, check_out)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @bookings_room_name_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'room_name'
);
SET @bookings_room_name_sql := IF(@bookings_room_name_exists = 0, 'ALTER TABLE bookings ADD COLUMN room_name VARCHAR(150) DEFAULT NULL AFTER room_id', 'SELECT 1');
PREPARE bookings_room_name_stmt FROM @bookings_room_name_sql;
EXECUTE bookings_room_name_stmt;
DEALLOCATE PREPARE bookings_room_name_stmt;

SET @bookings_guest_name_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'guest_name'
);
SET @bookings_guest_name_sql := IF(@bookings_guest_name_exists = 0, 'ALTER TABLE bookings ADD COLUMN guest_name VARCHAR(150) DEFAULT NULL AFTER room_name', 'SELECT 1');
PREPARE bookings_guest_name_stmt FROM @bookings_guest_name_sql;
EXECUTE bookings_guest_name_stmt;
DEALLOCATE PREPARE bookings_guest_name_stmt;

SET @bookings_guest_email_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'guest_email'
);
SET @bookings_guest_email_sql := IF(@bookings_guest_email_exists = 0, 'ALTER TABLE bookings ADD COLUMN guest_email VARCHAR(150) DEFAULT NULL AFTER guest_name', 'SELECT 1');
PREPARE bookings_guest_email_stmt FROM @bookings_guest_email_sql;
EXECUTE bookings_guest_email_stmt;
DEALLOCATE PREPARE bookings_guest_email_stmt;

SET @bookings_guest_phone_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'guest_phone'
);
SET @bookings_guest_phone_sql := IF(@bookings_guest_phone_exists = 0, 'ALTER TABLE bookings ADD COLUMN guest_phone VARCHAR(50) DEFAULT NULL AFTER guest_email', 'SELECT 1');
PREPARE bookings_guest_phone_stmt FROM @bookings_guest_phone_sql;
EXECUTE bookings_guest_phone_stmt;
DEALLOCATE PREPARE bookings_guest_phone_stmt;

SET @bookings_billing_address_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'billing_address'
);
SET @bookings_billing_address_sql := IF(@bookings_billing_address_exists = 0, 'ALTER TABLE bookings ADD COLUMN billing_address VARCHAR(255) DEFAULT NULL AFTER guest_phone', 'SELECT 1');
PREPARE bookings_billing_address_stmt FROM @bookings_billing_address_sql;
EXECUTE bookings_billing_address_stmt;
DEALLOCATE PREPARE bookings_billing_address_stmt;

SET @bookings_billing_city_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'billing_city'
);
SET @bookings_billing_city_sql := IF(@bookings_billing_city_exists = 0, 'ALTER TABLE bookings ADD COLUMN billing_city VARCHAR(100) DEFAULT NULL AFTER billing_address', 'SELECT 1');
PREPARE bookings_billing_city_stmt FROM @bookings_billing_city_sql;
EXECUTE bookings_billing_city_stmt;
DEALLOCATE PREPARE bookings_billing_city_stmt;

SET @bookings_billing_postal_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'billing_postal'
);
SET @bookings_billing_postal_sql := IF(@bookings_billing_postal_exists = 0, 'ALTER TABLE bookings ADD COLUMN billing_postal VARCHAR(30) DEFAULT NULL AFTER billing_city', 'SELECT 1');
PREPARE bookings_billing_postal_stmt FROM @bookings_billing_postal_sql;
EXECUTE bookings_billing_postal_stmt;
DEALLOCATE PREPARE bookings_billing_postal_stmt;

SET @bookings_nights_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'nights'
);
SET @bookings_nights_sql := IF(@bookings_nights_exists = 0, 'ALTER TABLE bookings ADD COLUMN nights INT NOT NULL DEFAULT 1 AFTER check_out', 'SELECT 1');
PREPARE bookings_nights_stmt FROM @bookings_nights_sql;
EXECUTE bookings_nights_stmt;
DEALLOCATE PREPARE bookings_nights_stmt;

SET @bookings_room_rate_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND column_name = 'room_rate'
);
SET @bookings_room_rate_sql := IF(@bookings_room_rate_exists = 0, 'ALTER TABLE bookings ADD COLUMN room_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER nights', 'SELECT 1');
PREPARE bookings_room_rate_stmt FROM @bookings_room_rate_sql;
EXECUTE bookings_room_rate_stmt;
DEALLOCATE PREPARE bookings_room_rate_stmt;

SET @bookings_room_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND constraint_name = 'bookings_ibfk_1'
      AND constraint_type = 'FOREIGN KEY'
);
SET @drop_bookings_room_fk_sql := IF(@bookings_room_fk_exists > 0, 'ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_1', 'SELECT 1');
PREPARE drop_bookings_room_fk_stmt FROM @drop_bookings_room_fk_sql;
EXECUTE drop_bookings_room_fk_stmt;
DEALLOCATE PREPARE drop_bookings_room_fk_stmt;

SET @bookings_user_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'bookings'
      AND constraint_name = 'bookings_user_fk'
      AND constraint_type = 'FOREIGN KEY'
);
SET @bookings_user_fk_sql := IF(
    @bookings_user_fk_exists = 0,
    'ALTER TABLE bookings ADD CONSTRAINT bookings_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE bookings_user_fk_stmt FROM @bookings_user_fk_sql;
EXECUTE bookings_user_fk_stmt;
DEALLOCATE PREPARE bookings_user_fk_stmt;

CREATE TABLE IF NOT EXISTS spa_bookings (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    guest_name VARCHAR(150) NOT NULL,
    guest_email VARCHAR(150) NOT NULL,
    treatment_name VARCHAR(150) NOT NULL,
    treatment_date DATE NOT NULL,
    treatment_time TIME NOT NULL,
    guests INT NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY spa_bookings_user_idx (user_id),
    KEY spa_bookings_schedule_idx (treatment_date, treatment_time),
    CONSTRAINT spa_bookings_user_fk
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(150) NOT NULL,
    rating INT NOT NULL,
    title VARCHAR(150) DEFAULT NULL,
    body TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    food TINYINT(1) NOT NULL DEFAULT 0,
    room TINYINT(1) NOT NULL DEFAULT 0,
    views TINYINT(1) NOT NULL DEFAULT 0,
    service TINYINT(1) NOT NULL DEFAULT 0,
    amenities TINYINT(1) NOT NULL DEFAULT 0,
    cleanliness TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @reviews_food_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reviews'
      AND column_name = 'food'
);
SET @reviews_food_sql := IF(@reviews_food_exists = 0, 'ALTER TABLE reviews ADD COLUMN food TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE reviews_food_stmt FROM @reviews_food_sql;
EXECUTE reviews_food_stmt;
DEALLOCATE PREPARE reviews_food_stmt;

SET @reviews_room_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reviews'
      AND column_name = 'room'
);
SET @reviews_room_sql := IF(@reviews_room_exists = 0, 'ALTER TABLE reviews ADD COLUMN room TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE reviews_room_stmt FROM @reviews_room_sql;
EXECUTE reviews_room_stmt;
DEALLOCATE PREPARE reviews_room_stmt;

SET @reviews_views_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reviews'
      AND column_name = 'views'
);
SET @reviews_views_sql := IF(@reviews_views_exists = 0, 'ALTER TABLE reviews ADD COLUMN views TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE reviews_views_stmt FROM @reviews_views_sql;
EXECUTE reviews_views_stmt;
DEALLOCATE PREPARE reviews_views_stmt;

SET @reviews_service_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reviews'
      AND column_name = 'service'
);
SET @reviews_service_sql := IF(@reviews_service_exists = 0, 'ALTER TABLE reviews ADD COLUMN service TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE reviews_service_stmt FROM @reviews_service_sql;
EXECUTE reviews_service_stmt;
DEALLOCATE PREPARE reviews_service_stmt;

SET @reviews_amenities_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reviews'
      AND column_name = 'amenities'
);
SET @reviews_amenities_sql := IF(@reviews_amenities_exists = 0, 'ALTER TABLE reviews ADD COLUMN amenities TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE reviews_amenities_stmt FROM @reviews_amenities_sql;
EXECUTE reviews_amenities_stmt;
DEALLOCATE PREPARE reviews_amenities_stmt;

SET @reviews_cleanliness_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reviews'
      AND column_name = 'cleanliness'
);
SET @reviews_cleanliness_sql := IF(@reviews_cleanliness_exists = 0, 'ALTER TABLE reviews ADD COLUMN cleanliness TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE reviews_cleanliness_stmt FROM @reviews_cleanliness_sql;
EXECUTE reviews_cleanliness_stmt;
DEALLOCATE PREPARE reviews_cleanliness_stmt;

SET @review_categories_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'review_categories'
);

SET @migrate_review_categories_sql := IF(
    @review_categories_exists > 0,
    'UPDATE reviews r LEFT JOIN (SELECT review_id, MAX(CASE WHEN category_code = ''food'' THEN 1 ELSE 0 END) AS food, MAX(CASE WHEN category_code = ''room'' THEN 1 ELSE 0 END) AS room, MAX(CASE WHEN category_code = ''views'' THEN 1 ELSE 0 END) AS views, MAX(CASE WHEN category_code = ''service'' THEN 1 ELSE 0 END) AS service, MAX(CASE WHEN category_code = ''amenities'' THEN 1 ELSE 0 END) AS amenities, MAX(CASE WHEN category_code = ''cleanliness'' THEN 1 ELSE 0 END) AS cleanliness FROM review_categories GROUP BY review_id) rc ON rc.review_id = r.id SET r.food = GREATEST(COALESCE(r.food, 0), COALESCE(rc.food, 0)), r.room = GREATEST(COALESCE(r.room, 0), COALESCE(rc.room, 0)), r.views = GREATEST(COALESCE(r.views, 0), COALESCE(rc.views, 0)), r.service = GREATEST(COALESCE(r.service, 0), COALESCE(rc.service, 0)), r.amenities = GREATEST(COALESCE(r.amenities, 0), COALESCE(rc.amenities, 0)), r.cleanliness = GREATEST(COALESCE(r.cleanliness, 0), COALESCE(rc.cleanliness, 0))',
    'SELECT 1'
);

PREPARE migrate_review_categories_stmt FROM @migrate_review_categories_sql;
EXECUTE migrate_review_categories_stmt;
DEALLOCATE PREPARE migrate_review_categories_stmt;

SET @drop_review_categories_sql := IF(
    @review_categories_exists > 0,
    'DROP TABLE review_categories',
    'SELECT 1'
);

PREPARE drop_review_categories_stmt FROM @drop_review_categories_sql;
EXECUTE drop_review_categories_stmt;
DEALLOCATE PREPARE drop_review_categories_stmt;

INSERT INTO reviews (user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
SELECT 'Alicia T.', 5, 'Perfect weekend getaway', 'Smooth check-in, spotless room, and the view at sunrise was unreal. We loved how calm the lobby felt even when it was busy.', 'assets/images/HotelHomePage.webp', 0, 1, 1, 1, 0, 1, 1, '2026-03-25 08:00:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Alicia T.'
      AND title = 'Perfect weekend getaway'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
SELECT 'Marcus L.', 4, 'Great amenities, minor wait', 'Facilities were excellent and the spa was a highlight. Only downside was a short wait during peak dinner time, but staff handled it well.', 'assets/images/SpaRoom.webp', 1, 0, 0, 1, 1, 0, 1, '2026-03-24 19:30:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Marcus L.'
      AND title = 'Great amenities, minor wait'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
SELECT 'Nur S.', 5, 'Best views from the lounge', 'The skyline view from the lounge is stunning. Clean, quiet, and very comfortable. Will definitely come back for another staycation.', 'assets/images/AboutUs.webp', 0, 1, 1, 0, 0, 1, 1, '2026-03-23 14:15:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Nur S.'
      AND title = 'Best views from the lounge'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
SELECT 'Daniel R.', 4, 'Clean rooms and good breakfast', 'Breakfast had a solid range and everything tasted fresh. The room was well-kept and the bed was super comfortable.', 'assets/images/dining/Cafe.webp', 1, 1, 0, 0, 0, 1, 1, '2026-03-22 09:10:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Daniel R.'
      AND title = 'Clean rooms and good breakfast'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
SELECT 'Priya M.', 5, 'Spa day was amazing', 'Booked a spa session and it was honestly the best part of the trip. Quiet, relaxing, and the facilities were immaculate.', 'assets/images/SpaRoom.webp', 0, 0, 0, 1, 1, 1, 1, '2026-03-21 16:45:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Priya M.'
      AND title = 'Spa day was amazing'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
SELECT 'Olivia B.', 5, 'Loved the amenities', 'Gym was well-equipped and not crowded. Everything felt polished and thoughtfully designed. Would recommend for business trips too.', 'assets/images/Suite1.webp', 0, 0, 0, 1, 1, 1, 1, '2026-03-20 11:20:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Olivia B.'
      AND title = 'Loved the amenities'
);
