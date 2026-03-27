CREATE DATABASE IF NOT EXISTS azure_horizon
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE azure_horizon;

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    occupancy INT NOT NULL,
    view_type VARCHAR(50) DEFAULT NULL,
    is_accessible TINYINT(1) DEFAULT 0,
    size_label VARCHAR(100) DEFAULT NULL,
    bed_summary VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_images (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_images_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_features (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    feature_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_features_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_benefits (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    benefit_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_benefits_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_bathroom_features (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    feature_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_bathroom_features_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_furnishings (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    item_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_furnishings_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    room_id INT DEFAULT NULL,
    check_in DATE DEFAULT NULL,
    check_out DATE DEFAULT NULL,
    total_price DECIMAL(10, 2) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Confirmed',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT bookings_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amenities (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (id)
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
